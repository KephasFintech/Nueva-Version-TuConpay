<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'amount',
        'currency',
        'description',
        'expense_date',
        'receipt_path',
        'registered_by',
        'approved_by',
        'approved_at',
        'cash_register_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:6',
            'expense_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }
}
