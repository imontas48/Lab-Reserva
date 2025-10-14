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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id(); // BIGINT, UNSIGNED, PK, AUTO_INCREMENT
            $table->foreignId('lab_id')->constrained('labs')->onDelete('restrict'); // FK a labs con borrado restringido
            $table->string('identifier'); // VARCHAR(255) - Identificador único del equipo
            $table->string('type', 100)->default('PC'); // VARCHAR(100), DEFAULT('PC')
            $table->text('specifications')->nullable(); // TEXT, NULLABLE
            $table->boolean('is_operational')->default(true); // BOOLEAN, DEFAULT(true)
            $table->timestamps(); // created_at, updated_at

            // Índice compuesto único en (lab_id, identifier)
            $table->unique(['lab_id', 'identifier']);

            // Índice en is_operational
            $table->index('is_operational');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
