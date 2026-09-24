<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de "contrasena temporal": la cuenta la creo un administrador con una
 * contrasena provisional y el usuario tiene que sustituirla en su primer
 * inicio de sesion antes de usar nada mas. Las cuentas del registro publico
 * nunca la llevan, porque ahi la contrasena la elige el propio usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
