<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M00Core\Services\AuditService;
use Modules\M02Customers\Models\Customer;
use Modules\M05Contracts\Models\Contract;
use Modules\M07Invoicing\Models\Invoice;
use Modules\M07Invoicing\Services\InvoiceService;

class InvoiceController extends ApiController
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * List invoices with filtering, pagination and financial KPIs.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Invoice::query()->with([
            'customer',
            'branch',
            'contract.vehicle',
        ]);

        // Scoping
        if ($user && ! $user->hasRole('Super Admin') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', $search)
                  ->orWhere('customer_name', 'like', $search)
                  ->orWhere('customer_nif', 'like', $search)
                  ->orWhereHas('contract', function ($cq) use ($search) {
                      $cq->where('contract_number', 'like', $search);
                  });
            });
        }

        // Financial KPIs
        $metricsQuery = clone $query;
        $totalBilled = (clone $metricsQuery)->whereNotIn('status', [Invoice::STATUS_CANCELLED])->sum('total_ttc_dzd');
        $totalPaid = (clone $metricsQuery)->whereNotIn('status', [Invoice::STATUS_CANCELLED])->sum('paid_amount_dzd');
        $totalDue = (clone $metricsQuery)->whereNotIn('status', [Invoice::STATUS_CANCELLED])->sum('remaining_amount_dzd');
        $paidCount = (clone $metricsQuery)->where('status', Invoice::STATUS_PAID)->count();
        $unpaidCount = (clone $metricsQuery)->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])->count();

        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->ok($invoices->items(), [
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'metrics' => [
                'total_billed_dzd' => (float) $totalBilled,
                'total_paid_dzd' => (float) $totalPaid,
                'total_due_dzd' => (float) $totalDue,
                'paid_count' => $paidCount,
                'unpaid_count' => $unpaidCount,
            ],
        ]);
    }

    /**
     * Create manual invoice with custom line items.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'payment_method' => 'required|string|in:cash,cheque,bank_transfer,cib_edahabia',
            'payment_terms' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price_ht_dzd' => 'required|numeric|min:0',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        // Calculate subtotal
        $subtotalHt = 0.0;
        foreach ($validated['items'] as $item) {
            $subtotalHt += ((float) $item['quantity']) * ((float) $item['unit_price_ht_dzd']);
        }

        $taxes = $this->invoiceService->calculateTaxes($subtotalHt, 19.00, $validated['payment_method']);

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'contract_id' => $validated['contract_id'] ?? null,
            'customer_id' => $customer->id,
            'branch_id' => $validated['branch_id'],
            'created_by' => $request->user()?->id,
            'status' => Invoice::STATUS_ISSUED,
            'type' => 'standard',
            'issue_date' => Carbon::parse($validated['issue_date']),
            'due_date' => Carbon::parse($validated['due_date']),
            'subtotal_ht_dzd' => $taxes['subtotal_ht'],
            'vat_rate' => $taxes['vat_rate'],
            'vat_amount_dzd' => $taxes['vat_amount'],
            'stamp_duty_dzd' => $taxes['stamp_duty'],
            'total_ttc_dzd' => $taxes['total_ttc'],
            'paid_amount_dzd' => 0.0,
            'remaining_amount_dzd' => $taxes['total_ttc'],
            'customer_name' => $customer->full_name,
            'customer_nif' => $customer->company_tax_id ?? $customer->national_id_number,
            'customer_rc' => $customer->company_name ? ($customer->company_tax_id ?? null) : null,
            'customer_address' => ($customer->address ? $customer->address . ', ' : '') . ($customer->city ?? 'Alger'),
            'payment_method' => $validated['payment_method'],
            'payment_terms' => $validated['payment_terms'] ?? 'Règlement à réception',
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $qty = (float) $item['quantity'];
            $unitHt = (float) $item['unit_price_ht_dzd'];
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $qty,
                'unit_price_ht_dzd' => $unitHt,
                'total_ht_dzd' => round($qty * $unitHt, 2),
                'vat_rate' => 19.00,
            ]);
        }

        $this->auditService->log('M07Invoicing', 'invoice.created', $invoice, null, [
            'invoice_number' => $invoice->invoice_number,
            'total_ttc_dzd' => $invoice->total_ttc_dzd,
        ]);

        return $this->created($invoice->load(['items', 'customer', 'branch']));
    }

    /**
     * Show detailed invoice.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            'items',
            'customer',
            'branch',
            'contract.vehicle',
            'creator',
            'payments.receiver',
        ]);

        return $this->ok($invoice);
    }

    /**
     * Generate invoice directly from contract.
     */
    public function fromContract(Request $request, Contract $contract): JsonResponse
    {
        $paymentMethod = $request->input('payment_method', 'cheque');
        $invoice = $this->invoiceService->generateFromContract($contract, $request->user(), $paymentMethod);

        return $this->created($invoice);
    }

    /**
     * Record payment against invoice.
     */
    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'amount_dzd' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,cheque,bank_transfer,cib_edahabia',
            'payment_date' => 'nullable|date',
            'reference_details' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment = $this->invoiceService->recordPayment($invoice, $validated, $request->user());

        return $this->created([
            'payment' => $payment,
            'invoice' => $invoice->fresh()->load(['payments.receiver', 'customer']),
        ]);
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->paid_amount_dzd > 0) {
            return $this->badRequest('Une facture ayant déjà reçu des règlements ne peut pas être annulée directement.');
        }

        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

        $this->auditService->log('M07Invoicing', 'invoice.cancelled', $invoice);

        return $this->ok($invoice->fresh());
    }

    /**
     * Delete invoice.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->auditService->log('M07Invoicing', 'invoice.deleted', $invoice);
        $invoice->delete();

        return $this->noContent();
    }
}

