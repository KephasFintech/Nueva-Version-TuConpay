<?php

namespace App\Events;

use App\Models\ExchangeTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaAlertTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExchangeTicket $ticket
    ) {}

    public function broadcastOn(): array
    {
        // Notificar al ATC responsable en su canal privado
        return [
            new PrivateChannel('agent.' . $this->ticket->atc_user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sla.alert';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'code'      => $this->ticket->code,
            'message'   => "El ticket {$this->ticket->code} está próximo a vencer.",
        ];
    }
}
