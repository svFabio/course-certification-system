<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE groups DROP CONSTRAINT IF EXISTS groups_status_check');
        DB::statement("ALTER TABLE groups ADD CONSTRAINT groups_status_check CHECK (status IN ('habilitado', 'no_habilitado', 'completo', 'en_curso', 'finalizado', 'cerrado'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE groups DROP CONSTRAINT IF EXISTS groups_status_check');
        DB::statement("ALTER TABLE groups ADD CONSTRAINT groups_status_check CHECK (status IN ('habilitado', 'no_habilitado', 'cerrado'))");
    }
};
