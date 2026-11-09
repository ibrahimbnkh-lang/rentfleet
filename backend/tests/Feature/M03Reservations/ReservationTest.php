<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M02Customers\Database\Seeders\M02CustomersSeeder;
use Modules\M02Customers\Models\Customer;
use Modules\M03Reservations\Database\Seeders\M03ReservationsSeeder;
use Modules\M03Reservations\Models\Reservation;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M01FleetSeeder::class);
    $this->seed(M02CustomersSeeder::class);
    $this->seed(M03ReservationsSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
});

it('lists all reservations with metrics and pagination', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/reservations');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['pagination', 'metrics']]);

    expect($response->json('meta.pagination.total'))->toBeGreaterThanOrEqual(1);
    expect($response->json('meta.metrics'))->toHaveKeys([
        'total_count',
        'confirmed_count',
        'in_progress_count',
        'pending_count',
        'total_revenue_dzd',
    ]);
});

it('creates a new reservation and generates reference code', function () {
    $customer = Customer::first();
    $vehicle = Vehicle::where('status', 'available')->first() ?? Vehicle::first();
    $pickup = Carbon::now()->addDays(20)->format('Y-m-d H:i:s');
    $return = Carbon::now()->addDays(25)->format('Y-m-d H:i:s');

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/reservations', [
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'branch_id' => $vehicle->branch_id,
        'return_branch_id' => $vehicle->branch_id,
        'pickup_datetime' => $pickup,
        'return_datetime' => $return,
        'deposit_amount_dzd' => 50000,
        'notes' => 'Test reservation unit run',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.reference_code'))->toStartWith('RES-');
    expect($response->json('data.status'))->toBe(Reservation::STATUS_CONFIRMED);
});

it('checks vehicle availability and detects conflicts', function () {
    $reservation = Reservation::first();
    $vehicle = $reservation->vehicle;

    // Overlapping dates with existing reservation
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/reservations/availability?' . http_build_query([
        'vehicle_id' => $vehicle->id,
        'pickup_datetime' => $reservation->pickup_datetime->format('Y-m-d H:i:s'),
        'return_datetime' => $reservation->return_datetime->format('Y-m-d H:i:s'),
    ]));

    $response->assertStatus(200);
    expect($response->json('data.available'))->toBeFalse();
    expect($response->json('data.conflicts'))->not->toBeEmpty();
});

it('provides planning timeline events for calendar view', function () {
    $from = Carbon::now()->subMonth()->format('Y-m-d');
    $to = Carbon::now()->addMonth()->format('Y-m-d');

    $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/reservations/timeline?start_date={$from}&end_date={$to}");

    $response->assertStatus(200);
    expect($response->json('data'))->toBeArray();
});
