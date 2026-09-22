<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->date('fecha');
            $table->string('alcance')->default('nacional'); // nacional, departamental (Cochabamba), universitario (UMSS)
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['fecha', 'alcance']);
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->boolean('es_pospuesta')->default(false)->after('dictada');
            $table->date('fecha_original')->nullable()->after('es_pospuesta');
        });
    }

    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropColumn(['es_pospuesta', 'fecha_original']);
        });

        Schema::dropIfExists('holidays');
    }
};
