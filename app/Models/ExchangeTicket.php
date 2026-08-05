<?php

namespace App\Models;

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
        'exchange_rate',
        'amount_to_deliver',
        'status',
        'expires_at',
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
            'expires_at'      => 'datetime',
            'sla_alerted_at'  => 'datetime',
            'closed_at'       => 'datetime',
            'amount_requested' => 'decimal:6',
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
}
