<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('motivo_devolucion')->nullable()->after('motivo_rechazo');
            $table->foreignId('reembolsado_por')
                ->nullable()
                ->after('motivo_devolucion')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reembolsado_en')->nullable()->after('reembolsado_por');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reembolsado_por');
            $table->dropColumn(['motivo_devolucion', 'reembolsado_en']);
        });
    }
};
