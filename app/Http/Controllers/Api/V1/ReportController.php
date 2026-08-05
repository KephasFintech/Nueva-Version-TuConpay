<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketStatus;
use App\Models\ExchangeTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends ApiController
{
    /**
     * Dashboard de Ganancias y Métricas
     * 
     * Retorna métricas generales como tickets por estado, GNB total, y deudas.
     */
    public function profit(Request $request): JsonResponse
    {
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
            
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        // 1. GNB Total en el período
        $gnbTotal = ExchangeTicket::whereBetween('closed_at', [$startDate, $endDate])
            ->where('status', TicketStatus::CLOSED->value)
            ->sum('gnb');

        // 2. Tickets por estado (activos)
        $ticketsByStatus = ExchangeTicket::select('status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // 3. Monto total movido (volumen)
        $totalVolume = ExchangeTicket::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', [TicketStatus::CANCELLED->value, TicketStatus::CANCELLED_BY_TIMEOUT->value])
            ->sum('amount_requested');

        // 4. Deudas pendientes de pago a agentes en el período
        $pendingPayments = DB::table('ticket_distributions')
            ->join('exchange_tickets', 'ticket_distributions.ticket_id', '=', 'exchange_tickets.id')
            ->select('ticket_distributions.beneficiary_role', DB::raw('SUM(ticket_distributions.amount) as total_debt'))
            ->whereNull('ticket_distributions.paid_at')
            ->whereBetween('exchange_tickets.closed_at', [$startDate, $endDate])
            ->groupBy('ticket_distributions.beneficiary_role')
            ->get();

        return $this->success([
            'period' => [
                'start' => $startDate->toDateString(),
                'end'   => $endDate->toDateString(),
            ],
            'metrics' => [
                'gnb_total'         => (float) $gnbTotal,
                'total_volume'      => (float) $totalVolume,
                'tickets_by_status' => $ticketsByStatus,
                'pending_payments'  => $pendingPayments,
            ]
        ], 'Reporte generado exitosamente');
    }
}
