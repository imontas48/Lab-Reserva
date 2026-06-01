<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote: permisos asignados a cada rol.
     *
     * Resuelve la relación N:M entre roles y permisos.
     * No lleva timestamps porque es una asignación estructural, no un evento.
     *
     * Ejemplo:
     *   role_id=1 (admin) → permission_id=5 (labs.delete)
     *   role_id=2 (teacher) → permission_id=2 (reservations.create)
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            // Clave primaria compuesta: un rol no puede tener el mismo permiso dos veces
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
