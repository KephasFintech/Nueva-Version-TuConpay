<?php

namespace App\Enums;

enum CashMovementSource: string
{
    case TICKET = 'ticket';
    case MANUAL_INCOME = 'manual_income';
    case COMPANY_EXPENSE = 'company_expense';
    case ADJUSTMENT = 'adjustment';
    case OPENING = 'opening';

    public function label(): string
    {
        return match ($this) {
            self::TICKET => 'Ticket de Cambio',
            self::MANUAL_INCOME => 'Ingreso Manual',
            self::COMPANY_EXPENSE => 'Gasto de Empresa',
            self::ADJUSTMENT => 'Ajuste',
            self::OPENING => 'Apertura de Caja',
        };
    }
}
