<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajusta los índices a las consultas que el código hace de verdad.
 *
 * reservations tenía tres índices de UNA sola columna (start_time, end_time,
 * status), pero ninguna consulta real filtra por una sola: todas combinan el
 * equipo, el estado y un rango temporal. MySQL solo podía apoyarse en el índice
 * implícito de la clave foránea y filtrar el resto en memoria.
 *
 * Es el camino caliente del sistema: se recorre en cada creación de reserva
 * (dentro de la transacción y con el equipo bloqueado, así que su coste alarga
 * la sección crítica) y en cada consulta de disponibilidad del calendario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Cubre la detección de solapamiento: equipo + estado + rango.
            $table->index(
                ['equipment_id', 'status', 'start_time', 'end_time'],
                'reservations_availability_index'
            );

            // Cubre "mis reservas", que además ordena por start_time.
            $table->index(
                ['user_id', 'status', 'start_time'],
                'reservations_user_timeline_index'
            );

            // status por sí solo tiene tres valores posibles: como índice
            // independiente no descarta casi nada, y ahora es la segunda
            // columna de los dos índices de arriba.
            $table->dropIndex('reservations_status_index');
        });

        Schema::table('equipment', function (Blueprint $table) {
            // Se filtra por tipo en el listado y se hace SELECT DISTINCT type
            // para poblar el desplegable; sin índice, ambas recorren la tabla.
            $table->index('type', 'equipment_type_index');
        });

        Schema::table('permissions', function (Blueprint $table) {
            // Prefijo izquierdo exacto de permissions_subject_action_unique:
            // no aporta nada y encarece cada escritura.
            $table->dropIndex('permissions_subject_index');
        });

        Schema::table('permission_overrides', function (Blueprint $table) {
            // El código nunca filtra por type en SQL: separa grants y revokes
            // en memoria sobre la colección ya cargada. Y user_id como prefijo
            // ya lo cubre el índice único.
            $table->dropIndex('permission_overrides_user_id_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('permission_overrides', function (Blueprint $table) {
            $table->index(['user_id', 'type'], 'permission_overrides_user_id_type_index');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->index('subject', 'permissions_subject_index');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex('equipment_type_index');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('status', 'reservations_status_index');

            // InnoDB descarta el índice que crea implícitamente para una clave
            // foránea en cuanto otro lo cubre por prefijo izquierdo, de modo que
            // al añadir los compuestos absorbió reservations_user_id_foreign y
            // reservations_equipment_id_foreign. Hay que devolverlos ANTES de
            // borrar los compuestos: si no, MySQL rechaza el DROP con
            // "needed in a foreign key constraint".
            $table->index('user_id', 'reservations_user_id_foreign');
            $table->index('equipment_id', 'reservations_equipment_id_foreign');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_user_timeline_index');
            $table->dropIndex('reservations_availability_index');
        });
    }
};
