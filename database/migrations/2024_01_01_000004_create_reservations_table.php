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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id(); // BIGINT, UNSIGNED, PK, AUTO_INCREMENT
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // FK a users con borrado en cascada
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade'); // FK a equipment con borrado en cascada
            $table->dateTime('start_time'); // DATETIME - Momento de inicio de la reserva
            $table->dateTime('end_time'); // DATETIME - Momento de fin de la reserva
            $table->enum('status', ['confirmed', 'cancelled', 'completed'])->default('confirmed'); // ENUM con DEFAULT
            $table->timestamps(); // created_at, updated_at

            // Índices en start_time y end_time para optimizar búsquedas por rangos de fechas
            $table->index('start_time');
            $table->index('end_time');

            // Índice en status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
