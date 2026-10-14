<?php

declare(strict_types=1);

use App\Enums\InterestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estado')->default(InterestStatus::INTERESADO->value);
            $table->timestamp('notificado_at')->nullable();
            $table->foreignId('preinscription_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        DB::statement("CREATE UNIQUE INDEX course_interests_active_unique ON course_interests (course_id, email) WHERE estado IN ('".InterestStatus::INTERESADO->value."', '".InterestStatus::NOTIFICADO->value."')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS course_interests_active_unique');

        Schema::dropIfExists('course_interests');
    }
};
