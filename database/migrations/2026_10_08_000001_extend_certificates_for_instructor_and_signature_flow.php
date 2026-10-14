<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['preinscription_id']);
        });

        DB::statement('ALTER TABLE certificates ALTER COLUMN preinscription_id DROP NOT NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN tipo TYPE VARCHAR(32) USING tipo::text');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE certificates DROP CONSTRAINT IF EXISTS certificates_tipo_check');
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT certificates_tipo_check CHECK (tipo IN ('aprobacion', 'asistencia', 'instructor'))");
        }

        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreign('preinscription_id')
                ->references('id')
                ->on('preinscriptions')
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->nullable()
                ->after('preinscription_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('grupos_detalle')
                ->nullable()
                ->after('tipo');

            $table->timestamp('listo_en')
                ->nullable()
                ->after('emitido_en');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['instructor_id']);
            $table->dropColumn(['instructor_id', 'grupos_detalle', 'listo_en']);
            $table->dropForeign(['preinscription_id']);
        });

        DB::statement('DELETE FROM certificates WHERE preinscription_id IS NULL');
        DB::statement('ALTER TABLE certificates ALTER COLUMN preinscription_id SET NOT NULL');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT certificates_tipo_check CHECK (tipo IN ('aprobacion', 'asistencia'))");
        }

        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreign('preinscription_id')
                ->references('id')
                ->on('preinscriptions')
                ->cascadeOnDelete();
        });
    }
};
