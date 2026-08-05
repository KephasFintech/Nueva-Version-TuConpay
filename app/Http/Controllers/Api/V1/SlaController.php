<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExchangeTicket;
use App\Services\SlaService;
use Illuminate\Http\JsonResponse;

class SlaController extends ApiController
{
    public function __construct(
        private readonly SlaService $slaService
    ) {}

    /**
     * Consultar estado del SLA de un ticket
     */
    public function show(ExchangeTicket $exchangeTicket): JsonResponse
    {
        return $this->success(
            $this->slaService->getSlaData($exchangeTicket),
            'Estado de SLA'
        );
    }
}
