<?php

namespace App\Enums;

enum CostType: string
{
    case ADMIN1    = 'admin1';    // Costo Administrador Externo (C_Admin1)
    case PROVIDER  = 'provider';  // Costo Proveedor (C_Prov2)
    case LOGISTICS = 'logistics'; // Costo Logística / Motorizado (C_Log)
    case OTHER     = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN1    => 'Administrador Externo (A1)',
            self::PROVIDER  => 'Proveedor (P2/P3)',
            self::LOGISTICS => 'Logística / Motorizado',
            self::OTHER     => 'Otro',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
