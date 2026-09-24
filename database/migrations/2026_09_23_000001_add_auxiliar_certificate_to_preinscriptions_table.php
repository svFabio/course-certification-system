<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preinscriptions', function (Blueprint $table) {
            $table->string('auxiliar_certificado_path')->nullable()->after('fotocopia_ci');
            $table->boolean('auxiliar_certificado_aprobado')->nullable()->after('auxiliar_certificado_path');
            $table->foreignId('auxiliar_certificado_aprobado_por')
                ->nullable()
                ->after('auxiliar_certificado_aprobado')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('auxiliar_certificado_aprobado_en')->nullable()->after('auxiliar_certificado_aprobado_por');
            $table->string('auxiliar_certificado_motivo')->nullable()->after('auxiliar_certificado_aprobado_en');
        });
    }

    public function down(): void
    {
        Schema::table('preinscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auxiliar_certificado_aprobado_por');
            $table->dropColumn([
                'auxiliar_certificado_path',
                'auxiliar_certificado_aprobado',
                'auxiliar_certificado_aprobado_en',
                'auxiliar_certificado_motivo',
            ]);
        });
    }
};
