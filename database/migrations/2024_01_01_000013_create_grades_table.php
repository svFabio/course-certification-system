<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_criteria_id')->constrained('evaluation_criteria')->cascadeOnDelete();
            $table->foreignId('preinscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('nota', 5, 2);
            $table->timestamps();

            $table->unique(['evaluation_criteria_id', 'preinscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
