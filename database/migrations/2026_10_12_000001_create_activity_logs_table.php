<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de actividades: quien hizo que, cuando y desde donde.
 *
 * Es de solo anexado. No tiene updated_at ni soft deletes, y la aplicacion no
 * expone ninguna ruta para modificarlo ni borrarlo (ver App\Models\ActivityLog).
 *
 * actor_name, actor_email y subject_label son copias del momento y no se
 * resuelven por relacion: el registro tiene que seguir diciendo lo mismo
 * aunque luego se renombre el laboratorio o se dé de baja al usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('event', 40);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->string('description', 500);
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('source', ['web', 'console'])->default('web');
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['actor_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
