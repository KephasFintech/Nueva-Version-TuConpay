<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CashMovementSource;
use App\Enums\CashRegisterStatus;
use App\Http\Requests\Finance\CloseCashRegisterRequest;
use App\Http\Requests\Finance\OpenCashRegisterRequest;
use App\Http\Requests\Finance\StoreCashMovementRequest;
use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends ApiController
{
    /**
     * Listar turnos de caja
     */
    public function index(Request $request): JsonResponse
    {
        $query = CashRegister::with(['openedBy', 'closedBy'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginate($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Abrir caja
     */
    public function store(OpenCashRegisterRequest $request): JsonResponse
    {
        // Verificar si ya hay una caja abierta
        $openRegister = CashRegister::where('status', CashRegisterStatus::OPEN->value)->first();
        
        if ($openRegister) {
            return $this->error('Ya existe un turno de caja abierto ('.$openRegister->code.')', 422);
        }

        $currency = $request->input('currency', 'USD');
        
        $register = DB::transaction(function () use ($request, $currency) {
            $register = CashRegister::create([
                'code' => CashRegister::generateCode(),
                'opened_by' => Auth::id(),
                'opening_balance' => $request->input('opening_balance'),
                'currency' => $currency,
                'status' => CashRegisterStatus::OPEN->value,
                'opened_at' => now(),
                'notes' => $request->input('notes'),
            ]);

            // Registrar el movimiento inicial
            if ($register->opening_balance > 0) {
                $register->movements()->create([
                    'type' => \App\Enums\CashMovementType::INCOME->value,
                    'source' => CashMovementSource::OPENING->value,
                    'amount' => $register->opening_balance,
                    'currency' => $currency,
                    'description' => 'Saldo de apertura',
                    'registered_by' => Auth::id(),
                ]);
            }

            return $register;
        });

        return $this->created($register, 'Caja abierta exitosamente');
    }

    /**
     * Ver detalle y movimientos
     */
    public function show(CashRegister $cashRegister): JsonResponse
    {
        $cashRegister->load(['openedBy', 'closedBy', 'movements.registeredBy']);
        $cashRegister->current_expected_balance = $cashRegister->calculateExpectedBalance();

        return $this->success($cashRegister);
    }

    /**
     * Cerrar caja
     */
    public function close(CloseCashRegisterRequest $request, CashRegister $cashRegister): JsonResponse
    {
        if ($cashRegister->status === CashRegisterStatus::CLOSED) {
            return $this->error('La caja ya se encuentra cerrada', 422);
        }

        $expectedBalance = $cashRegister->calculateExpectedBalance();
        $closingBalance = $request->input('closing_balance');
        $difference = $closingBalance - $expectedBalance;

        $cashRegister->update([
            'closing_balance' => $closingBalance,
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'closed_by' => Auth::id(),
            'status' => CashRegisterStatus::CLOSED->value,
            'closed_at' => now(),
            'notes' => $request->input('notes') ? $cashRegister->notes . "\nCierre: " . $request->input('notes') : $cashRegister->notes,
        ]);

        return $this->success($cashRegister, 'Caja cerrada exitosamente');
    }

    /**
     * Registrar movimiento manual (Ajustes o Ingresos/Egresos directos)
     */
    public function storeMovement(StoreCashMovementRequest $request, CashRegister $cashRegister): JsonResponse
    {
        if ($cashRegister->status === CashRegisterStatus::CLOSED) {
            return $this->error('No se pueden registrar movimientos en una caja cerrada', 422);
        }

        $movement = $cashRegister->movements()->create([
            'type' => $request->input('type'),
            'source' => CashMovementSource::MANUAL_INCOME->value, // Podría ampliarse según necesidad
            'amount' => $request->input('amount'),
            'currency' => $cashRegister->currency,
            'description' => $request->input('description'),
            'registered_by' => Auth::id(),
        ]);

        return $this->created($movement, 'Movimiento registrado exitosamente');
    }

    /**
     * Resumen de caja actual o de un registro específico
     */
    public function summary(CashRegister $cashRegister): JsonResponse
    {
        $incomes = $cashRegister->movements()->where('type', \App\Enums\CashMovementType::INCOME->value)->sum('amount');
        $expenses = $cashRegister->movements()->where('type', \App\Enums\CashMovementType::EXPENSE->value)->sum('amount');
        
        return $this->success([
            'register_code' => $cashRegister->code,
            'status' => $cashRegister->status->label(),
            'currency' => $cashRegister->currency,
            'opening_balance' => $cashRegister->opening_balance,
            'total_incomes' => $incomes,
            'total_expenses' => $expenses,
            'expected_balance' => $cashRegister->calculateExpectedBalance(),
        ]);
    }
}
