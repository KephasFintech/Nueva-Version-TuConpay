<?php

namespace App\Notifications;

use App\Models\ExchangeTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExchangeTicket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket Cerrado: {$this->ticket->code}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu ticket de cambio {$this->ticket->code} ha sido completado y cerrado exitosamente.")
            ->line("Monto Entregado: " . number_format($this->ticket->amount_to_deliver, 2) . " " . $this->ticket->currency_to)
            ->action('Ver Detalle', url("/tickets/{$this->ticket->id}"))
            ->line('Gracias por confiar en TuConpay.');
    }
}
