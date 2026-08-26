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

        // Si el ticket pasa a PAYMENT_RECEIVED en flujo interno -> EGRESO de caja
        if ($newStatus === TicketStatus::PAYMENT_RECEIVED && $ticket->external_admin_id === null) {
            $this->registerCashExpense($ticket);
        }

        // Auditoría: registrar el cambio de estado
        $ticket->statusLogs()->create([
            'from_status' => $currentStatus->value,
            'to_status'   => $newStatus->value,
            'changed_by'  => Auth::id(),
            'ip_address'  => Request::ip(),
            'notes'       => $notes,
        ]);

        \App\Events\TicketStatusChanged::dispatch($ticket);

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

    /**
     * Registra el egreso de la caja abierta si el ticket usa capital interno.
     */
    private function registerCashExpense(ExchangeTicket $ticket): void
    {
        $openRegister = \App\Models\CashRegister::where(
            'status', \App\Enums\CashRegisterStatus::OPEN->value
        )->first();

        if ($openRegister && $ticket->amount_to_deliver > 0) {
            $openRegister->movements()->create([
                'type'          => \App\Enums\CashMovementType::EXPENSE->value,
                'source'        => \App\Enums\CashMovementSource::TICKET->value,
                'reference_id'  => $ticket->id,
                'amount'        => $ticket->amount_to_deliver,
                'currency'      => $openRegister->currency,
                'description'   => "Desembolso por ticket interno {$ticket->code}",
                'registered_by' => Auth::id(),
            ]);
        }
    }
}
