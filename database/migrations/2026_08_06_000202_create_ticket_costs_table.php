<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('exchange_tickets')->cascadeOnDelete();
            $table->string('cost_type', 30)->comment('admin1 | provider | logistics | other');
            $table->decimal('amount', 18, 6);
            $table->string('currency', 10)->default('USD');
            $table->text('description')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_costs');
    }
};
