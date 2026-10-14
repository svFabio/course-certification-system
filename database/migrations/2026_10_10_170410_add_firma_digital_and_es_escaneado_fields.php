<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firma_digital_path', 255)->nullable()->after('remember_token');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->boolean('es_escaneado')->default(false)->after('pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('es_escaneado');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('firma_digital_path');
        });
    }
};
