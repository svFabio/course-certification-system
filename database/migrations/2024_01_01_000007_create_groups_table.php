<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('cupo_minimo')->default(15);
            $table->integer('cupo_maximo');
            $table->enum('status', ['habilitado', 'no_habilitado', 'cerrado'])->default('habilitado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
