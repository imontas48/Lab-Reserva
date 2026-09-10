<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reserva de laboratorio completo y flujo de aprobacion.
 *
 * Hasta ahora una reserva solo podia apuntar a un equipo, de modo que un
 * profesor no tenia forma de apartar un laboratorio entero para dar una
 * clase. Se extiende la misma tabla en vez de crear otra o una relacion
 * polimorfica: asi se conserva la unica definicion de solapamiento
 * (Reservation::scopeOverlapping), el indice caliente de disponibilidad, una
 * sola maquina de estados y un solo listado de "mis reservas".
 *
 *  - type distingue el objetivo (equipment | lab); equipment_id pasa a ser
 *    nullable y aparece lab_id. Un CHECK garantiza que exactamente uno de los
 *    dos esta informado segun el tipo.
 *  - status gana pending (solicitud sin resolver), rejected (decision humana
 *    con motivo) y expired (la franja llego sin decision).
 *  - purpose, rejection_reason, reviewed_by y reviewed_at dan trazabilidad
 *    a la solicitud y a su resolucion.
 *
 * down() falla a proposito si existen reservas de laboratorio o en estados
 * nuevos: revertir no debe destruir historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MODIFY directo: change() sobre un ENUM es fragil y reescribe la
        // columna con un tipo distinto segun la version del driver.
        DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'rejected', 'expired') NOT NULL DEFAULT 'confirmed'");

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
        });

        DB::statement('ALTER TABLE reservations MODIFY equipment_id BIGINT UNSIGNED NULL');

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('equipment_id')->references('id')->on('equipment')->restrictOnDelete();

            $table->enum('type', ['equipment', 'lab'])->default('equipment')->after('user_id');
            $table->foreignId('lab_id')->nullable()->after('equipment_id')
                ->constrained('labs')->restrictOnDelete();
            $table->string('purpose', 255)->nullable()->after('status');
            $table->string('rejection_reason', 500)->nullable()->after('purpose');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');

            // Espejo de reservations_availability_index para la rama de
            // laboratorio de la comprobacion cruzada de conflictos.
            $table->index(
                ['lab_id', 'status', 'start_time', 'end_time'],
                'reservations_lab_availability_index'
            );
        });

        // Red de seguridad en el esquema (MySQL >= 8.0.16 la aplica; versiones
        // anteriores la ignoran). La garantia real vive en los FormRequests y
        // en ReservationService.
        DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_target_check CHECK ((type = 'equipment' AND equipment_id IS NOT NULL AND lab_id IS NULL) OR (type = 'lab' AND lab_id IS NOT NULL AND equipment_id IS NULL))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reservations DROP CHECK reservations_target_check');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['lab_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex('reservations_lab_availability_index');
            $table->dropColumn(['type', 'lab_id', 'purpose', 'rejection_reason', 'reviewed_by', 'reviewed_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
        });

        DB::statement('ALTER TABLE reservations MODIFY equipment_id BIGINT UNSIGNED NOT NULL');

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('equipment_id')->references('id')->on('equipment')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE reservations MODIFY status ENUM('confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'confirmed'");
    }
};
