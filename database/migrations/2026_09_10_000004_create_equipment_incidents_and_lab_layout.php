<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo de vida del equipo y plano del laboratorio.
 *
 *  - equipment_incidents: incidencias reportadas por cualquier usuario y
 *    resueltas por el administrador (con trazabilidad de quien y cuando).
 *  - labs.grid_rows / grid_cols y equipment.grid_row / grid_col: la
 *    cuadricula del plano y la posicion de cada puesto, para el mapa con
 *    estado en tiempo real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['equipment_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('labs', function (Blueprint $table) {
            $table->unsignedTinyInteger('grid_rows')->default(0)->after('capacity');
            $table->unsignedTinyInteger('grid_cols')->default(0)->after('grid_rows');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedTinyInteger('grid_row')->nullable()->after('is_operational');
            $table->unsignedTinyInteger('grid_col')->nullable()->after('grid_row');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['grid_row', 'grid_col']);
        });

        Schema::table('labs', function (Blueprint $table) {
            $table->dropColumn(['grid_rows', 'grid_cols']);
        });

        Schema::dropIfExists('equipment_incidents');
    }
};
