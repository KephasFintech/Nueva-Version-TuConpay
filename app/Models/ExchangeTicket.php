<?php

namespace App\Models;

use App\Enums\RateType;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeTicket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'atc_user_id',
        'client_id',
        'broker_id',
        'external_admin_id',
        'provider_id',
        'courier_id',
        'currency_from',
        'currency_to',
        'amount_requested',
        'rate_type',
        'exchange_rate',
        'amount_to_deliver',
        'bridge_asset',
        'bridge_amount',
        'status',
        'delivery_otp',
        'expires_at',
        'global_expires_at',
        'sla_alerted_at',
        'closed_at',
        'gnb',
        'metadata',
        'notes',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status'          => TicketStatus::class,
            'rate_type'       => RateType::class,
            'expires_at'      => 'datetime',
            'global_expires_at' => 'datetime',
            'sla_alerted_at'  => 'datetime',
            'closed_at'       => 'datetime',
            'amount_requested' => 'decimal:6',
            'bridge_amount'   => 'decimal:6',
            'exchange_rate'   => 'decimal:6',
            'amount_to_deliver' => 'decimal:6',
            'gnb'             => 'decimal:6',
            'metadata'        => 'array',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function atc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atc_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    public function externalAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'external_admin_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TicketStatusLog::class, 'ticket_id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(TicketCost::class, 'ticket_id');
    }

    public function distribution(): HasMany
    {
        return $this->hasMany(TicketDistribution::class, 'ticket_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'ticket_id');
    }

    public function declarations(): HasMany
    {
        return $this->hasMany(Declaration::class, 'ticket_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            TicketStatus::CLOSED->value,
            TicketStatus::CANCELLED->value,
            TicketStatus::CANCELLED_BY_TIMEOUT->value,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereIn('status', [
                TicketStatus::DRAFT->value,
                TicketStatus::WAITING_PAYMENT->value,
            ]);
    }

    public function scopeAwaitingSlaAlert($query)
    {
        $threshold = config('exchange.sla.alert_threshold_minutes', 30);

        return $query->whereNull('sla_alerted_at')
            ->whereIn('status', [
                TicketStatus::DRAFT->value,
                TicketStatus::WAITING_PAYMENT->value,
            ])
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addMinutes($threshold));
    }

    public function scopeGlobalOverdue($query)
    {
        return $query->where('global_expires_at', '<', now())
            ->whereNotIn('status', [
                TicketStatus::CLOSED->value,
                TicketStatus::CANCELLED->value,
                TicketStatus::CANCELLED_BY_TIMEOUT->value,
            ]);
    }

    public function scopeByStatus($query, TicketStatus|string $status)
    {
        $value = $status instanceof TicketStatus ? $status->value : $status;
        return $query->where('status', $value);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Genera el siguiente código de ticket: TC-YYYY-NNNN.
     */
    public static function generateCode(): string
    {
        $prefix = config('exchange.ticket_prefix', 'TC');
        $year   = now()->year;
        $count  = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }

    /**
     * Retorna el timestamp de vencimiento del SLA.
     */
    public static function calculateExpiresAt(): \Illuminate\Support\Carbon
    {
        $minutes = config('exchange.sla.duration_minutes', 90);
        return now()->addMinutes($minutes);
    }

    /**
     * Calcula el monto a entregar basado en el tipo de tasa (rate_type) y la tasa (exchange_rate).
     */
    public function calculateAmountToDeliver(): ?float
    {
        if ($this->exchange_rate === null || $this->amount_requested === null) {
            return null;
        }

        $type = $this->rate_type ?? RateType::FIXED;

        if ($type === RateType::PERCENTAGE) {
            return round((float) $this->amount_requested * ((float) $this->exchange_rate / 100), 6);
        }

        return round((float) $this->amount_requested * (float) $this->exchange_rate, 6);
    }

    /**
     * Verifica que los agentes requeridos estén asignados al ticket.
     * Si es un flujo externo (Dirección), requiere Admin A1 y Proveedor P2.
     * Si es un flujo interno (Administración), no los requiere.
     * En ambos casos, se requiere Motorizado y Broker.
     */
    public function hasRequiredAgentsAssigned(): bool
    {
        // Broker y Motorizado siempre son obligatorios operativamente
        if ($this->broker_id === null || $this->courier_id === null) {
            return false;
        }

        // Si se asignó un Administrador Externo (A1), entonces es un flujo de capital externo (Dirección).
        // En este caso, también es obligatorio el Proveedor (P2/P3).
        if ($this->external_admin_id !== null) {
            return $this->provider_id !== null;
        }

        // Si no hay Admin Externo, es flujo interno (capital propio de la empresa).
        return true;
    }
}
