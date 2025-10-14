<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id(); // BIGINT, UNSIGNED, PK, AUTO_INCREMENT
            $table->string('name')->unique(); // VARCHAR(255), UNIQUE
            $table->string('location'); // VARCHAR(255)
            $table->text('description')->nullable(); // TEXT, NULLABLE
            $table->boolean('is_active')->default(true); // BOOLEAN, DEFAULT(true)
            $table->timestamps(); // created_at, updated_at

            // Índice en is_active
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labs');
    }
};
