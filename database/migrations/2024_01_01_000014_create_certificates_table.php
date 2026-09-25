<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preinscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['aprobacion', 'asistencia']);
            $table->string('codigo_unico')->unique()->index();
            $table->string('pdf_path')->nullable();
            $table->enum('signature_status', [
                'pendiente',
                'firmado_jefe_depto',
                'firmado_director_academico',
                'firmado_decano',
                'listo',
            ])->default('pendiente');
            $table->timestamp('emitido_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
