<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\ExchangeTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Servicio que implementa la máquina de estados de los tickets.
 * Valida y ejecuta transiciones, registrando el log de auditoría.
 */
class TicketStateMachine
{
    /**
     * Intenta transicionar un ticket al nuevo estado.
     *
     * @throws InvalidStateTransitionException
     */
    public function transition(ExchangeTicket $ticket, TicketStatus $newStatus, ?string $notes = null, ?string $providedOtp = null): ExchangeTicket
    {
        $currentStatus = $ticket->status;

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new InvalidStateTransitionException($currentStatus, $newStatus);
        }

        // PRD Requirement: OTP Validation before DELIVERED
        if ($newStatus === TicketStatus::DELIVERED && $ticket->delivery_otp) {
            if ($providedOtp !== $ticket->delivery_otp) {
                throw new \InvalidArgumentException('El OTP proporcionado es inválido para la entrega.');
            }
        }

        $ticket->status = $newStatus;

        // Si el ticket entra en estado terminal, registrar fecha de cierre
        if ($newStatus->isTerminal()) {
            $ticket->closed_at = now();
        }

        $ticket->save();

        // Auditoría: registrar el cambio de estado
        $ticket->statusLogs()->create([
            'from_status' => $currentStatus->value,
            'to_status'   => $newStatus->value,
            'changed_by'  => Auth::id(),
            'ip_address'  => Request::ip(),
            'notes'       => $notes,
        ]);

        return $ticket->fresh();
    }

    /**
     * Verifica si una transición es válida sin ejecutarla.
     */
    public function canTransition(ExchangeTicket $ticket, TicketStatus $newStatus): bool
    {
        return $ticket->status->canTransitionTo($newStatus);
    }

    /**
     * Retorna las transiciones disponibles desde el estado actual.
     *
     * @return array<TicketStatus>
     */
    public function availableTransitions(ExchangeTicket $ticket): array
    {
        return $ticket->status->allowedTransitions();
    }
}
