<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horario de apertura por laboratorio, cierres puntuales y periodos
 * academicos.
 *
 * Hasta ahora se podia reservar a cualquier hora de cualquier dia. Un
 * laboratorio sin filas en lab_opening_hours sigue sin restriccion horaria
 * (compatibilidad con los datos existentes); en cuanto tiene alguna, solo se
 * puede reservar dentro de esas franjas. Un cierre con lab_id nulo aplica a
 * todos los laboratorios (festivos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            // 0 = domingo ... 6 = sabado, como Carbon::dayOfWeek.
            $table->unsignedTinyInteger('weekday');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();

            $table->unique(['lab_id', 'weekday']);
        });

        Schema::create('lab_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->nullable()->constrained('labs')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lab_id', 'starts_at', 'ends_at'], 'lab_closures_window_index');
        });

        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dateTime('reminder_sent_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('lab_closures');
        Schema::dropIfExists('lab_opening_hours');
    }
};
