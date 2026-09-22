<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preinscriptions', function (Blueprint $table) {
            $table->string('cod_sis', 50)->nullable()->after('ci');
            $table->boolean('fotocopia_ci')->default(false)->after('status');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->string('aula', 100)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('preinscriptions', function (Blueprint $table) {
            $table->dropColumn(['cod_sis', 'fotocopia_ci']);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('aula');
        });
    }
};
