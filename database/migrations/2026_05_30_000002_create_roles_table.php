<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de roles del sistema.
     *
     * Los roles agrupan conjuntos de permisos y se asignan a usuarios
     * ya sea individualmente (user_roles) o por grupo (group_role_assignments).
     *
     * IMPORTANTE: Los roles con is_system = true son roles base del sistema
     * (admin, teacher, student) y NO deben eliminarse ni renombrarse desde
     * la UI, ya que el campo users.role depende de sus slugs.
     *
     * El campo `color` es un nombre semántico de Tailwind (blue, green, red,
     * yellow, purple, orange) usado por la UI para renderizar los badges.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            // Slug único, usado como identificador en código (ej. 'lab_manager')
            $table->string('name', 64)->unique();

            // Nombre legible para mostrar en la UI (ej. 'Gestor de Laboratorio')
            $table->string('display_name', 128);

            // Descripción del propósito del rol
            $table->text('description')->nullable();

            // Color semántico para el badge de la UI (blue, green, red, yellow, purple, orange)
            $table->string('color', 32)->default('blue');

            // Los roles de sistema no se pueden eliminar desde la UI
            $table->boolean('is_system')->default(false);

            // Permite desactivar un rol sin eliminarlo
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
