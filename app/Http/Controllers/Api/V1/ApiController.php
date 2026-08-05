<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Controlador base para la API v1.
 * Provee métodos estandarizados de respuesta JSON.
 */
abstract class ApiController extends Controller
{
    /**
     * Respuesta exitosa estándar.
     *
     * @param  mixed  $data
     * @param  string $message
     * @param  int    $status
     */
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Respuesta de error estándar.
     */
    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Respuesta con paginación estándar.
     */
    protected function paginate(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Respuesta 201 Created.
     *
     * @param  mixed $data
     */
    protected function created(mixed $data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Respuesta 204 No Content.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
