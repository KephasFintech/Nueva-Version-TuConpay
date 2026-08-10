<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\ExchangeTicket;
use App\Notifications\SlaWarningNotification;
use App\Services\SlaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSlaAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SlaService $slaService): void
    {
        // 1. SLA Cliente (90 min) - Alerta Temprana
        $tickets = ExchangeTicket::awaitingSlaAlert()->get();

        foreach ($tickets as $ticket) {
            // Marcar como alertado
            $ticket->update(['sla_alerted_at' => now()]);

            // Alerta al cliente (o al ATC que lo creó)
            // Aquí notificamos al ATC para seguimiento
            if (config('exchange.notifications_enabled', false) && $ticket->atc) {
                $ticket->atc->notify(new SlaWarningNotification($ticket));
            }

            Log::info("SLA Alert disparada para el ticket {$ticket->code}");
            
            // Broadcast del evento en tiempo real (Reverb)
            event(new \App\Events\SlaAlertTriggered($ticket));
        }

        // 2. SLA Global (180 min) - Overdue Alert
        $globalOverdueTickets = ExchangeTicket::globalOverdue()->get();
        foreach ($globalOverdueTickets as $ticket) {
            $meta = $ticket->metadata ?? [];
            if (isset($meta['global_alerted_at'])) continue;

            $meta['global_alerted_at'] = now()->toDateTimeString();
            $ticket->update(['metadata' => $meta]);

            Log::critical("Global SLA (180 min) Overdue disparado para el ticket {$ticket->code}");
            
            // Nota PRD: Aquí se alertaría a la sala de operaciones (sin cancelar el ticket).
        }
    }
}
