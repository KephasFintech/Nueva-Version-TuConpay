<?php

namespace App\Models;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'type',
        'source',
        'reference_id',
        'amount',
        'currency',
        'description',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'source' => CashMovementSource::class,
            'amount' => 'decimal:6',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
