<?php

namespace Tests\Feature\Security;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\ExchangeTicket;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthorizedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_client_cannot_create_user()
    {
        $client = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);

        $response = $this->actingAs($client, 'api')->postJson('/api/v1/users', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'Password123!',
            'role' => UserRole::ATC->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_courier_cannot_settle_ticket()
    {
        $courier = User::factory()->create();
        $courier->assignRole(UserRole::COURIER->value);

        $client = User::factory()->create();
        $ticket = ExchangeTicket::create([
            'code' => 'TC-TEST-001',
            'client_id' => $client->id,
            'currency_from' => 'USD',
            'currency_to' => 'EUR',
            'amount_requested' => 100,
            'status' => TicketStatus::DELIVERED->value,
        ]);

        $response = $this->actingAs($courier, 'api')->postJson("/api/v1/exchange-tickets/{$ticket->id}/close");

        $response->assertStatus(403);
    }
}
