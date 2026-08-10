<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN    = 'super_admin';
    case ATC            = 'atc';            // Agente de Taquilla / Cambios
    case ADMIN          = 'admin';          // Administración
    case DIRECTION      = 'direction';      // Dirección
    case DATA_ANALYST   = 'data_analyst';   // Registro / Análisis de Datos
    case BROKER         = 'broker';         // Corredor / Intermediario
    case EXTERNAL_ADMIN = 'external_admin'; // Administrador Externo (A1)
    case PROVIDER       = 'provider';       // Proveedor (P2/P3)
    case COURIER        = 'courier';        // Motorizado / Logística
    case CLIENT         = 'client';         // Cliente

    /**
     * Etiqueta legible.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN    => 'Super Administrador',
            self::ATC            => 'Agente de Taquilla',
            self::ADMIN          => 'Administración',
            self::DIRECTION      => 'Dirección',
            self::DATA_ANALYST   => 'Analista de Datos',
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
     * Roles que pueden gestionar tickets (operativos principales).
     *
     * @return array<string>
     */
    public static function operativeRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::ATC->value,
            self::ADMIN->value,
            self::DIRECTION->value,
            self::DATA_ANALYST->value,
        ];
    }

    /**
     * Roles agentes (participan en la distribución financiera y comisiones).
     *
     * @return array<string>
     */
    public static function financialAgentRoles(): array
    {
        return [
            self::BROKER->value,
            self::EXTERNAL_ADMIN->value,
            self::PROVIDER->value,
        ];
    }
}
