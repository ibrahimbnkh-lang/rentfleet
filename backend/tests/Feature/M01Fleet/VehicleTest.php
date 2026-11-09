<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M00Core\Models\Branch;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleCategory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M01FleetSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
    $this->oranManager = User::where('email', 'manager.oran@rentfleet.dz')->first();
});

it('lists all vehicles for super admin with pagination', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/fleet/vehicles');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['data', 'meta', 'links']])
        ->assertJsonPath('data.meta.total', 10);
});

it('filters vehicles by branch for agency manager', function () {
    $response = $this->actingAs($this->oranManager, 'sanctum')->getJson('/api/v1/fleet/vehicles');

    $response->assertStatus(200);
    $items = $response->json('data.data');

    // Oran has 3 seeded vehicles
    expect($items)->toHaveCount(3);
    foreach ($items as $item) {
        expect($item['branch_id'])->toBe($this->oranManager->branch_id);
    }
});

it('creates a new vehicle with initial equipment checklist and audit log', function () {
    $branch = Branch::first();
    $category = VehicleCategory::first();

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/fleet/vehicles', [
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'brand' => 'Kia',
        'model' => 'Picanto GT Line',
        'year' => 2024,
        'registration_plate' => '09999-124-16',
        'vin' => 'KNABA000099999999',
        'color' => 'Gris Titane',
        'fuel_type' => 'essence',
        'transmission' => 'automatique',
        'seats' => 5,
        'doors' => 5,
        'mileage' => 5000,
        'daily_price_dzd' => 5500.00,
        'deposit_dzd' => 50000.00,
        'status' => 'available',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.registration_plate', '09999-124-16')
        ->assertJsonPath('data.brand', 'Kia');

    $this->assertDatabaseHas('vehicles', ['registration_plate' => '09999-124-16']);
    $this->assertDatabaseHas('vehicle_equipment', ['name' => 'Roue de secours']);
    $this->assertDatabaseHas('audit_logs', ['module' => 'M01Fleet', 'action' => 'vehicle.created']);
});

it('shows vehicle details with regulatory compliance status', function () {
    $vehicle = Vehicle::first();

    $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/fleet/vehicles/{$vehicle->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'vehicle' => ['id', 'brand', 'model', 'registration_plate', 'category', 'branch'],
                'compliance' => ['is_compliant', 'status', 'documents'],
            ],
        ]);
});

it('updates vehicle status and mileage', function () {
    $vehicle = Vehicle::first();

    $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/fleet/vehicles/{$vehicle->id}", [
        'status' => 'maintenance',
        'mileage' => 45000,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'maintenance')
        ->assertJsonPath('data.mileage', 45000);

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'status' => 'maintenance',
        'mileage' => 45000,
    ]);
});

it('retrieves expiring documents across the fleet', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/fleet/expiring-documents?days=30');

    $response->assertStatus(200);
    $items = $response->json('data');

    // Should include the seeded vehicle with expiring document in 12 days
    expect($items)->not->toBeEmpty();
});
