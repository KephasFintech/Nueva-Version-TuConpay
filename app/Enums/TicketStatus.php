<?php

namespace App\Enums;

enum TicketStatus: string
{
    case DRAFT               = 'draft';
    case WAITING_PAYMENT     = 'waiting_payment';
    case PAYMENT_RECEIVED    = 'payment_received';
    case PROCESSING          = 'processing';
    case READY_FOR_DELIVERY  = 'ready_for_delivery';
    case IN_TRANSIT          = 'in_transit';
    case DELIVERED           = 'delivered';
    case CLOSED              = 'closed';
    case CANCELLED           = 'cancelled';
    case CANCELLED_BY_TIMEOUT = 'cancelled_by_timeout';
    case DISPUTED            = 'disputed';

    /**
     * Retorna los estados a los que se puede transicionar desde el estado actual.
     *
     * @return array<TicketStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT               => [self::WAITING_PAYMENT, self::CANCELLED],
            self::WAITING_PAYMENT     => [self::PAYMENT_RECEIVED, self::CANCELLED, self::CANCELLED_BY_TIMEOUT],
            self::PAYMENT_RECEIVED    => [self::PROCESSING, self::DISPUTED],
            self::PROCESSING          => [self::READY_FOR_DELIVERY, self::DISPUTED],
            self::READY_FOR_DELIVERY  => [self::IN_TRANSIT, self::DELIVERED],
            self::IN_TRANSIT          => [self::DELIVERED],
            self::DELIVERED           => [self::CLOSED],
            self::CLOSED              => [],
            self::CANCELLED           => [],
            self::CANCELLED_BY_TIMEOUT => [],
            self::DISPUTED            => [self::PROCESSING, self::CANCELLED],
        };
    }

    /**
     * Verifica si la transición al estado dado está permitida.
     */
    public function canTransitionTo(TicketStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Indica si el ticket está en un estado terminal (no puede avanzar).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::CLOSED,
            self::CANCELLED,
            self::CANCELLED_BY_TIMEOUT,
        ], true);
    }

    /**
     * Indica si el ticket está activo (SLA aplica).
     */
    public function isSlaActive(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::WAITING_PAYMENT,
        ], true);
    }

    /**
     * Etiqueta legible para humanos.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT               => 'Borrador',
            self::WAITING_PAYMENT     => 'Esperando Pago',
            self::PAYMENT_RECEIVED    => 'Pago Recibido',
            self::PROCESSING          => 'En Proceso',
            self::READY_FOR_DELIVERY  => 'Listo para Entrega',
            self::IN_TRANSIT          => 'En Tránsito',
            self::DELIVERED           => 'Entregado',
            self::CLOSED              => 'Cerrado',
            self::CANCELLED           => 'Cancelado',
            self::CANCELLED_BY_TIMEOUT => 'Cancelado por Tiempo',
            self::DISPUTED            => 'En Disputa',
        };
    }
}
