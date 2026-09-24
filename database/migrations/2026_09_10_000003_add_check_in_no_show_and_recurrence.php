<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Check-in, no-show y series recurrentes.
 *
 *  - check_in_code se genera al confirmar una reserva; una reserva sin
 *    codigo (datos antiguos) no exige check-in y se completa como siempre.
 *  - checked_in_at / no_show_at registran la asistencia real. El estado
 *    no_show es terminal y libera la franja.
 *  - recurrence_group agrupa las ocurrencias de una serie semanal.
 *  - users.no_show_count y reservation_blocked_until sostienen la politica
 *    de reincidencia (config/lab-reserva.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'rejected', 'expired', 'no_show') NOT NULL DEFAULT 'confirmed'");

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('check_in_code', 8)->nullable()->after('reminder_sent_at');
            $table->dateTime('checked_in_at')->nullable()->after('check_in_code');
            $table->dateTime('no_show_at')->nullable()->after('checked_in_at');
            $table->char('recurrence_group', 36)->nullable()->after('no_show_at');

            $table->index('recurrence_group', 'reservations_recurrence_group_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('no_show_count')->default(0)->after('role');
            $table->dateTime('reservation_blocked_until')->nullable()->after('no_show_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['no_show_count', 'reservation_blocked_until']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_recurrence_group_index');
            $table->dropColumn(['check_in_code', 'checked_in_at', 'no_show_at', 'recurrence_group']);
        });

        DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'rejected', 'expired') NOT NULL DEFAULT 'confirmed'");
    }
};
