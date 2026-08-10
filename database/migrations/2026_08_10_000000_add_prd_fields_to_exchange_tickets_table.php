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
            $table->string('bridge_asset', 20)->default('USDT')->after('amount_requested')->comment('Fase E2: Activo puente');
            $table->decimal('bridge_amount', 18, 6)->nullable()->after('bridge_asset')->comment('Fase E2: Monto puente');
            
            $table->string('delivery_otp', 10)->nullable()->after('status')->comment('Fase E3: OTP para entrega');
            
            $table->timestamp('global_expires_at')->nullable()->after('expires_at')->comment('Fase E3: Vencimiento SLA Global (180 min)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_tickets', function (Blueprint $table) {
            $table->dropColumn(['bridge_asset', 'bridge_amount', 'delivery_otp', 'global_expires_at']);
        });
    }
};
