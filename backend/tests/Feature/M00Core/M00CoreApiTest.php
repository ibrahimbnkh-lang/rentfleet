<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M00Core\Models\Branch;
use Modules\M00Core\Services\LicenseService;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->admin = User::where('email', 'admin@rentfleet.dz')->first();
});

it('lists branches and supports filtering by wilaya', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/core/branches?wilaya_code=16');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['data', 'meta', 'links']])
        ->assertJsonPath('data.data.0.wilaya_code', '16');
});

it('creates a new branch', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/core/branches', [
        'name' => 'Agence Annaba Plage',
        'code' => 'ANB-01',
        'wilaya_code' => '23',
        'city' => 'Annaba',
        'address' => '02 Boulevard du 1er Novembre',
        'phone' => '+213 38 45 12 34',
        'email' => 'annaba@rentfleet.dz',
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'ANB-01')
        ->assertJsonPath('data.city', 'Annaba');

    $this->assertDatabaseHas('branches', ['code' => 'ANB-01']);
});

it('creates a new user and assigns roles', function () {
    $branch = Branch::first();

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/core/users', [
        'name' => 'Nadir Belkacem',
        'email' => 'nadir.belkacem@rentfleet.dz',
        'password' => 'secret12345',
        'phone' => '+213 550 99 88 77',
        'branch_id' => $branch->id,
        'roles' => ['Agent'],
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.email', 'nadir.belkacem@rentfleet.dz');

    $this->assertDatabaseHas('users', ['email' => 'nadir.belkacem@rentfleet.dz']);
});

it('prevents user from deleting themselves', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->deleteJson('/api/v1/core/users/' . $this->admin->id);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'cannot_delete_self');
});

it('lists roles with assigned permissions', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/core/roles');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]]);
});

it('records audit log entries on branch creation', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/core/branches', [
        'name' => 'Agence Tlemcen',
        'code' => 'TLM-01',
        'wilaya_code' => '13',
        'city' => 'Tlemcen',
        'address' => 'Centre Ville',
        'phone' => '+213 43 20 10 00',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/core/audit-log?action=branch.created');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['data', 'meta', 'links']]);

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'M00Core',
        'action' => 'branch.created',
    ]);
});

it('can fetch and update system settings', function () {
    $getResponse = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/core/settings');
    $getResponse->assertStatus(200)
        ->assertJsonPath('data.currency', 'DZD');

    $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson('/api/v1/core/settings', [
        'settings' => [
            ['key' => 'currency', 'value' => 'DZD'],
            ['key' => 'company_name', 'value' => 'RentFleet Algeria updated'],
        ],
    ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.company_name', 'RentFleet Algeria updated');
});

it('shows active license information', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/core/license');

    $response->assertStatus(200)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.client_email', 'contact@rentfleet.dz');
});

it('activates a freshly issued license token', function () {
    $licenseService = app(LicenseService::class);
    $issued = $licenseService->issueLicense('New Client DZ', 'new@rentfleet.dz', ['M00Core', 'M01Fleet'], 180);

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/core/license/activate', [
        'license_token' => $issued['token'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.license.client_name', 'New Client DZ');
});
