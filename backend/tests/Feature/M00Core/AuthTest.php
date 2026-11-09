<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M00Core\Services\TwoFactorService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(M00CoreSeeder::class);
});

it('can login with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@rentfleet.dz',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
            ],
        ]);
});

it('rejects login with invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@rentfleet.dz',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'invalid_credentials');
});

it('rejects disabled accounts', function () {
    $user = User::where('email', 'admin@rentfleet.dz')->first();
    $user->update(['is_active' => false]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@rentfleet.dz',
        'password' => 'password',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'account_disabled');
});

it('can fetch current user profile via auth/me', function () {
    $user = User::where('email', 'admin@rentfleet.dz')->first();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.email', 'admin@rentfleet.dz')
        ->assertJsonPath('data.name', 'Administrateur Principal');
});

it('can logout and revoke token', function () {
    $user = User::where('email', 'admin@rentfleet.dz')->first();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJsonPath('data.message', 'Déconnexion réussie.');
});

it('requires 2fa code when 2fa is enabled', function () {
    $user = User::where('email', 'admin@rentfleet.dz')->first();
    $t2fa = app(TwoFactorService::class);
    $secret = $t2fa->generateSecret();

    $user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    // First attempt without code returns requires_2fa challenge
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@rentfleet.dz',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.requires_2fa', true);
});
