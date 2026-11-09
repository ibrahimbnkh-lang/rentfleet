<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M02Customers\Database\Seeders\M02CustomersSeeder;
use Modules\M02Customers\Models\Customer;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M02CustomersSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
});

it('lists all customers with pagination and filtering', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/customers');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['items', 'meta']]);

    // 5 seeded customers
    expect($response->json('data.meta.total'))->toBe(5);

    // Filter by type: company
    $companyResponse = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/customers?type=company');
    $companyResponse->assertStatus(200);
    expect($companyResponse->json('data.items'))->toHaveCount(1);
    expect($companyResponse->json('data.items.0.company_name'))->toBe('SARL NUMIDIA LOGISTICS');
});

it('creates a new customer with Algerian NIN and driver license', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/customers', [
        'type' => 'individual',
        'first_name' => 'Farid',
        'last_name' => 'Mansouri',
        'phone' => '+213 551 99 88 77',
        'email' => 'farid.mansouri@test.dz',
        'national_id_number' => '116900112233445566',
        'driver_license_number' => '16/2016/55443',
        'driver_license_issued_at' => '2016-05-15',
        'driver_license_wilaya' => '16',
        'birth_date' => '1990-08-20',
        'wilaya_code' => '16',
        'city' => 'Kouba',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.first_name', 'Farid')
        ->assertJsonPath('data.kyc_status', 'pending')
        ->assertJsonPath('data.is_blacklisted', false);

    $this->assertDatabaseHas('customers', [
        'phone' => '+213 551 99 88 77',
        'national_id_number' => '116900112233445566',
    ]);
});

it('shows customer details with automated rental eligibility verification', function () {
    $amine = Customer::where('phone', '+213 550 11 22 33')->first();

    $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/customers/{$amine->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['customer', 'eligibility']]);

    $eligibility = $response->json('data.eligibility');
    expect($eligibility['is_eligible'])->toBeTrue();
    expect($eligibility['reasons'])->toBeEmpty();
    expect($eligibility['criteria']['kyc_status'])->toBe('verified');
});

it('verifies and rejects customer KYC workflow', function () {
    $meriem = Customer::where('phone', '+213 770 99 88 77')->first();
    expect($meriem->kyc_status)->toBe('pending');

    // Reject KYC
    $rejectResponse = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/customers/{$meriem->id}/kyc/reject", [
        'reason' => 'Photo du permis de conduire floue et illisible.',
    ]);

    $rejectResponse->assertStatus(200)
        ->assertJsonPath('data.customer.kyc_status', 'rejected')
        ->assertJsonPath('data.customer.kyc_rejection_reason', 'Photo du permis de conduire floue et illisible.');

    // Verify KYC
    $verifyResponse = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/customers/{$meriem->id}/kyc/verify");

    $verifyResponse->assertStatus(200)
        ->assertJsonPath('data.customer.kyc_status', 'verified');

    expect($meriem->fresh()->kyc_status)->toBe('verified');
    expect($meriem->fresh()->kyc_rejection_reason)->toBeNull();
});

it('manages blacklist status and reflects immediately in rental eligibility', function () {
    $amine = Customer::where('phone', '+213 550 11 22 33')->first();
    expect($amine->is_blacklisted)->toBeFalse();

    // Add to blacklist
    $addResponse = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/customers/{$amine->id}/blacklist", [
        'reason' => 'impayes',
        'severity' => 'high',
        'incident_date' => '2026-10-15',
        'notes' => 'Chèque de caution sans provision après restitution du véhicule.',
    ]);

    $addResponse->assertStatus(201);
    expect($amine->fresh()->is_blacklisted)->toBeTrue();

    // Eligibility check must now fail
    $eligibilityResponse = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/customers/{$amine->id}/eligible");
    $eligibilityResponse->assertStatus(200)
        ->assertJsonPath('data.is_eligible', false);
    expect($eligibilityResponse->json('data.reasons.0'))->toContain('liste noire');

    // Remove from blacklist
    $removeResponse = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/customers/{$amine->id}/blacklist");
    $removeResponse->assertStatus(200);
    expect($amine->fresh()->is_blacklisted)->toBeFalse();

    // Eligibility check is now true
    $eligibilityResponse2 = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/customers/{$amine->id}/eligible");
    expect($eligibilityResponse2->json('data.is_eligible'))->toBeTrue();
});

it('rejects rental eligibility for drivers with insufficient license seniority', function () {
    $youngCustomer = Customer::create([
        'type' => 'individual',
        'first_name' => 'Walid',
        'last_name' => 'Dahmani',
        'phone' => '+213 554 00 11 22',
        'national_id_number' => '116999888777666555',
        'driver_license_number' => '16/2026/00123',
        'driver_license_issued_at' => Carbon::now()->subMonths(6)->toDateString(), // 6 months < 2 years
        'birth_date' => Carbon::now()->subYears(24)->toDateString(), // age 24 >= 21
        'kyc_status' => 'verified',
        'is_blacklisted' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/customers/{$youngCustomer->id}/eligible");

    $response->assertStatus(200)
        ->assertJsonPath('data.is_eligible', false);

    $reasons = $response->json('data.reasons');
    expect($reasons)->toHaveCount(1);
    expect($reasons[0])->toContain('Ancienneté du permis insuffisante');
});
