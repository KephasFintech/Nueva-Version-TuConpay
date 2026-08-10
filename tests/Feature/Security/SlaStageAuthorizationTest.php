<?php

namespace Tests\Feature\Security;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\ExchangeTicket;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaStageAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_atc_can_create_ticket_but_cannot_verify_payment()
    {
        $atc = User::factory()->create();
        $atc->assignRole(UserRole::ATC->value);
        
        $client = User::factory()->create();

        // 1. Can create
        $responseCreate = $this->actingAs($atc, 'api')->postJson('/api/v1/exchange-tickets', [
            'client_id' => $client->id,
            'currency_from' => 'USDT', // Assuming USDT and EUR are valid based on PRD config
            'currency_to' => 'EUR',
            'amount_requested' => 100,
        ]);

        // We assert it doesn't return 403, it might return 422 if validation fails or 201 if success
        $this->assertNotEquals(403, $responseCreate->status());

        // 2. Cannot verify payment (Policy check)
        $ticket = ExchangeTicket::create([
            'code' => 'TC-TEST-002',
            'client_id' => $client->id,
            'currency_from' => 'USD',
            'currency_to' => 'EUR',
            'amount_requested' => 100,
            'status' => TicketStatus::WAITING_PAYMENT->value,
        ]);

        $policy = new \App\Policies\ExchangeTicketPolicy();
        $this->assertFalse($policy->verifyPayment($atc, $ticket));
    }
}
