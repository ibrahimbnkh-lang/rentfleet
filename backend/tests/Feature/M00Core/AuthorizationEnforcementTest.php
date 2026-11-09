<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
    $this->seed(M01FleetSeeder::class);
});

it('blocks users without any roles or permissions from accessing protected endpoints', function () {
    // Create an authenticated user with NO roles and NO permissions
    $userWithoutRole = User::factory()->create([
        'email' => 'norole@rentfleet.dz',
        'is_active' => true,
    ]);

    // Attempt to access vehicles API (requires fleet.vehicle.viewAny)
    $response = $this->actingAs($userWithoutRole, 'sanctum')->getJson('/api/v1/fleet/vehicles');

    $response->assertStatus(403)
        ->assertJson([
            'error' => [
                'code' => 'forbidden',
            ],
        ]);

    // Attempt to access core users API (requires core.user.viewAny)
    $responseUsers = $this->actingAs($userWithoutRole, 'sanctum')->getJson('/api/v1/core/users');

    $responseUsers->assertStatus(403);
});

it('allows Super Admin full access without explicit per-route role checks', function () {
    $admin = User::where('email', 'admin@rentfleet.dz')->first();

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/fleet/vehicles');
    $response->assertStatus(200);

    $responseUsers = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/core/users');
    $responseUsers->assertStatus(200);
});

it('prevents agent from accessing admin-only user management or role management', function () {
    $agent = User::where('email', 'agent.alger@rentfleet.dz')->first();

    // Agent has fleet.vehicle.viewAny, so should be 200
    $responseFleet = $this->actingAs($agent, 'sanctum')->getJson('/api/v1/fleet/vehicles');
    $responseFleet->assertStatus(200);

    // Agent does NOT have core.user.viewAny or core.role.viewAny, so should be 403
    $responseUsers = $this->actingAs($agent, 'sanctum')->getJson('/api/v1/core/users');
    $responseUsers->assertStatus(403);

    $responseRoles = $this->actingAs($agent, 'sanctum')->getJson('/api/v1/core/roles');
    $responseRoles->assertStatus(403);
});

it('immediately revokes access when roles are deleted/synced from user', function () {
    $agent = User::where('email', 'agent.alger@rentfleet.dz')->first();

    // First, verify agent can view vehicles
    $response1 = $this->actingAs($agent, 'sanctum')->getJson('/api/v1/fleet/vehicles');
    $response1->assertStatus(200);

    // Remove all roles from the agent
    $agent->syncRoles([]);
    $agent->refresh();

    // Verify agent is immediately blocked with 403
    $response2 = $this->actingAs($agent, 'sanctum')->getJson('/api/v1/fleet/vehicles');
    $response2->assertStatus(403)
        ->assertJson([
            'error' => [
                'code' => 'forbidden',
            ],
        ]);
});

