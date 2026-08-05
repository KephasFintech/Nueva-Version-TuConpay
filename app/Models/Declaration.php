<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Declaration extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'type',
        'file_path',
        'form_data',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'signed_at' => 'datetime',
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
