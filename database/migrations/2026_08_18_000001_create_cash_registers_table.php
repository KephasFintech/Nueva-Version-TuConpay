<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Código de la caja');
            $table->foreignId('opened_by')->constrained('users')->comment('Quién abre la caja');
            $table->foreignId('closed_by')->nullable()->constrained('users')->comment('Quién cierra la caja');
            $table->decimal('opening_balance', 18, 6)->comment('Monto inicial');
            $table->decimal('closing_balance', 18, 6)->nullable()->comment('Monto físico al cierre');
            $table->string('currency', 10)->default('USD')->comment('Moneda de la caja');
            $table->decimal('expected_balance', 18, 6)->nullable()->comment('Monto calculado por sistema');
            $table->decimal('difference', 18, 6)->nullable()->comment('Diferencia entre físico y sistema');
            $table->string('status', 30)->default('open')->comment('open | closed');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
