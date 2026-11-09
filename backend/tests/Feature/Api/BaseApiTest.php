<?php

declare(strict_types=1);

it('returns 200 from health endpoint', function () {
    $response = $this->getJson('/api/v1/health');

    $response
        ->assertStatus(200)
        ->assertJsonStructure(['status', 'timestamp', 'timezone'])
        ->assertJson(['status' => 'ok']);
});

it('returns 404 JSON for unknown API routes', function () {
    $response = $this->getJson('/api/v1/this-route-does-not-exist');

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found');
});

it('returns 401 for protected routes without token', function () {
    $response = $this->getJson('/api/v1/core/users');

    $response->assertStatus(401);
});
