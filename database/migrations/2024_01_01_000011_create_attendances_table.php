<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->foreignId('preinscription_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['presente', 'ausente', 'justificado'])->default('ausente');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('distancia_metros', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'preinscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
