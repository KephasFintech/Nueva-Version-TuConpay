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
        Route::get('users', [UserController::class, 'index'])->middleware(['permission:user:manage|client:view|courier:view|broker:view|external-admin:view|provider:view']);
        Route::middleware(['permission:user:manage'])->group(function () {
            Route::apiResource('users', UserController::class)->except(['store', 'index']);
        });

        // Creación de usuario: accesible por admins (user:manage) y por ATC (client:create)
        Route::post('users', [UserController::class, 'store'])
             ->middleware(['permission:user:manage|client:create|courier:create|broker:create|external-admin:create|provider:create']);

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
        
        // Caja (Cash Registers)
        Route::middleware('permission:cash:view')->group(function () {
            Route::get('cash-registers', [\App\Http\Controllers\Api\V1\CashRegisterController::class, 'index']);
            Route::get('cash-registers/{cash_register}', [\App\Http\Controllers\Api\V1\CashRegisterController::class, 'show']);
            Route::get('cash-registers/{cash_register}/summary', [\App\Http\Controllers\Api\V1\CashRegisterController::class, 'summary']);
        });
        Route::post('cash-registers', [\App\Http\Controllers\Api\V1\CashRegisterController::class, 'store'])->middleware('permission:cash:open-shift');
        Route::post('cash-registers/{cash_register}/close', [\App\Http\Controllers\Api\V1\CashRegisterController::class, 'close'])->middleware('permission:cash:close-shift');
        Route::post('cash-registers/{cash_register}/movements', [\App\Http\Controllers\Api\V1\CashRegisterController::class, 'storeMovement'])->middleware('permission:cash:register-movement');

        // Gastos de Empresa (Company Expenses)
        Route::middleware('permission:expense:view')->group(function () {
            Route::get('company-expenses', [\App\Http\Controllers\Api\V1\CompanyExpenseController::class, 'index']);
            Route::get('company-expenses/{company_expense}', [\App\Http\Controllers\Api\V1\CompanyExpenseController::class, 'show']);
        });
        Route::post('company-expenses', [\App\Http\Controllers\Api\V1\CompanyExpenseController::class, 'store'])->middleware('permission:expense:create');
        Route::post('company-expenses/{company_expense}/approve', [\App\Http\Controllers\Api\V1\CompanyExpenseController::class, 'approve'])->middleware('permission:expense:approve');
        
        // Reportes
        Route::get('reports/profit', [\App\Http\Controllers\Api\V1\ReportController::class, 'profit'])
             ->middleware('permission:ticket:audit-agents');

        // Notificaciones
        Route::get('notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markRead']);
    });

});
