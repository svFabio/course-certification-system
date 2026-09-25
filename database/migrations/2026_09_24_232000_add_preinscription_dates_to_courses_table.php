<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->date('fecha_inicio_preinscripcion')->nullable()->after('status');
            $table->date('fecha_fin_preinscripcion')->nullable()->after('fecha_inicio_preinscripcion');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_inicio_preinscripcion',
                'fecha_fin_preinscripcion',
            ]);
        });
    }
};
