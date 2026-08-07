<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exchange_tickets', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 6)->nullable()->comment('Tasa de cambio aplicada')->change();
            $table->decimal('amount_to_deliver', 18, 6)->nullable()->comment('Monto final a entregar')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_tickets', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 6)->nullable(false)->comment('Tasa de cambio aplicada')->change();
            $table->decimal('amount_to_deliver', 18, 6)->nullable(false)->comment('Monto final a entregar')->change();
        });
    }
};
