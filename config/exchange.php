<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SLA Configuration
    |--------------------------------------------------------------------------
    |
    | Tiempo máximo (en minutos) para que el cliente realice el pago.
    | alert_threshold_minutes: cuántos minutos antes del vencimiento se alerta.
    |
    */
    'sla' => [
        'duration_minutes'          => env('TICKET_SLA_MINUTES', 90),
        'alert_threshold_minutes'   => env('TICKET_SLA_ALERT_THRESHOLD_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profit Distribution (Porcentajes sobre GNB)
    |--------------------------------------------------------------------------
    |
    | Los porcentajes deben sumar 100. Cada clave corresponde a un rol.
    | GNB = Monto entregado - Sum(costos registrados)
    |
    */
    'distribution' => [
        'atc'            => env('DIST_ATC_PERCENT', 25),    // Agente de Taquilla / Oficina
        'broker'         => env('DIST_BROKER_PERCENT', 10),
        'provider'       => env('DIST_PROVIDER_PERCENT', 30),
        'external_admin' => env('DIST_ADMIN_PERCENT', 35),  // A1
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'USD', 'EUR', 'VES', 'COP', 'BRL', 'ARS', 'GBP',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket Code Prefix
    |--------------------------------------------------------------------------
    */
    'ticket_prefix' => env('TICKET_CODE_PREFIX', 'TC'),
];
