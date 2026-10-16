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
            if (Schema::hasColumn('certificates', 'signature_status')) {
                $table->dropColumn('signature_status');
            }
            if (Schema::hasColumn('certificates', 'listo_en')) {
                $table->dropColumn('listo_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            if (! Schema::hasColumn('certificates', 'signature_status')) {
                $table->string('signature_status', 32)->default('listo');
            }
            if (! Schema::hasColumn('certificates', 'listo_en')) {
                $table->timestamp('listo_en')->nullable();
            }
        });
    }
};
