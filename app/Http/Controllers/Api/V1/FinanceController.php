<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Finance\StoreTicketCostRequest;
use App\Models\ExchangeTicket;
use App\Services\ProfitCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FinanceController extends ApiController
{
    public function __construct(
        private readonly ProfitCalculatorService $profitCalculator
    ) {}

    /**
     * Registrar Costo Operativo
     * 
     * Registra un costo asociado a un ticket (ej. C_Admin1, C_Prov2, C_Log).
     */
    public function storeCost(StoreTicketCostRequest $request, ExchangeTicket $exchangeTicket): JsonResponse
    {
        if ($exchangeTicket->status->isTerminal()) {
            return $this->error('No se pueden agregar costos a un ticket cerrado o cancelado', 422);
        }

        $cost = $exchangeTicket->costs()->create(array_merge(
            $request->validated(),
            ['registered_by' => Auth::id()]
        ));

        return $this->created($cost, 'Costo registrado exitosamente');
    }

    /**
     * Cierre Financiero del Ticket
     * 
     * Ejecuta el cálculo de la GNB y genera la distribución 25/10/30/35.
     * Solo puede ejecutarse si están asignados los 4 agentes (ATC, Broker, Provider, Admin).
     */
    public function close(ExchangeTicket $exchangeTicket): JsonResponse
    {
        if ($exchangeTicket->status->isTerminal()) {
            return $this->error('El ticket ya se encuentra en un estado terminal', 422);
        }

        try {
            $result = $this->profitCalculator->calculate($exchangeTicket);

            // Cambiamos el estado a cerrado usando el State Machine
            $stateMachine = app(\App\Services\TicketStateMachine::class);
            $stateMachine->transition(
                $exchangeTicket, 
                \App\Enums\TicketStatus::CLOSED, 
                'Cierre financiero automático'
            );

            return $this->success($result, 'Ticket cerrado y distribución calculada exitosamente');

        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\App\Exceptions\InvalidStateTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Consultar Distribución
     * 
     * Retorna el detalle de cómo se repartió la ganancia de un ticket cerrado.
     */
    public function showDistribution(ExchangeTicket $exchangeTicket): JsonResponse
    {
        return $this->success([
            'gnb'          => $exchangeTicket->gnb,
            'total_costs'  => $exchangeTicket->costs()->sum('amount'),
            'distribution' => $exchangeTicket->distribution()->with('user:id,name,email')->get()
        ], 'Distribución financiera');
    }
}
