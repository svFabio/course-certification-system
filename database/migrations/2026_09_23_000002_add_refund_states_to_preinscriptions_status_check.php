<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE preinscriptions DROP CONSTRAINT IF EXISTS preinscriptions_status_check');
        DB::statement("ALTER TABLE preinscriptions ADD CONSTRAINT preinscriptions_status_check CHECK (((status)::text = ANY ((ARRAY['pendiente_pago'::character varying, 'inscrito'::character varying, 'retirado'::character varying, 'rechazado'::character varying, 'devolucion_pendiente'::character varying, 'reembolsado'::character varying])::text[])))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE preinscriptions DROP CONSTRAINT IF EXISTS preinscriptions_status_check');
        DB::statement("ALTER TABLE preinscriptions ADD CONSTRAINT preinscriptions_status_check CHECK (((status)::text = ANY ((ARRAY['pendiente_pago'::character varying, 'inscrito'::character varying, 'retirado'::character varying, 'rechazado'::character varying])::text[])))");
    }
};
