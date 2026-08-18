<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use App\Enums\CashRegisterStatus;
use App\Http\Requests\Finance\StoreCompanyExpenseRequest;
use App\Models\CashRegister;
use App\Models\CompanyExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyExpenseController extends ApiController
{
    /**
     * Listar gastos
     */
    public function index(Request $request): JsonResponse
    {
        $query = CompanyExpense::with(['registeredBy', 'approvedBy', 'cashRegister'])
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        return $this->paginate($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Registrar un gasto
     */
    public function store(StoreCompanyExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['registered_by'] = Auth::id();
        $data['currency'] = $request->input('currency', 'USD');

        // Si hay una caja abierta, podemos vincularlo (o dejarlo opcional si es por transferencia, etc.)
        // Para simplificar: si el gasto es en efectivo, se asume que sale de la caja abierta
        $openRegister = CashRegister::where('status', CashRegisterStatus::OPEN->value)->first();

        $expense = DB::transaction(function () use ($data, $openRegister) {
            if ($openRegister) {
                $data['cash_register_id'] = $openRegister->id;
            }

            $expense = CompanyExpense::create($data);

            if ($openRegister) {
                $openRegister->movements()->create([
                    'type' => CashMovementType::EXPENSE->value,
                    'source' => CashMovementSource::COMPANY_EXPENSE->value,
                    'reference_id' => $expense->id,
                    'amount' => $expense->amount,
                    'currency' => $expense->currency,
                    'description' => "Gasto: {$expense->category} - " . ($expense->description ?? 'Sin detalle'),
                    'registered_by' => Auth::id(),
                ]);
            }

            return $expense;
        });

        return $this->created($expense->load('cashRegister'), 'Gasto registrado exitosamente');
    }

    /**
     * Ver detalle
     */
    public function show(CompanyExpense $companyExpense): JsonResponse
    {
        return $this->success($companyExpense->load(['registeredBy', 'approvedBy', 'cashRegister']));
    }

    /**
     * Aprobar gasto
     */
    public function approve(CompanyExpense $companyExpense): JsonResponse
    {
        if ($companyExpense->approved_at !== null) {
            return $this->error('Este gasto ya ha sido aprobado', 422);
        }

        $companyExpense->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $this->success($companyExpense, 'Gasto aprobado exitosamente');
    }
}
