<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preinscriptions', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->restrictOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['preinscription_id']);
            $table->foreign('preinscription_id')
                ->references('id')
                ->on('preinscriptions')
                ->restrictOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['preinscription_id']);
            $table->foreign('preinscription_id')
                ->references('id')
                ->on('preinscriptions')
                ->restrictOnDelete();

            $table->dropForeign(['course_id']);
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->restrictOnDelete();
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropForeign(['course_id']);
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropForeign(['course_id']);
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['course_id']);
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();

            $table->dropForeign(['preinscription_id']);
            $table->foreign('preinscription_id')
                ->references('id')
                ->on('preinscriptions')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['preinscription_id']);
            $table->foreign('preinscription_id')
                ->references('id')
                ->on('preinscriptions')
                ->cascadeOnDelete();
        });

        Schema::table('preinscriptions', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->cascadeOnDelete();
        });
    }
};
