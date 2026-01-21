<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('establecimientos', 'matricula')) {
                $table->integer('matricula')->nullable()->after('ruralidad');
            }
            if (!Schema::hasColumn('establecimientos', 'director')) {
                $table->string('director', 150)->nullable()->after('matricula');
            }
            if (!Schema::hasColumn('establecimientos', 'telefono')) {
                $table->string('telefono', 50)->nullable()->after('director');
            }
            if (!Schema::hasColumn('establecimientos', 'email')) {
                $table->string('email', 150)->nullable()->after('telefono');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            if (Schema::hasColumn('establecimientos', 'matricula')) {
                $table->dropColumn('matricula');
            }
            if (Schema::hasColumn('establecimientos', 'director')) {
                $table->dropColumn('director');
            }
            if (Schema::hasColumn('establecimientos', 'telefono')) {
                $table->dropColumn('telefono');
            }
            if (Schema::hasColumn('establecimientos', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};



