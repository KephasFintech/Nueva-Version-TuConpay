<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketStatus;
use App\Http\Requests\Ticket\StoreExchangeTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Models\ExchangeTicket;
use App\Services\TicketStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeTicketController extends ApiController
{
    public function __construct(
        private readonly TicketStateMachine $stateMachine
    ) {}

    /**
     * Listar Tickets
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExchangeTicket::with(['client', 'atc', 'broker', 'provider']);

        // Filtros básicos
        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        // Ordenamiento
        $query->orderByDesc('created_at');

        return $this->paginate($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Crear Ticket
     */
    public function store(StoreExchangeTicketRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $data['code'] = ExchangeTicket::generateCode();
        $data['atc_user_id'] = Auth::id();
        $data['status'] = TicketStatus::DRAFT->value;
        $data['expires_at'] = ExchangeTicket::calculateExpiresAt();

        $ticket = new ExchangeTicket($data);
        if ($ticket->exchange_rate !== null && !isset($data['amount_to_deliver'])) {
            $ticket->amount_to_deliver = $ticket->calculateAmountToDeliver();
        }
        $ticket->save();

        // Registro inicial en auditoría
        $ticket->statusLogs()->create([
            'to_status'  => TicketStatus::DRAFT->value,
            'changed_by' => Auth::id(),
            'ip_address' => $request->ip(),
            'notes'      => 'Ticket creado',
        ]);

        return $this->created($ticket->load('client'), 'Ticket creado exitosamente');
    }

    /**
     * Ver Ticket
     */
    public function show(ExchangeTicket $exchangeTicket): JsonResponse
    {
        return $this->success(
            $exchangeTicket->load([
                'client', 'atc', 'broker', 'provider', 'externalAdmin', 'courier',
                'statusLogs.changedBy', 'costs', 'receipts', 'distribution'
            ])
        );
    }

    /**
     * Cambiar Estado del Ticket
     */
    public function updateStatus(UpdateTicketStatusRequest $request, ExchangeTicket $exchangeTicket): JsonResponse
    {
        $newStatus = TicketStatus::from($request->input('status'));

        try {
            $ticket = $this->stateMachine->transition(
                $exchangeTicket, 
                $newStatus, 
                $request->input('notes')
            );

            return $this->success($ticket, "Estado cambiado a {$newStatus->label()}");
            
        } catch (\App\Exceptions\InvalidStateTransitionException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
    /**
     * Actualizar datos del Ticket (Ej. Tasa de cambio por el Admin)
     */
    public function update(\Illuminate\Http\Request $request, ExchangeTicket $exchangeTicket): JsonResponse
    {
        $data = $request->validate([
            'rate_type' => 'nullable|string|in:fixed,percentage',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount_to_deliver' => 'nullable|numeric|min:0',
            'external_admin_id' => 'nullable|integer|exists:users,id',
            'provider_id' => 'nullable|integer|exists:users,id',
            'courier_id' => 'nullable|integer|exists:users,id',
        ]);
        
        $exchangeTicket->fill($data);
        if ($exchangeTicket->isDirty(['exchange_rate', 'rate_type']) && !isset($data['amount_to_deliver'])) {
            $exchangeTicket->amount_to_deliver = $exchangeTicket->calculateAmountToDeliver();
        }
        $exchangeTicket->save();
        return $this->success(
            $exchangeTicket->fresh()->load('client', 'atc'), 
            'Ticket actualizado exitosamente'
        );
    }
}
