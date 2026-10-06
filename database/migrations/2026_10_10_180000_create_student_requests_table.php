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
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preinscription_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['cambio_grupo', 'devolucion']);
            $table->foreignId('group_id_destino')->nullable()->constrained('groups')->nullOnDelete();
            $table->text('motivo');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->string('motivo_rechazo', 255)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        DB::statement("CREATE UNIQUE INDEX student_requests_pending_unique ON student_requests (preinscription_id, tipo) WHERE estado = 'pendiente'");
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};
