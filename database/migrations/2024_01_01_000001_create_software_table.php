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
        Schema::create('software', function (Blueprint $table) {
            $table->id(); // BIGINT, UNSIGNED, PK, AUTO_INCREMENT
            $table->string('name')->unique(); // VARCHAR(255), UNIQUE
            $table->string('version', 50)->nullable(); // VARCHAR(50), NULLABLE
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software');
    }
};
