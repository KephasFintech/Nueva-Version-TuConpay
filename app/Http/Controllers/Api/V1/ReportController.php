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
        $cacheKey = 'report:profit:' . md5(json_encode($request->only(['start_date', 'end_date', 'currency'])));

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            $startDate = $request->filled('start_date') 
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : now()->startOfMonth();
                
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : now()->endOfMonth();

            $currency = $request->input('currency');
            $hasCurrencyFilter = !empty($currency) && $currency !== 'ALL';

            // 1. GNB Total en el período
            $gnbTotal = ExchangeTicket::whereBetween('closed_at', [$startDate, $endDate])
                ->where('status', TicketStatus::CLOSED->value)
                ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
                ->sum('gnb');

            // 2. Tickets por estado (activos)
            $ticketsByStatus = ExchangeTicket::select('status', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            // 3. Monto total movido (volumen)
            $totalVolume = ExchangeTicket::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('status', [TicketStatus::CANCELLED->value, TicketStatus::CANCELLED_BY_TIMEOUT->value])
                ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
                ->sum('amount_requested');

            // 4. Deudas pendientes de pago a agentes en el período
            $pendingPayments = DB::table('ticket_distributions')
                ->join('exchange_tickets', 'ticket_distributions.ticket_id', '=', 'exchange_tickets.id')
                ->select('ticket_distributions.beneficiary_role', DB::raw('SUM(ticket_distributions.amount) as total_debt'))
                ->whereNull('ticket_distributions.paid_at')
                ->whereBetween('exchange_tickets.closed_at', [$startDate, $endDate])
                ->when($hasCurrencyFilter, fn($q) => $q->where('exchange_tickets.currency_from', $currency))
                ->groupBy('ticket_distributions.beneficiary_role')
                ->get();

            return [
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
            ];
        });

        return $this->success($data, 'Reporte generado exitosamente');
    }

    public function timeseries(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'currency'   => 'nullable|string|max:10',
            'group_by'   => 'nullable|in:day,week,month',
        ]);

        $cacheKey = 'report:timeseries:' . md5(json_encode($request->only(['start_date', 'end_date', 'currency', 'group_by'])));

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : now()->startOfMonth();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : now()->endOfMonth();

            $groupBy  = $request->input('group_by', 'day');
            $currency = $request->input('currency');
            $hasCurrencyFilter = !empty($currency) && $currency !== 'ALL';

            // Formato SQL según agrupación
            $dateFormat = match ($groupBy) {
                'week'  => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            $base = ExchangeTicket::whereBetween('closed_at', [$startDate, $endDate])
                ->where('status', TicketStatus::CLOSED->value)
                ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency));

            // GNB agrupado
            $gnbSeries = (clone $base)
                ->select(DB::raw("DATE_FORMAT(closed_at, '{$dateFormat}') as date"), DB::raw('SUM(gnb) as value'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Volumen agrupado (usa created_at, todos los no-cancelados)
            $volumeSeries = ExchangeTicket::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('status', [TicketStatus::CANCELLED->value, TicketStatus::CANCELLED_BY_TIMEOUT->value])
                ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
                ->select(DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date"), DB::raw('SUM(amount_requested) as value'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Conteo de tickets creados
            $countSeries = ExchangeTicket::whereBetween('created_at', [$startDate, $endDate])
                ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
                ->select(DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date"), DB::raw('COUNT(*) as value'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'period'   => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
                'group_by' => $groupBy,
                'series'   => [
                    'gnb'          => $gnbSeries,
                    'volume'       => $volumeSeries,
                    'ticket_count' => $countSeries,
                ],
            ];
        });

        return $this->success($data, 'Series temporales generadas');
    }

    public function agents(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'currency'   => 'nullable|string|max:10',
            'role'       => 'nullable|string|in:atc,broker,provider,external_admin,courier',
        ]);

        $cacheKey = 'report:agents:' . md5(json_encode($request->only(['start_date', 'end_date', 'currency', 'role'])));

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : now()->startOfMonth();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : now()->endOfMonth();

            $currency = $request->input('currency');
            $hasCurrencyFilter = !empty($currency) && $currency !== 'ALL';
            $roleFilter = $request->input('role');

            $roleColumns = [
                'atc'            => 'atc_user_id',
                'broker'         => 'broker_id',
                'provider'       => 'provider_id',
                'external_admin' => 'external_admin_id',
                'courier'        => 'courier_id',
            ];

            if ($roleFilter) {
                $roleColumns = array_intersect_key($roleColumns, [$roleFilter => true]);
            }

            $results = [];

            foreach ($roleColumns as $role => $column) {
                $rows = ExchangeTicket::whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotNull($column)
                    ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
                    ->select(
                        "{$column} as user_id",
                        DB::raw('COUNT(*) as tickets_count'),
                        DB::raw('SUM(CASE WHEN status = "closed" THEN gnb ELSE 0 END) as gnb_generated'),
                        DB::raw('SUM(CASE WHEN status NOT IN ("cancelled","cancelled_by_timeout") THEN amount_requested ELSE 0 END) as volume_operated')
                    )
                    ->groupBy($column)
                    ->with("{$this->roleRelation($role)}:id,name")
                    ->get();

                foreach ($rows as $row) {
                    $pendingDebt = DB::table('ticket_distributions')
                        ->join('exchange_tickets', 'ticket_distributions.ticket_id', '=', 'exchange_tickets.id')
                        ->where('ticket_distributions.user_id', $row->user_id)
                        ->whereNull('ticket_distributions.paid_at')
                        ->whereBetween('exchange_tickets.closed_at', [$startDate, $endDate])
                        ->sum('ticket_distributions.amount');

                    $results[] = [
                        'user_id'        => $row->user_id,
                        'name'           => $row->{$this->roleRelation($role)}?->name ?? 'N/A',
                        'role'           => $role,
                        'tickets_count'  => $row->tickets_count,
                        'gnb_generated'  => (float) $row->gnb_generated,
                        'volume_operated'=> (float) $row->volume_operated,
                        'pending_debt'   => (float) $pendingDebt,
                    ];
                }
            }

            usort($results, fn($a, $b) => $b['gnb_generated'] <=> $a['gnb_generated']);

            return [
                'period' => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
                'agents' => $results,
            ];
        });

        return $this->success($data, 'Rendimiento por agente generado');
    }

    private function roleRelation(string $role): string
    {
        return match ($role) {
            'atc'            => 'atc',
            'broker'         => 'broker',
            'provider'       => 'provider',
            'external_admin' => 'externalAdmin',
            'courier'        => 'courier',
        };
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'currency'   => 'nullable|string|max:10',
        ]);

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
            
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        $currency = $request->input('currency');
        $hasCurrencyFilter = !empty($currency) && $currency !== 'ALL';

        $tickets = ExchangeTicket::with(['client', 'atc'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($hasCurrencyFilter, fn($q) => $q->where('currency_from', $currency))
            ->orderBy('created_at', 'desc')
            ->get();

        $csvFileName = 'reporte_tickets_' . now()->format('Ymd_His') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($tickets) {
            $file = fopen('php://output', 'w');
            
            // BOM para que Excel lea los acentos correctamente
            fputs($file, "\xEF\xBB\xBF");
            
            $columns = [
                'Código', 'Fecha Creación', 'Estado', 'Cliente', 'ATC', 
                'Moneda', 'Monto Solicitado', 'Tasa', 'Monto a Entregar', 
                'GNB', 'Fecha Cierre'
            ];
            
            fputcsv($file, $columns, ';');

            foreach ($tickets as $ticket) {
                $row = [
                    $ticket->code,
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->status->label(),
                    $ticket->client ? $ticket->client->name : 'N/A',
                    $ticket->atc ? $ticket->atc->name : 'N/A',
                    $ticket->currency_from,
                    $ticket->amount_requested,
                    $ticket->exchange_rate,
                    $ticket->amount_to_deliver,
                    $ticket->gnb,
                    $ticket->closed_at ? $ticket->closed_at->format('Y-m-d H:i:s') : 'N/A'
                ];

                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
