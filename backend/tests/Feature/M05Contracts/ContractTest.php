<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;
use Modules\M02Customers\Database\Seeders\M02CustomersSeeder;
use Modules\M03Reservations\Database\Seeders\M03ReservationsSeeder;
use Modules\M03Reservations\Models\Reservation;
use Modules\M05Contracts\Database\Seeders\M05ContractsSeeder;
use Modules\M05Contracts\Models\Contract;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M01FleetSeeder::class);
    $this->seed(M02CustomersSeeder::class);
    $this->seed(M03ReservationsSeeder::class);
    $this->seed(M05ContractsSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
});

it('lists all contracts with pagination and metrics', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/contracts');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['pagination', 'metrics']]);

    expect($response->json('meta.pagination.total'))->toBeGreaterThanOrEqual(1);
    expect($response->json('meta.metrics'))->toHaveKeys([
        'total_contracts',
        'active_contracts',
        'completed_contracts',
        'draft_contracts',
    ]);
});

it('creates contract from confirmed reservation', function () {
    $reservation = Reservation::where('status', Reservation::STATUS_CONFIRMED)
        ->whereDoesntHave('contract')
        ->first();

    if (! $reservation) {
        $reservation = Reservation::first();
    }

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/contracts/from-reservation/' . $reservation->id);

    $response->assertStatus(201);
    expect($response->json('data.contract_number'))->toStartWith('CTR-');
    expect($response->json('data.customer_id'))->toBe($reservation->customer_id);
});

it('records e-signature and updates contract status', function () {
    $contract = Contract::where('status', 'draft')->first() ?? Contract::first();

    $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/sign", [
        'client_signature' => 'data:image/svg+xml;utf8,<svg viewBox="0 0 100 30"><path d="M10 15 Q 30 5 50 15 T 90 15" stroke="black"/></svg>',
    ]);

    $response->assertStatus(200);
    expect($response->json('data.signed_at'))->not->toBeNull();
    expect($response->json('data.status'))->toBe(Contract::STATUS_ACTIVE);
});

it('records vehicle checkout (restitution) and closes contract', function () {
    $contract = Contract::where('status', 'active')->first() ?? Contract::first();

    $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/checkout", [
        'end_mileage' => ($contract->start_mileage ?? 10000) + 300,
        'end_fuel_level' => '100%',
        'fuel_fee_dzd' => 0,
        'damages_fee_dzd' => 0,
        'end_notes' => 'Véhicule restitué en parfait état.',
    ]);

    $response->assertStatus(200);
    expect($response->json('data.status'))->toBe('completed');
    expect($response->json('data.actual_return_datetime'))->not->toBeNull();
});
