<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Código legible: TC-2024-001');

            // Agentes involucrados
            $table->foreignId('atc_user_id')->constrained('users')->comment('Agente de Taquilla que creó el ticket');
            $table->foreignId('client_id')->constrained('users')->comment('Cliente beneficiario');
            $table->foreignId('broker_id')->nullable()->constrained('users')->comment('Broker asignado');
            $table->foreignId('external_admin_id')->nullable()->constrained('users')->comment('Administrador Externo (A1)');
            $table->foreignId('provider_id')->nullable()->constrained('users')->comment('Proveedor (P2/P3)');
            $table->foreignId('courier_id')->nullable()->constrained('users')->comment('Motorizado / Logística');

            // Datos del cambio
            $table->string('currency_from', 10)->comment('Divisa origen (USD, EUR, etc.)');
            $table->string('currency_to', 10)->comment('Divisa destino');
            $table->decimal('amount_requested', 18, 6)->comment('Monto que solicita el cliente');
            $table->decimal('exchange_rate', 18, 6)->comment('Tasa de cambio aplicada');
            $table->decimal('amount_to_deliver', 18, 6)->comment('Monto final a entregar');

            // Estado y flujo
            $table->string('status', 30)->default('draft')->comment('Estado actual del ticket');

            // SLA (Acuerdo de Nivel de Servicio)
            $table->timestamp('expires_at')->nullable()->comment('Vencimiento del SLA (90 min desde creación)');
            $table->timestamp('sla_alerted_at')->nullable()->comment('Cuándo se lanzó la alerta de SLA');

            // Cierre
            $table->timestamp('closed_at')->nullable();
            $table->decimal('gnb', 18, 6)->nullable()->comment('Ganancia Neta Bruta calculada al cierre');

            // Metadata adicional
            $table->json('metadata')->nullable()->comment('IP, canal de origen, notas, etc.');
            $table->text('notes')->nullable()->comment('Notas internas del operador');

            $table->timestamps();
            $table->softDeletes();

            // Índices de búsqueda frecuente
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_tickets');
    }
};
