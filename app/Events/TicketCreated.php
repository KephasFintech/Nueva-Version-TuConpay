<?php

namespace App\Events;

use App\Models\ExchangeTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExchangeTicket $ticket
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('office.tickets'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'code'      => $this->ticket->code,
            'status'    => $this->ticket->status->value,
            'label'     => $this->ticket->status->label(),
        ];
    }
}
