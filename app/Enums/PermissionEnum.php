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
    case CASH_CLOSE_SHIFT = 'cash:close-shift';                     // Cierre de Caja (Admin/Data Analyst)
    
    // Módulo de Administración de Usuarios
    case USER_MANAGE    = 'user:manage';                            // Super Admin / Dirección
    case CLIENT_CREATE  = 'client:create';                          // Crear clientes (ATC)

    /**
     * Retorna todos los valores como array de strings.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
