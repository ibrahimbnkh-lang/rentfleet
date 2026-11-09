<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\M00Core\Services\AuditService;
use Modules\M02Customers\Models\Customer;
use Modules\M05Contracts\Models\Contract;
use Modules\M07Invoicing\Models\Invoice;
use Modules\M07Invoicing\Models\InvoiceItem;
use Modules\M07Invoicing\Models\Payment;

class InvoiceService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * Compute Algerian tax breakdown (HT, TVA 19%, Droit de timbre if cash, Total TTC).
     *
     * @return array{
     *     subtotal_ht: float,
     *     vat_rate: float,
     *     vat_amount: float,
     *     stamp_duty: float,
     *     total_ttc: float,
     * }
     */
    public function calculateTaxes(float $subtotalHt, float $vatRate = 19.00, string $paymentMethod = 'cash'): array
    {
        $subtotal = round($subtotalHt, 2);
        $vat = round($subtotal * ($vatRate / 100), 2);
        $baseTtc = $subtotal + $vat;

        // Algerian stamp duty (Droit de timbre pour paiement en espèces: 1% avec plancher et plafond)
        $stampDuty = 0.0;
        if ($paymentMethod === 'cash' && $baseTtc > 0) {
            $rawStamp = ceil($baseTtc * 0.01);
            $stampDuty = (float) max(20.0, min(2500.0, $rawStamp));
        }

        $totalTtc = round($baseTtc + $stampDuty, 2);

        return [
            'subtotal_ht' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vat,
            'stamp_duty' => $stampDuty,
            'total_ttc' => $totalTtc,
        ];
    }

    /**
     * Generate invoice automatically from a rental contract.
     */
    public function generateFromContract(Contract $contract, ?User $user, string $paymentMethod = 'cheque'): Invoice
    {
        $customer = $contract->customer;
        $vehicle = $contract->vehicle;

        // Base rental HT calculations (assuming contract rate is TTC, we back-calculate HT: TTC / 1.19)
        $rateTtc = (float) $contract->daily_rate_dzd;
        $rateHt = round($rateTtc / 1.19, 2);
        $days = (int) $contract->days_count;
        $rentalSubtotalHt = round($rateHt * $days, 2);

        $items = [];
        $items[] = [
            'description' => "Location {$vehicle->brand} {$vehicle->model} (Matricule: {$vehicle->registration_plate}) — {$days} jours",
            'quantity' => $days,
            'unit_price_ht_dzd' => $rateHt,
            'total_ht_dzd' => $rentalSubtotalHt,
            'vat_rate' => 19.00,
        ];

        $extraSubtotalHt = 0.0;

        // Extra fees
        if ($contract->fuel_fee_dzd > 0) {
            $fuelHt = round(((float) $contract->fuel_fee_dzd) / 1.19, 2);
            $items[] = [
                'description' => "Frais de carburant manquant à la restitution ({$contract->end_fuel_level})",
                'quantity' => 1,
                'unit_price_ht_dzd' => $fuelHt,
                'total_ht_dzd' => $fuelHt,
                'vat_rate' => 19.00,
            ];
            $extraSubtotalHt += $fuelHt;
        }

        if ($contract->extra_mileage_fee_dzd > 0) {
            $kmHt = round(((float) $contract->extra_mileage_fee_dzd) / 1.19, 2);
            $items[] = [
                'description' => "Frais de dépassement kilométrique ({$contract->driven_mileage} km parcourus)",
                'quantity' => 1,
                'unit_price_ht_dzd' => $kmHt,
                'total_ht_dzd' => $kmHt,
                'vat_rate' => 19.00,
            ];
            $extraSubtotalHt += $kmHt;
        }

        if ($contract->damages_fee_dzd > 0) {
            $dmgHt = round(((float) $contract->damages_fee_dzd) / 1.19, 2);
            $items[] = [
                'description' => "Frais de remise en état carrosserie / accessoires",
                'quantity' => 1,
                'unit_price_ht_dzd' => $dmgHt,
                'total_ht_dzd' => $dmgHt,
                'vat_rate' => 19.00,
            ];
            $extraSubtotalHt += $dmgHt;
        }

        $totalSubtotalHt = $rentalSubtotalHt + $extraSubtotalHt;
        $taxes = $this->calculateTaxes($totalSubtotalHt, 19.00, $paymentMethod);

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'branch_id' => $contract->branch_id,
            'created_by' => $user?->id,
            'status' => Invoice::STATUS_ISSUED,
            'type' => 'standard',
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(15)->toDateString(),
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
            'payment_method' => $paymentMethod,
            'notes' => "Facture émise au titre du contrat N° {$contract->contract_number}.",
        ]);

        foreach ($items as $item) {
            $invoice->items()->create($item);
        }

        $this->auditService->log(
            module: 'M07Invoicing',
            action: 'invoice.created_from_contract',
            auditable: $invoice,
            newValues: [
                'invoice_number' => $invoice->invoice_number,
                'contract_number' => $contract->contract_number,
                'total_ttc_dzd' => $invoice->total_ttc_dzd,
            ],
        );

        return $invoice->load(['items', 'customer', 'contract', 'branch']);
    }

    /**
     * Record a payment for an invoice.
     */
    public function recordPayment(Invoice $invoice, array $data, ?User $user): Payment
    {
        $amount = (float) $data['amount_dzd'];

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount_dzd' => ['Le montant du paiement doit être supérieur à zéro.']]);
        }

        if ($amount > (float) $invoice->remaining_amount_dzd) {
            throw ValidationException::withMessages([
                'amount_dzd' => ["Le montant saisi ({$amount} DZD) excède le solde restant dû ({$invoice->remaining_amount_dzd} DZD)."],
            ]);
        }

        $payment = Payment::create([
            'payment_reference' => Payment::generatePaymentReference(),
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'branch_id' => $invoice->branch_id,
            'received_by' => $user?->id,
            'amount_dzd' => $amount,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_date' => isset($data['payment_date']) ? Carbon::parse($data['payment_date']) : now(),
            'reference_details' => $data['reference_details'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Update invoice settlement
        $newPaid = (float) ($invoice->paid_amount_dzd + $amount);
        $newRemaining = max(0.0, round((float) $invoice->total_ttc_dzd - $newPaid, 2));
        $newStatus = ($newRemaining <= 0.05) ? Invoice::STATUS_PAID : Invoice::STATUS_PARTIALLY_PAID;

        $invoice->update([
            'paid_amount_dzd' => $newPaid,
            'remaining_amount_dzd' => $newRemaining,
            'status' => $newStatus,
        ]);

        $this->auditService->log(
            module: 'M07Invoicing',
            action: 'payment.recorded',
            auditable: $payment,
            newValues: [
                'payment_reference' => $payment->payment_reference,
                'invoice_number' => $invoice->invoice_number,
                'amount_dzd' => $amount,
                'new_status' => $newStatus,
            ],
        );

        return $payment;
    }
}

