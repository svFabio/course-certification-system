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
            if (Schema::hasColumn('certificates', 'upload_token')) {
                $table->dropUnique(['upload_token']);
                $table->dropColumn('upload_token');
            }

            if (Schema::hasColumn('certificates', 'upload_token_expires_at')) {
                $table->dropColumn('upload_token_expires_at');
            }

            if (! Schema::hasColumn('certificates', 'entregado_en')) {
                $table->timestamp('entregado_en')->nullable()->after('escaneado_en');
            }

            if (! Schema::hasColumn('certificates', 'estado')) {
                $table->string('estado', 32)->default('generado')->after('entregado_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            if (Schema::hasColumn('certificates', 'estado')) {
                $table->dropColumn('estado');
            }

            if (Schema::hasColumn('certificates', 'entregado_en')) {
                $table->dropColumn('entregado_en');
            }

            if (! Schema::hasColumn('certificates', 'upload_token')) {
                $table->string('upload_token', 64)->nullable()->unique()->after('escaneado_en');
            }

            if (! Schema::hasColumn('certificates', 'upload_token_expires_at')) {
                $table->timestamp('upload_token_expires_at')->nullable()->after('upload_token');
            }
        });
    }
};
