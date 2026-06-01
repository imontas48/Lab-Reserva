<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sobreescrituras de permisos por usuario (excepciones individuales).
     *
     * Permite afinar los permisos de un usuario concreto fuera del sistema
     * de roles. Hay dos tipos de sobreescritura:
     *
     *   GRANT  → El usuario obtiene este permiso aunque ninguno de sus roles
     *            lo incluya. Útil para dar acceso puntual sin crear un rol nuevo.
     *
     *   REVOKE → El usuario pierde este permiso aunque alguno de sus roles
     *            lo incluya. Útil para restringir sin alterar el rol completo.
     *
     * PRIORIDAD de resolución (de mayor a menor):
     *   1. permission_overrides (type=revoke)  ← mayor prioridad
     *   2. permission_overrides (type=grant)
     *   3. Permisos acumulados desde roles (user_roles + group_role_assignments)
     *
     * Un usuario no puede tener dos sobreescrituras distintas para el mismo
     * permiso (la constraint UNIQUE lo garantiza).
     */
    public function up(): void
    {
        Schema::create('permission_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            // 'grant' = conceder permiso extra | 'revoke' = quitar permiso del rol
            $table->enum('type', ['grant', 'revoke']);

            // Razón del override (para auditoría y transparencia)
            $table->string('reason', 255)->nullable();

            // Usuario administrador que aplicó la sobreescritura
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // null = sobreescritura permanente; fecha = expira en esa fecha
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Un usuario solo puede tener una sobreescritura por permiso
            $table->unique(['user_id', 'permission_id']);

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_overrides');
    }
};
