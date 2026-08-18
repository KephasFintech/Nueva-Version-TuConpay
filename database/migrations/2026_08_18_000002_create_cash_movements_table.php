<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->string('type', 20)->comment('income | expense');
            $table->string('source', 50)->comment('ticket | manual_income | company_expense | adjustment | opening');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID relacionado (Ticket o CompanyExpense)');
            $table->decimal('amount', 18, 6);
            $table->string('currency', 10);
            $table->text('description')->nullable();
            $table->foreignId('registered_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
