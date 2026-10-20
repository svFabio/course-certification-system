<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->string('pdf_escaneado_path')->nullable()->after('pdf_path');
            $table->timestamp('escaneado_en')->nullable()->after('pdf_escaneado_path');
            $table->timestamp('entregado_en')->nullable()->after('escaneado_en');
            $table->string('estado', 32)->default('generado')->after('entregado_en');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropColumn([
                'pdf_escaneado_path',
                'escaneado_en',
                'entregado_en',
                'estado',
            ]);
        });
    }
};
