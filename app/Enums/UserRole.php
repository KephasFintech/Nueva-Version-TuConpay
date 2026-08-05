<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN    = 'super_admin';
    case ATC            = 'atc';            // Agente de Taquilla / Cambios
    case BROKER         = 'broker';
    case EXTERNAL_ADMIN = 'external_admin'; // Administrador Externo (A1)
    case PROVIDER       = 'provider';       // Proveedor (P2/P3)
    case COURIER        = 'courier';        // Motorizado / Logística
    case CLIENT         = 'client';

    /**
     * Etiqueta legible.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN    => 'Super Administrador',
            self::ATC            => 'Agente de Taquilla',
            self::BROKER         => 'Broker',
            self::EXTERNAL_ADMIN => 'Administrador Externo',
            self::PROVIDER       => 'Proveedor',
            self::COURIER        => 'Motorizado',
            self::CLIENT         => 'Cliente',
        };
    }

    /**
     * Retorna todos los valores como array de strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Roles que pueden gestionar tickets (operativos).
     *
     * @return array<string>
     */
    public static function operativeRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::ATC->value,
            self::BROKER->value,
        ];
    }

    /**
     * Roles agentes (participan en la distribución financiera).
     *
     * @return array<string>
     */
    public static function financialAgentRoles(): array
    {
        return [
            self::ATC->value,
            self::BROKER->value,
            self::EXTERNAL_ADMIN->value,
            self::PROVIDER->value,
        ];
    }
}
