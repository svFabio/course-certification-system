<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preinscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('monto', 8, 2);
            $table->enum('metodo', ['efectivo', 'qr']);
            $table->string('numero_comprobante')->nullable();
            $table->foreignId('verificado_por')->nullable()->constrained('users');
            $table->timestamp('verificado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
