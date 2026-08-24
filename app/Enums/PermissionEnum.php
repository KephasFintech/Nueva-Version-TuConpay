<?php

namespace App\Enums;

enum PermissionEnum: string 
{
    // Módulo de Tickets / Operativo
    case TICKET_CREATE = 'ticket:create';                           // Etapa 01 (ATC)
    case TICKET_ASSIGN_RATE_INTERNAL = 'ticket:assign-rate-internal';// Etapa 02 (Admin)
    case TICKET_ASSIGN_RATE_EXTERNAL = 'ticket:assign-rate-external';// Etapa 02 (Dirección)
    case TICKET_SEND_TO_CLIENT = 'ticket:send-to-client';           // Etapa 03 (ATC)
    case TICKET_UPLOAD_AFFIDAVIT = 'ticket:upload-affidavit';       // Etapa 03 (ATC/Cliente)
    case TICKET_UPLOAD_PROOF = 'ticket:upload-proof';               // Etapa 04 (ATC/Cliente)
    case TICKET_VERIFY_PAYMENT_INTERNAL = 'ticket:verify-internal'; // Etapa 05 (Admin)
    case TICKET_VERIFY_PAYMENT_EXTERNAL = 'ticket:verify-external'; // Etapa 05 (Dirección)
    case TICKET_PROCESS_BRIDGE = 'ticket:process-bridge';           // Etapa 06 (Dirección/Admin)
    case TICKET_DISPATCH_COURIER = 'ticket:dispatch-courier';       // Etapa 07 (Admin)
    case TICKET_CONFIRM_DELIVERY = 'ticket:confirm-delivery';       // Etapa 08 (ATC/Courier)
    
    // Módulo de Cierre Financiero
    case TICKET_AUDIT_AGENTS = 'ticket:audit-agents';               // Etapa 09 (Data Analyst)
    case TICKET_SETTLE = 'ticket:settle';                           // Etapa 09 (Data Analyst)

    // Módulo de Reportes
    case REPORT_VIEW = 'report:view';                               // Ver reportes (Analista, Admin, Dirección)
    
    // Módulo de Caja (Cash Register)
    case CASH_OPEN_SHIFT = 'cash:open-shift';                       // Abrir caja
    case CASH_CLOSE_SHIFT = 'cash:close-shift';                     // Cierre de Caja (Admin/Data Analyst)
    case CASH_VIEW = 'cash:view';                                   // Ver caja y movimientos
    case CASH_REGISTER_MOVEMENT = 'cash:register-movement';         // Registrar movimiento manual
    
    // Módulo de Gastos de Empresa (Company Expenses)
    case EXPENSE_CREATE = 'expense:create';                         // Crear gasto
    case EXPENSE_APPROVE = 'expense:approve';                       // Aprobar gasto
    case EXPENSE_VIEW = 'expense:view';                             // Ver gastos
    
    // Módulo de Administración de Usuarios
    case USER_MANAGE    = 'user:manage';                            // Super Admin / Dirección
    case CLIENT_CREATE  = 'client:create';                          // Crear clientes (ATC)
    case CLIENT_VIEW    = 'client:view';                            // Ver lista de clientes
    case COURIER_CREATE = 'courier:create';                         // Crear motorizados
    case COURIER_VIEW   = 'courier:view';                           // Ver lista de motorizados
    case BROKER_CREATE  = 'broker:create';                          // Crear brokers
    case BROKER_VIEW    = 'broker:view';                            // Ver lista de brokers
    case EXTERNAL_ADMIN_CREATE = 'external-admin:create';           // Crear admin externo
    case EXTERNAL_ADMIN_VIEW   = 'external-admin:view';             // Ver admin externo
    case PROVIDER_CREATE = 'provider:create';                       // Crear proveedores
    case PROVIDER_VIEW   = 'provider:view';                         // Ver lista de proveedores

    /**
     * Retorna todos los valores como array de strings.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
