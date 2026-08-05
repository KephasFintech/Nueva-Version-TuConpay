<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ExchangeTicket;
use App\Models\TicketDistribution;
use Illuminate\Support\Facades\DB;

/**
 * Calculadora de Ganancia Neta Bruta (GNB) y distribución de ganancias.
 *
 * Fórmula:
 *   GNB = amount_to_deliver - SUM(costs registrados en ticket_costs)
 *
 * Distribución por defecto (configurable en config/exchange.php):
 *   ATC / Oficina  : 25%
 *   Broker         : 10%
 *   Provider (P2)  : 30%
 *   External Admin : 35%
 */
class ProfitCalculatorService
{
    /**
     * @param  array<string, int>  $distributionPercentages  ['atc' => 25, 'broker' => 10, ...]
     */
    public function __construct(
        private readonly array $distributionPercentages
    ) {}

    /**
     * Verifica que el ticket tiene todos los agentes requeridos antes de calcular.
     *
     * @throws \RuntimeException
     */
    public function validateRequirements(ExchangeTicket $ticket): void
    {
        $missing = [];

        if (! $ticket->atc_user_id) {
            $missing[] = UserRole::ATC->label();
        }

        if (! $ticket->broker_id) {
            $missing[] = UserRole::BROKER->label();
        }

        if (! $ticket->provider_id) {
            $missing[] = UserRole::PROVIDER->label();
        }

        if (! $ticket->external_admin_id) {
            $missing[] = UserRole::EXTERNAL_ADMIN->label();
        }

        if (! empty($missing)) {
            throw new \RuntimeException(
                'No se puede cerrar el ticket. Faltan los siguientes agentes asignados: ' .
                implode(', ', $missing)
            );
        }
    }

    /**
     * Calcula el GNB a partir del monto entregado y los costos registrados.
     */
    public function calculateGnb(ExchangeTicket $ticket): float
    {
        $totalCosts = (float) $ticket->costs()->sum('amount');
        return max(0, (float) $ticket->amount_to_deliver - $totalCosts);
    }

    /**
     * Calcula y persiste la distribución de ganancias del ticket.
     * Retorna el array con el detalle del reparto.
     *
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function calculate(ExchangeTicket $ticket): array
    {
        $this->validateRequirements($ticket);

        $gnb = $this->calculateGnb($ticket);

        $distribution = [
            'atc' => [
                'role'       => UserRole::ATC->value,
                'label'      => UserRole::ATC->label(),
                'user_id'    => $ticket->atc_user_id,
                'percentage' => $this->distributionPercentages['atc'],
                'amount'     => $this->applyPercentage($gnb, $this->distributionPercentages['atc']),
            ],
            'broker' => [
                'role'       => UserRole::BROKER->value,
                'label'      => UserRole::BROKER->label(),
                'user_id'    => $ticket->broker_id,
                'percentage' => $this->distributionPercentages['broker'],
                'amount'     => $this->applyPercentage($gnb, $this->distributionPercentages['broker']),
            ],
            'provider' => [
                'role'       => UserRole::PROVIDER->value,
                'label'      => UserRole::PROVIDER->label(),
                'user_id'    => $ticket->provider_id,
                'percentage' => $this->distributionPercentages['provider'],
                'amount'     => $this->applyPercentage($gnb, $this->distributionPercentages['provider']),
            ],
            'external_admin' => [
                'role'       => UserRole::EXTERNAL_ADMIN->value,
                'label'      => UserRole::EXTERNAL_ADMIN->label(),
                'user_id'    => $ticket->external_admin_id,
                'percentage' => $this->distributionPercentages['external_admin'],
                'amount'     => $this->applyPercentage($gnb, $this->distributionPercentages['external_admin']),
            ],
        ];

        // Persistir en base de datos
        DB::transaction(function () use ($ticket, $gnb, $distribution) {
            // Eliminar distribución previa si existía
            $ticket->distribution()->delete();

            foreach ($distribution as $entry) {
                TicketDistribution::create([
                    'ticket_id'         => $ticket->id,
                    'user_id'           => $entry['user_id'],
                    'beneficiary_role'  => $entry['role'],
                    'percentage'        => $entry['percentage'],
                    'amount'            => $entry['amount'],
                ]);
            }

            // Actualizar GNB en el ticket
            $ticket->update(['gnb' => $gnb]);
        });

        return [
            'gnb'          => $gnb,
            'distribution' => array_values($distribution),
            'total_costs'  => (float) $ticket->costs()->sum('amount'),
            'total_distributed' => array_sum(array_column($distribution, 'amount')),
        ];
    }

    /**
     * Aplica un porcentaje a un monto con 6 decimales de precisión.
     */
    private function applyPercentage(float $amount, int $percentage): float
    {
        return round($amount * $percentage / 100, 6);
    }
}
