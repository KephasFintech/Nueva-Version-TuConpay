<?php

namespace App\Notifications;

use App\Models\ExchangeTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ExchangeTicket $ticket
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerta SLA: Ticket {$this->ticket->code}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El ticket {$this->ticket->code} está próximo a vencer.")
            ->line("Por favor, asegúrate de procesarlo antes de que se cancele automáticamente.")
            ->action('Ver Ticket', url("/tickets/{$this->ticket->id}"))
            ->line('Gracias por usar nuestro sistema.');
    }
}
