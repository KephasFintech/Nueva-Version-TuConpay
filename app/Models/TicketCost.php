<?php

namespace App\Models;

use App\Enums\CostType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCost extends Model
{
    protected $fillable = [
        'ticket_id',
        'cost_type',
        'amount',
        'currency',
        'description',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'cost_type' => CostType::class,
            'amount'    => 'decimal:6',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ExchangeTicket::class, 'ticket_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
