<?php

namespace App\Exceptions;

use Exception;
use App\Enums\TicketStatus;

class InvalidStateTransitionException extends Exception
{
    public function __construct(TicketStatus $from, TicketStatus $to)
    {
        parent::__construct(
            "Transición inválida de estado: [{$from->label()}] → [{$to->label()}]. " .
            "Esta transición no está permitida por el flujo del sistema."
        );
    }
}
