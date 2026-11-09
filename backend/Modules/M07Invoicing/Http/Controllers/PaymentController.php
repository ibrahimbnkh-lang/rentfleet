<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Http\Controllers;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\M07Invoicing\Models\Payment;

class PaymentController extends ApiController
{
    /**
     * List payments (Cash book / Journal des encaissements).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::query()->with([
            'invoice.customer',
            'customer',
            'branch',
            'receiver',
        ]);

        if ($user && ! $user->hasRole('Super Admin') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }

        if ($request->filled('from_date')) {
            $query->where('payment_date', '>=', Carbon::parse((string) $request->input('from_date'))->startOfDay());
        }
        if ($request->filled('to_date')) {
            $query->where('payment_date', '<=', Carbon::parse((string) $request->input('to_date'))->endOfDay());
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', $search)
                  ->orWhere('reference_details', 'like', $search)
                  ->orWhereHas('customer', fn ($cq) => $cq->where('first_name', 'like', $search)->orWhere('last_name', 'like', $search)->orWhere('company_name', 'like', $search))
                  ->orWhereHas('invoice', fn ($iq) => $iq->where('invoice_number', 'like', $search));
            });
        }

        // Method breakdowns
        $totalsQuery = clone $query;
        $totalCollected = (clone $totalsQuery)->sum('amount_dzd');
        $cashTotal = (clone $totalsQuery)->where('payment_method', 'cash')->sum('amount_dzd');
        $chequeTotal = (clone $totalsQuery)->where('payment_method', 'cheque')->sum('amount_dzd');
        $transferTotal = (clone $totalsQuery)->where('payment_method', 'bank_transfer')->sum('amount_dzd');
        $cibTotal = (clone $totalsQuery)->where('payment_method', 'cib_edahabia')->sum('amount_dzd');

        $payments = $query->orderByDesc('payment_date')->paginate($request->integer('per_page', 20));

        return $this->ok($payments->items(), [
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
            'metrics' => [
                'total_collected_dzd' => (float) $totalCollected,
                'cash_total_dzd' => (float) $cashTotal,
                'cheque_total_dzd' => (float) $chequeTotal,
                'transfer_total_dzd' => (float) $transferTotal,
                'cib_total_dzd' => (float) $cibTotal,
            ],
        ]);
    }

    /**
     * Show single payment receipt.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load([
            'invoice.items',
            'customer',
            'branch',
            'receiver',
        ]);

        return $this->ok($payment);
    }
}

