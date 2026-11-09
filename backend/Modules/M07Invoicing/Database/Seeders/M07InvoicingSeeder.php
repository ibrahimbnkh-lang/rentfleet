<?php

declare(strict_types=1);

namespace Modules\M07Invoicing\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\M00Core\Models\Branch;
use Modules\M02Customers\Models\Customer;
use Modules\M05Contracts\Models\Contract;
use Modules\M07Invoicing\Models\Invoice;
use Modules\M07Invoicing\Models\Payment;
use Modules\M07Invoicing\Services\InvoiceService;

class M07InvoicingSeeder extends Seeder
{
    public function run(InvoiceService $invoiceService): void
    {
        $admin = User::first();
        $branchAlger = Branch::where('code', 'ALG-01')->first() ?? Branch::first();
        $customers = Customer::all();
        $contracts = Contract::all();

        if ($customers->isEmpty()) {
            return;
        }

        // 1. Generate Invoice from completed contract CTR-2026-0002 (Fully Paid)
        $contract2 = Contract::where('contract_number', 'CTR-2026-0002')->first();
        if ($contract2) {
            $inv1 = $invoiceService->generateFromContract($contract2, $admin, 'cheque');
            $invoiceService->recordPayment($inv1, [
                'amount_dzd' => $inv1->total_ttc_dzd,
                'payment_method' => 'cheque',
                'payment_date' => Carbon::now()->subDays(10),
                'reference_details' => 'Chèque BADR N° 77124901',
                'notes' => 'Règlement intégral de la location par chèque de banque.',
            ], $admin);
        }

        // 2. Generate Invoice from active contract CTR-2026-0001 (Partially Paid with Cash Acompte)
        $contract1 = Contract::where('contract_number', 'CTR-2026-0001')->first();
        if ($contract1) {
            $inv2 = $invoiceService->generateFromContract($contract1, $admin, 'cash');
            // Advance payment of 15,000 DZD in cash
            $invoiceService->recordPayment($inv2, [
                'amount_dzd' => 15000.00,
                'payment_method' => 'cash',
                'payment_date' => Carbon::now()->subDays(2),
                'reference_details' => 'Reçu Caisse N° REC-2026-012',
                'notes' => 'Acompte versé en espèces lors de la prise en charge du véhicule.',
            ], $admin);
        }

        // 3. Corporate Direct Invoice for Company customer
        $companyCustomer = Customer::where('type', 'company')->first() ?? $customers->first();
        $subtotalHt = 80000.00;
        $taxes = $invoiceService->calculateTaxes($subtotalHt, 19.00, 'bank_transfer');

        $inv3 = Invoice::firstOrCreate(
            ['invoice_number' => 'FAC-2026-0003'],
            [
                'customer_id' => $companyCustomer->id,
                'branch_id' => $branchAlger->id,
                'created_by' => $admin?->id,
                'status' => Invoice::STATUS_ISSUED,
                'type' => 'standard',
                'issue_date' => Carbon::now()->subDays(5)->toDateString(),
                'due_date' => Carbon::now()->addDays(25)->toDateString(),
                'subtotal_ht_dzd' => $taxes['subtotal_ht'],
                'vat_rate' => $taxes['vat_rate'],
                'vat_amount_dzd' => $taxes['vat_amount'],
                'stamp_duty_dzd' => $taxes['stamp_duty'],
                'total_ttc_dzd' => $taxes['total_ttc'],
                'paid_amount_dzd' => 0.0,
                'remaining_amount_dzd' => $taxes['total_ttc'],
                'customer_name' => $companyCustomer->full_name,
                'customer_nif' => $companyCustomer->company_tax_id ?? '001916012345678',
                'customer_rc' => '16/00-9876543A20',
                'customer_address' => 'Zone Industrielle Oued Smar, Alger',
                'payment_method' => 'bank_transfer',
                'payment_terms' => 'Virement bancaire sous 30 jours fin de mois',
                'notes' => 'Mise à disposition flotte entreprise - Convention cadre mensuelle.',
            ]
        );

        $inv3->items()->firstOrCreate(
            ['description' => 'Mise à disposition flotte mensuelle Véhicules Berlines (Pack Entreprise)'],
            [
                'quantity' => 1.00,
                'unit_price_ht_dzd' => $subtotalHt,
                'total_ht_dzd' => $subtotalHt,
                'vat_rate' => 19.00,
            ]
        );
    }
}

