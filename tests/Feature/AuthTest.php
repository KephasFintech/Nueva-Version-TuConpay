<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->artisan('migrate:fresh --seed');
});

it('can login with correct credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@tuconpay.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
            ]
        ]);
});

it('cannot login with wrong credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@tuconpay.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

it('can get current user profile with roles', function () {
    $token = auth('api')->attempt(['email' => 'admin@tuconpay.com', 'password' => 'password123']);

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.email', 'admin@tuconpay.com')
        ->assertJsonPath('data.roles.0', UserRole::SUPER_ADMIN->value);
});
