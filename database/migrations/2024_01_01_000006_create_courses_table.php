<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('contenido')->nullable();
            $table->enum('carga_horaria', ['20', '30']);
            $table->string('nivel')->nullable();
            $table->string('periodo')->nullable();
            $table->enum('status', [
                'en_preparacion',
                'publicado',
                'preinscripcion_cerrada',
                'en_curso',
                'finalizado',
                'cancelado',
            ])->default('en_preparacion');
            $table->decimal('precio_umss', 8, 2)->default(0);
            $table->decimal('precio_externo', 8, 2)->default(0);
            $table->decimal('precio_auxiliar', 8, 2)->default(0);
            $table->foreignId('instructor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
