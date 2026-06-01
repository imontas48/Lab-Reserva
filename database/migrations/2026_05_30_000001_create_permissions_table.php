<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de permisos atómicos del sistema.
     *
     * Cada permiso se identifica de forma única por la combinación (subject, action).
     *
     * SUBJECTS (recursos del sistema):
     *   labs, equipment, software, reservations, users, roles, reports, system
     *
     * ACTIONS (verbos):
     *   create, read, update, delete, manage, cancel, export, assign
     *
     * La combinación sigue el patrón CASL/CanCan:
     *   "Puede [action] sobre [subject]"
     *   Ej: subject=reservations, action=cancel → "Puede cancelar reservas"
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            // Recurso del sistema al que aplica el permiso
            $table->string('subject', 64);

            // Acción que se permite sobre ese recurso
            $table->string('action', 64);

            // Descripción legible para la UI de administración
            $table->string('description', 255);

            $table->timestamps();

            // Un permiso es único por la combinación (subject, action)
            $table->unique(['subject', 'action']);

            // Índice para búsquedas por recurso
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
