<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ExchangeTicket;
use Symfony\Component\HttpFoundation\Response;

class EnforceFourAgentsAssigned
{
    public function handle(Request $request, Closure $next): Response
    {
        // Se espera que el parámetro de la ruta se llame 'exchange_ticket' o 'exchangeTicket'
        $ticketId = $request->route('exchange_ticket') ?? $request->route('exchangeTicket');
        
        if ($ticketId) {
            $ticket = $ticketId instanceof ExchangeTicket ? $ticketId : ExchangeTicket::find($ticketId);

            if ($ticket && !$ticket->hasRequiredAgentsAssigned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acción Bloqueada: No se puede liquidar el ticket sin los agentes obligatorios (Corredor, Motorizado, y si es flujo externo: Admin A1 y Proveedor P2).'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return $next($request);
    }
}
