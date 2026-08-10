<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('v1')->group(function () {
    
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        
        Route::middleware('auth:api')->group(function () {
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Operaciones protegidas por token
    Route::middleware('auth:api')->group(function () {
        // Usuarios & Agentes
        Route::middleware(['permission:user:manage'])->group(function () {
            Route::apiResource('users', UserController::class);
        });

        // Tickets de Cambio
        Route::get('exchange-tickets', [\App\Http\Controllers\Api\V1\ExchangeTicketController::class, 'index']);
        Route::post('exchange-tickets', [\App\Http\Controllers\Api\V1\ExchangeTicketController::class, 'store'])->middleware('permission:ticket:create');
        Route::get('exchange-tickets/{exchange_ticket}', [\App\Http\Controllers\Api\V1\ExchangeTicketController::class, 'show'])->can('view', 'exchange_ticket');
        Route::put('exchange-tickets/{exchange_ticket}', [\App\Http\Controllers\Api\V1\ExchangeTicketController::class, 'update']);
        
        Route::put('exchange-tickets/{exchange_ticket}/status', [\App\Http\Controllers\Api\V1\ExchangeTicketController::class, 'updateStatus']);
        Route::post('exchange-tickets/{exchange_ticket}/receipt', [\App\Http\Controllers\Api\V1\ReceiptController::class, 'store'])
             ->middleware('permission:ticket:upload-proof');
        Route::get('exchange-tickets/{exchange_ticket}/sla', [\App\Http\Controllers\Api\V1\SlaController::class, 'show']);

        // Finanzas
        Route::post('exchange-tickets/{exchange_ticket}/costs', [\App\Http\Controllers\Api\V1\FinanceController::class, 'storeCost']);
        Route::post('exchange-tickets/{exchange_ticket}/close', [\App\Http\Controllers\Api\V1\FinanceController::class, 'close'])
             ->middleware('four_agents')
             ->can('settle', 'exchange_ticket');
             
        Route::get('exchange-tickets/{exchange_ticket}/distribution', [\App\Http\Controllers\Api\V1\FinanceController::class, 'showDistribution']);
        
        // Reportes
        Route::get('reports/profit', [\App\Http\Controllers\Api\V1\ReportController::class, 'profit'])
             ->middleware('permission:ticket:audit-agents');

        // Notificaciones
        Route::get('notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markRead']);
    });

});
