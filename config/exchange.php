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
        'global_duration_minutes'   => env('TICKET_SLA_GLOBAL_MINUTES', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profit Distribution (Porcentajes sobre GNB)
    |--------------------------------------------------------------------------
    |
    | Los porcentajes deben sumar 100.
    | GNB = Monto entregado - Sum(costos registrados)
    |
    */
    'distribution' => [
        'broker'         => env('DIST_BROKER_PERCENT', 25),
        'investor'       => env('DIST_INVESTOR_PERCENT', 10),
        'team'           => env('DIST_TEAM_PERCENT', 30),
        'office'         => env('DIST_OFFICE_PERCENT', 35),
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
