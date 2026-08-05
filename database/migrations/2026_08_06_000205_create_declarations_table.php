<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('exchange_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Quién firmó la declaración');
            $table->string('type', 50)->comment('Tipo de declaración jurada');
            $table->string('file_path')->nullable()->comment('PDF de la declaración');
            $table->json('form_data')->nullable()->comment('Datos del formulario en caso de no requerir PDF');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('declarations');
    }
};
