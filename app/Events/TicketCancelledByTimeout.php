<?php

namespace App\Events;

use App\Models\ExchangeTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCancelledByTimeout implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExchangeTicket $ticket
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('office.tickets'),
            new Channel('ticket.' . $this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.cancelled.timeout';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'code'      => $this->ticket->code,
            'message'   => "Ticket {$this->ticket->code} cancelado por vencimiento de SLA.",
        ];
    }
}
