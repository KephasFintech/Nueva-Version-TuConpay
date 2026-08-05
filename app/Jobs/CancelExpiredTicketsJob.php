<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\ExchangeTicket;
use App\Services\TicketStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelExpiredTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TicketStateMachine $stateMachine): void
    {
        $tickets = ExchangeTicket::overdue()->get();

        foreach ($tickets as $ticket) {
            try {
                $stateMachine->transition(
                    $ticket,
                    TicketStatus::CANCELLED_BY_TIMEOUT,
                    'SLA vencido. Ticket cancelado automáticamente por el sistema.'
                );

                Log::info("Ticket {$ticket->code} cancelado por SLA.");

                // Broadcast del evento en tiempo real
                event(new \App\Events\TicketCancelledByTimeout($ticket));

            } catch (\Exception $e) {
                Log::error("Error cancelando ticket {$ticket->code} por SLA: " . $e->getMessage());
            }
        }
    }
}
