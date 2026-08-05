<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('exchange_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('beneficiary_role', 30)->comment('atc | broker | provider | external_admin');
            $table->decimal('percentage', 5, 2)->comment('Porcentaje de GNB asignado');
            $table->decimal('amount', 18, 6)->comment('Monto calculado');
            $table->timestamp('paid_at')->nullable()->comment('Fecha de pago efectivo');
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_distributions');
    }
};
