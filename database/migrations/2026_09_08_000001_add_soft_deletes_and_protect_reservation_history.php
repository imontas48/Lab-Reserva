<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Protege el historial de reservas.
 *
 * reservations.user_id y reservations.equipment_id estaban declaradas con
 * onDelete('cascade'), asi que:
 *
 *  - dar de baja a un alumno graduado borraba todo su historial de uso de los
 *    laboratorios, sin aviso y sin recuperacion;
 *  - EquipmentService::deleteEquipment solo bloquea el borrado si hay reservas
 *    confirmadas futuras, de modo que retirar un equipo antiguo se llevaba por
 *    delante todas sus reservas pasadas.
 *
 * En un sistema de laboratorio universitario esa trazabilidad (quien uso que
 * equipo y cuando) es justamente el dato que hay que conservar.
 *
 * Se pasa a restrict y se anaden soft deletes: la baja normal de un usuario o
 * un equipo pasa a ser logica y no toca las claves foraneas; un borrado fisico
 * queda bloqueado mientras existan reservas asociadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['labs', 'software', 'equipment', 'reservations', 'users'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->softDeletes();
            });
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['equipment_id']);

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('equipment_id')->references('id')->on('equipment')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['equipment_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('equipment_id')->references('id')->on('equipment')->cascadeOnDelete();
        });

        foreach (['labs', 'software', 'equipment', 'reservations', 'users'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
