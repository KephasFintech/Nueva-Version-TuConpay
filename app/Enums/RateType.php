<?php

namespace App\Enums;

enum RateType: string
{
    case FIXED      = 'fixed';
    case PERCENTAGE = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::FIXED      => 'Fija',
            self::PERCENTAGE => 'Porcentaje',
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
