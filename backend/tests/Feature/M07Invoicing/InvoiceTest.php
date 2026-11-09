<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;
use Modules\M02Customers\Database\Seeders\M02CustomersSeeder;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Database\Seeders\M03ReservationsSeeder;
use Modules\M05Contracts\Database\Seeders\M05ContractsSeeder;
use Modules\M05Contracts\Models\Contract;
use Modules\M07Invoicing\Database\Seeders\M07InvoicingSeeder;
use Modules\M07Invoicing\Models\Invoice;
use Modules\M07Invoicing\Services\InvoiceService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M01FleetSeeder::class);
    $this->seed(M02CustomersSeeder::class);
    $this->seed(M03ReservationsSeeder::class);
    $this->seed(M05ContractsSeeder::class);
    $this->seed(M07InvoicingSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
    $this->service = app(InvoiceService::class);
});

it('lists all invoices with Algerian fiscal metrics', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/invoicing/invoices');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['pagination', 'metrics']]);

    expect($response->json('meta.pagination.total'))->toBeGreaterThanOrEqual(1);
    expect($response->json('meta.metrics'))->toHaveKeys([
        'total_billed_dzd',
        'total_paid_dzd',
        'total_due_dzd',
        'paid_count',
        'unpaid_count',
    ]);
});

it('calculates Algerian tax rules correctly: 19% VAT and cash stamp duty', function () {
    // 100,000 DZD HT with cash payment
    $resultCash = $this->service->calculateTaxes(100000, 19.0, 'cash');

    expect($resultCash['subtotal_ht'])->toBe(100000.0);
    expect($resultCash['vat_amount'])->toBe(19000.0); // 19%
    // Base TTC = 119,000. Stamp duty is 1% = 1190 DZD (between min 20 and max 2500)
    expect($resultCash['stamp_duty'])->toBe(1190.0);
    expect($resultCash['total_ttc'])->toBe(120190.0);

    // Same amount with cheque / non-cash: stamp duty must be 0
    $resultCheque = $this->service->calculateTaxes(100000, 19.0, 'cheque');
    expect($resultCheque['stamp_duty'])->toBe(0.0);
    expect($resultCheque['total_ttc'])->toBe(119000.0);
});

it('creates a manual invoice with line items', function () {
    $customer = Customer::first();
    $branch = Branch::first();

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/invoicing/invoices', [
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'issue_date' => Carbon::now()->format('Y-m-d'),
        'due_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
        'payment_method' => 'bank_transfer',
        'items' => [
            [
                'description' => 'Location Véhicule - Forfait Semaine',
                'quantity' => 1,
                'unit_price_ht_dzd' => 50000,
            ],
            [
                'description' => 'Frais Nettoyage Approfondi',
                'quantity' => 1,
                'unit_price_ht_dzd' => 3000,
            ],
        ],
    ]);

    $response->assertStatus(201);
    expect($response->json('data.invoice_number'))->toStartWith('FAC-');
    expect((float) $response->json('data.subtotal_ht_dzd'))->toEqual(53000.0);
    expect((float) $response->json('data.vat_amount_dzd'))->toEqual(10070.0); // 19% of 53,000
    expect((float) $response->json('data.stamp_duty_dzd'))->toEqual(0.0);
    expect((float) $response->json('data.total_ttc_dzd'))->toEqual(63070.0);
});

it('generates invoice directly from contract', function () {
    $contract = Contract::first();

    $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/invoicing/invoices/from-contract/{$contract->id}", [
        'payment_method' => 'cheque',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.contract_id'))->toBe($contract->id);
    expect($response->json('data.total_ttc_dzd'))->toBeGreaterThan(0);
});

it('records payment and updates remaining invoice balance', function () {
    $invoice = Invoice::where('remaining_amount_dzd', '>', 5000)->first() ?? Invoice::first();
    $initialRemaining = (float) $invoice->remaining_amount_dzd;
    $payAmount = 5000.0;

    $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/invoicing/invoices/{$invoice->id}/payments", [
        'amount_dzd' => $payAmount,
        'payment_method' => 'cash',
        'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
        'reference_details' => 'Encaissement comptoir Caisse #1',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.payment.payment_reference'))->toStartWith('PAY-');
    expect((float) $response->json('data.invoice.remaining_amount_dzd'))->toBe(round($initialRemaining - $payAmount, 2));
});

it('lists payments cash book and calculates breakdown metrics', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/invoicing/payments');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['pagination', 'metrics']]);

    expect($response->json('meta.metrics'))->toHaveKeys([
        'total_collected_dzd',
        'cash_total_dzd',
        'cheque_total_dzd',
        'transfer_total_dzd',
        'cib_total_dzd',
    ]);
});

