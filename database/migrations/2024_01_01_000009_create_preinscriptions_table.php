<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preinscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('ci');
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('celular')->nullable();
            $table->string('email');
            $table->enum('tipo_participante', ['umss', 'externo', 'auxiliar']);
            $table->enum('status', ['pendiente_pago', 'inscrito', 'retirado'])->default('pendiente_pago');
            $table->timestamps();

            $table->unique(['ci', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preinscriptions');
    }
};
