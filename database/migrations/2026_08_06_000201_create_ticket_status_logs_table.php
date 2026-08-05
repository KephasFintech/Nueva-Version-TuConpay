<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('exchange_tickets')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable()->comment('Estado anterior');
            $table->string('to_status', 30)->comment('Estado nuevo');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario que realizó el cambio');
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable()->comment('Motivo o notas del cambio');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_status_logs');
    }
};
