<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M00Core\Models\Branch;
use Modules\M00Core\Services\LicenseService;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;
use Modules\M01Fleet\Models\Vehicle;
use Modules\M01Fleet\Models\VehicleCategory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M01FleetSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
    $this->licenseService = app(LicenseService::class);
});

it('strictly blocks creating a branch when max_branches license quota is reached', function () {
    // Issue and activate a license with exactly 4 branches maximum
    $tokenData = $this->licenseService->issueLicense(
        clientName: 'Test Agency',
        clientEmail: 'test@rentfleet.dz',
        modules: ['M00Core', 'M01Fleet'],
        days: 30,
        maxVehicles: 50,
        maxBranches: 4
    );
    $this->licenseService->activate($tokenData['token']);

    // Seed has 3 branches (Alger, Oran, Constantine)
    expect(Branch::count())->toBe(3);

    // 4th branch: Should succeed (3 < 4)
    $response4 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/core/branches', [
        'name' => 'Agence Annaba',
        'code' => 'ANN-01',
        'wilaya_code' => '23',
        'city' => 'Annaba',
        'address' => 'Boulevard de l\'ALN',
        'phone' => '+213 38 00 00 00',
        'is_active' => true,
    ]);

    $response4->assertStatus(201);
    expect(Branch::count())->toBe(4);

    // 5th branch: Must FAIL with 403 Forbidden because quota is 4!
    $response5 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/core/branches', [
        'name' => 'Agence Sétif',
        'code' => 'STF-01',
        'wilaya_code' => '19',
        'city' => 'Sétif',
        'address' => 'Centre Ville',
        'phone' => '+213 36 00 00 00',
        'is_active' => true,
    ]);

    $response5->assertStatus(403)
        ->assertJson([
            'error' => [
                'code' => 'license_quota_exceeded',
            ],
        ]);

    expect(Branch::count())->toBe(4);
});

it('strictly blocks creating a vehicle when max_vehicles license quota is reached', function () {
    $currentVehicles = Vehicle::count();

    // Issue and activate a license with maxVehicles = current count
    $tokenData = $this->licenseService->issueLicense(
        clientName: 'Test Agency Fleet',
        clientEmail: 'test@rentfleet.dz',
        modules: ['M00Core', 'M01Fleet'],
        days: 30,
        maxVehicles: $currentVehicles,
        maxBranches: 10
    );
    $this->licenseService->activate($tokenData['token']);

    $branch = Branch::first();
    $category = VehicleCategory::first();

    // Attempting to create one more vehicle must return 403 Forbidden
    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/fleet/vehicles', [
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'brand' => 'Dacia',
        'model' => 'Sandero Stepway',
        'year' => 2024,
        'registration_plate' => '99999-124-16',
        'vin' => 'VF1TESTVIN9999999',
        'fuel_type' => 'essence',
        'transmission' => 'manuelle',
        'mileage' => 1000,
        'status' => 'available',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'error' => [
                'code' => 'license_quota_exceeded',
            ],
        ]);
});

