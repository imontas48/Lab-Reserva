<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asignaciones de roles por grupo de usuarios.
     *
     * Permite asignar un rol automáticamente a todos los usuarios que
     * pertenezcan a un grupo determinado, sin necesidad de hacer
     * asignaciones individuales.
     *
     * DISEÑO EXTENSIBLE — group_type / group_value:
     * ┌─────────────────┬──────────────────────────────────────────────────┐
     * │ group_type      │ group_value (ejemplos)                           │
     * ├─────────────────┼──────────────────────────────────────────────────┤
     * │ user_type       │ admin, teacher, student                          │
     * │ (futuro)        │ Se pueden añadir más tipos sin alterar el esquema │
     * └─────────────────┴──────────────────────────────────────────────────┘
     *
     * Ejemplo de uso:
     *   group_type='user_type', group_value='student', role_id=3
     *   → Todos los usuarios con users.role='student' obtienen el rol #3.
     *
     * RESOLUCIÓN: Al consultar los permisos de un usuario, el sistema debe
     * unir los roles de esta tabla cuyo (group_type='user_type' AND
     * group_value = users.role) con los roles de user_roles.
     */
    public function up(): void
    {
        Schema::create('group_role_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            // Tipo de agrupación. Actualmente solo 'user_type'; preparado para extensión.
            $table->string('group_type', 64);

            // Valor del grupo (ej. 'student', 'teacher', 'admin')
            $table->string('group_value', 64);

            // Usuario administrador que creó la regla (nullable = regla del sistema)
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Permite desactivar la regla sin eliminarla
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Una regla de grupo es única por (rol, tipo de grupo, valor del grupo)
            $table->unique(['role_id', 'group_type', 'group_value']);

            $table->index(['group_type', 'group_value', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_role_assignments');
    }
};
