<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Literal 50 mirrors BusinessRules::DEFAULT_ATTENDANCE_WEIGHT.
            // Kept as a literal so the migration stays immutable if the constant changes.
            $table->unsignedTinyInteger('attendance_weight')->default(50);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('attendance_weight');
        });
    }
};
