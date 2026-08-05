<?php

namespace App\Services;

use App\Models\ExchangeTicket;

/**
 * Servicio SLA: manejo de tiempos y estados de alerta.
 */
class SlaService
{
    public function __construct(
        private readonly int $durationMinutes,
        private readonly int $alertThresholdMinutes
    ) {}

    /**
     * Segundos restantes antes del vencimiento del SLA.
     * Retorna 0 si ya venció.
     */
    public function getRemainingSeconds(ExchangeTicket $ticket): int
    {
        if (! $ticket->expires_at) {
            return 0;
        }

        $remaining = now()->diffInSeconds($ticket->expires_at, false);

        return max(0, (int) $remaining);
    }

    /**
     * Indica si el ticket ya venció su SLA.
     */
    public function isOverdue(ExchangeTicket $ticket): bool
    {
        return $ticket->expires_at && now()->isAfter($ticket->expires_at);
    }

    /**
     * Indica si el ticket está dentro del umbral de alerta (ej. 30 min restantes).
     */
    public function isInAlertZone(ExchangeTicket $ticket): bool
    {
        $remaining = $this->getRemainingSeconds($ticket);
        return $remaining > 0 && $remaining <= ($this->alertThresholdMinutes * 60);
    }

    /**
     * Indica si ya se envió la alerta de SLA para este ticket.
     */
    public function hasBeenAlerted(ExchangeTicket $ticket): bool
    {
        return ! is_null($ticket->sla_alerted_at);
    }

    /**
     * Datos completos del SLA para la respuesta del endpoint.
     *
     * @return array<string, mixed>
     */
    public function getSlaData(ExchangeTicket $ticket): array
    {
        $remainingSeconds = $this->getRemainingSeconds($ticket);

        return [
            'expires_at'         => $ticket->expires_at?->toIso8601String(),
            'seconds_remaining'  => $remainingSeconds,
            'minutes_remaining'  => (int) floor($remainingSeconds / 60),
            'is_overdue'         => $this->isOverdue($ticket),
            'is_in_alert_zone'   => $this->isInAlertZone($ticket),
            'is_alerted'         => $this->hasBeenAlerted($ticket),
            'sla_alerted_at'     => $ticket->sla_alerted_at?->toIso8601String(),
            'sla_duration_min'   => $this->durationMinutes,
            'alert_threshold_min' => $this->alertThresholdMinutes,
        ];
    }
}
