<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('exchange_tickets')->cascadeOnDelete();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path')->comment('Ruta relativa en el disco de almacenamiento');
            $table->string('original_name')->nullable();
            $table->string('file_type', 20)->nullable()->comment('image/jpeg, application/pdf, etc.');
            $table->unsignedBigInteger('file_size')->nullable()->comment('Tamaño en bytes');
            $table->timestamp('verified_at')->nullable()->comment('Cuándo fue verificado por un operador');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
