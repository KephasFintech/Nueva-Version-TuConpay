<?php

namespace App\Models;

use App\Enums\CashRegisterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'opened_by',
        'closed_by',
        'opening_balance',
        'closing_balance',
        'currency',
        'expected_balance',
        'difference',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CashRegisterStatus::class,
            'opening_balance' => 'decimal:6',
            'closing_balance' => 'decimal:6',
            'expected_balance' => 'decimal:6',
            'difference' => 'decimal:6',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'cash_register_id');
    }

    public static function generateCode(): string
    {
        $prefix = 'CX';
        $year   = now()->year;
        $count  = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }

    public function calculateExpectedBalance(): float
    {
        $incomes = $this->movements()->where('type', \App\Enums\CashMovementType::INCOME->value)->sum('amount');
        $expenses = $this->movements()->where('type', \App\Enums\CashMovementType::EXPENSE->value)->sum('amount');

        // Note: opening_balance is already registered as an INCOME movement upon opening, 
        // so we don't need to add it twice if we query all incomes.
        // Actually, to be safe, let's just sum incomes and subtract expenses.
        
        return round((float) $incomes - (float) $expenses, 6);
    }
}
