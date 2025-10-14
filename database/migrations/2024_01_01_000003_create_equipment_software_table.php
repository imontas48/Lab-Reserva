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
        Schema::create('equipment_software', function (Blueprint $table) {
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade'); // FK a equipment con borrado en cascada
            $table->foreignId('software_id')->constrained('software')->onDelete('cascade'); // FK a software con borrado en cascada

            // Clave primaria compuesta
            $table->primary(['equipment_id', 'software_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_software');
    }
};
