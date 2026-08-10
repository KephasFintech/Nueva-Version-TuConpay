<?php

namespace Tests\Feature\Security;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\ExchangeTicket;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FourAgentsLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_cannot_settle_without_four_agents()
    {
        $analyst = User::factory()->create();
        $analyst->assignRole(UserRole::DATA_ANALYST->value);

        $client = User::factory()->create();
        
        // Ticket missing courier and broker
        $ticket = ExchangeTicket::create([
            'code' => 'TC-TEST-003',
            'client_id' => $client->id,
            'external_admin_id' => $analyst->id,
            'provider_id' => $analyst->id,
            // Missing broker_id and courier_id
            'currency_from' => 'USD',
            'currency_to' => 'EUR',
            'amount_requested' => 100,
            'status' => TicketStatus::DELIVERED->value,
        ]);

        $response = $this->actingAs($analyst, 'api')->postJson("/api/v1/exchange-tickets/{$ticket->id}/close");

        // The middleware should block it with 422
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Acción Bloqueada: No se puede liquidar el ticket sin los agentes obligatorios (Corredor, Motorizado, y si es flujo externo: Admin A1 y Proveedor P2).']);
    }
}
