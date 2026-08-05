<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza todas las respuestas de la API a ser JSON.
 * Previene que Laravel devuelva HTML en errores (404, 401, 500, etc.)
 * cuando el cliente no envía Accept: application/json.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
