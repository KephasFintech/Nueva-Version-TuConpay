<?php

use App\Jobs\CancelExpiredTicketsJob;
use App\Jobs\CheckSlaAlertsJob;
use Illuminate\Support\Facades\Schedule;

// Ejecutar revisión de alertas de SLA cada minuto
Schedule::job(new CheckSlaAlertsJob)->everyMinute();

// Ejecutar cancelación automática de tickets vencidos cada minuto
Schedule::job(new CancelExpiredTicketsJob)->everyMinute();
