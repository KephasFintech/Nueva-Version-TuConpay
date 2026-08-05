<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDistribution extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'beneficiary_role',
        'percentage',
        'amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount'     => 'decimal:6',
            'paid_at'    => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ExchangeTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
