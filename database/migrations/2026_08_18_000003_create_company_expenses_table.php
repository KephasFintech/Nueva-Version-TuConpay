<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->comment('Categoría libre (ej: Alquiler, Sueldos, etc.)');
            $table->decimal('amount', 18, 6);
            $table->string('currency', 10)->default('USD');
            $table->text('description')->nullable();
            $table->date('expense_date');
            $table->string('receipt_path')->nullable();
            $table->foreignId('registered_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_expenses');
    }
};
