<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asignaciones individuales de roles a usuarios.
     *
     * Permite asignar un rol específico a un usuario concreto, con:
     * - Trazabilidad: quién otorgó el rol (granted_by)
     * - Temporalidad: el rol puede expirar (expires_at)
     *
     * Un usuario puede tener múltiples roles simultáneamente.
     * Un mismo rol no puede asignarse dos veces al mismo usuario
     * (la constraint UNIQUE lo previene; si el rol expiró y se quiere
     * reactivar, se debe actualizar el registro existente).
     *
     * RESOLUCIÓN DE PERMISOS:
     *   Los permisos efectivos de un usuario = UNIÓN de:
     *     1. Permisos de sus roles por grupo (group_role_assignments)
     *     2. Permisos de sus roles individuales (user_roles) no expirados
     *     3. Permisos adicionales o revocados vía permission_overrides
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            // Usuario administrador que realizó la asignación (nullable = asignación automática por sistema)
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // null = el rol no expira; fecha futura = expira en esa fecha
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Un usuario no puede tener el mismo rol asignado dos veces
            $table->unique(['user_id', 'role_id']);

            // Índice para consultar roles vigentes rápidamente
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
