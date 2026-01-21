<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('establecimientos', 'tipo')) {
                $table->string('tipo', 20)->default('Regular')->after('rbd');
            }
            if (!Schema::hasColumn('establecimientos', 'ruralidad')) {
                $table->string('ruralidad', 20)->nullable()->after('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            if (Schema::hasColumn('establecimientos', 'tipo')) {
                $table->dropColumn('tipo');
            }
            if (Schema::hasColumn('establecimientos', 'ruralidad')) {
                $table->dropColumn('ruralidad');
            }
        });
    }
};

