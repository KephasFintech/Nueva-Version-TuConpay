<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExchangeTicket;
use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Enums\TicketStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExchangeTicketPolicy
{
    use HandlesAuthorization;

    /**
     * Ver ticket: El cliente solo ve sus tickets. El broker solo ve donde está asignado. ATC/Admin ven todos.
     */
    public function view(User $user, ExchangeTicket $ticket): bool
    {
        if ($user->hasRole([
            UserRole::SUPER_ADMIN->value, 
            UserRole::ATC->value, 
            UserRole::ADMIN->value, 
            UserRole::DIRECTION->value,
            UserRole::DATA_ANALYST->value
        ])) {
            return true;
        }

        if ($user->hasRole(UserRole::CLIENT->value)) {
            return $ticket->client_id === $user->id;
        }

        if ($user->hasRole(UserRole::BROKER->value)) {
            return $ticket->broker_id === $user->id;
        }
        
        if ($user->hasRole(UserRole::EXTERNAL_ADMIN->value)) {
            return $ticket->external_admin_id === $user->id;
        }
        
        if ($user->hasRole(UserRole::PROVIDER->value)) {
            return $ticket->provider_id === $user->id;
        }

        return false;
    }

    /**
     * Permiso para asignar tasa (Etapa 02)
     */
    public function assignRate(User $user, ExchangeTicket $ticket): bool
    {
        return $ticket->status === TicketStatus::DRAFT && 
               ($user->can(PermissionEnum::TICKET_ASSIGN_RATE_INTERNAL->value) || 
                $user->can(PermissionEnum::TICKET_ASSIGN_RATE_EXTERNAL->value));
    }

    /**
     * Permiso para verificar pago (Etapa 05)
     */
    public function verifyPayment(User $user, ExchangeTicket $ticket): bool
    {
        // En la vida real, se verifica si payment_proof_path o receipts existen.
        // Aquí validamos el estado y el permiso del rol.
        return $ticket->status === TicketStatus::WAITING_PAYMENT && 
               ($user->can(PermissionEnum::TICKET_VERIFY_PAYMENT_INTERNAL->value) || 
                $user->can(PermissionEnum::TICKET_VERIFY_PAYMENT_EXTERNAL->value));
    }

    /**
     * Permiso para Liquidar y Dispersar GNB (Etapa 09)
     */
    public function settle(User $user, ExchangeTicket $ticket): bool
    {
        // Regla: Debe tener los agentes operativos y financieros requeridos asignados antes de liquidar
        if (!$ticket->hasRequiredAgentsAssigned()) {
            return false;
        }

        // Se permite liquidar si está entregado
        return $ticket->status === TicketStatus::DELIVERED && 
               $user->can(PermissionEnum::TICKET_SETTLE->value);
    }
}
