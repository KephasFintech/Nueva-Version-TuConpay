<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'uploader_id',
        'file_path',
        'original_name',
        'file_type',
        'file_size',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ExchangeTicket::class, 'ticket_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * URL pública del archivo (usando el disco configurado en Laravel Storage).
     */
    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::url($this->file_path);
    }
}
