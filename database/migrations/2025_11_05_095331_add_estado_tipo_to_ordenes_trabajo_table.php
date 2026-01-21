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
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_trabajo', 'estado')) {
                $table->string('estado', 50)->nullable()->after('contrato_id');
            }
            if (!Schema::hasColumn('ordenes_trabajo', 'tipo')) {
                $table->string('tipo', 50)->nullable()->after('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_trabajo', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('ordenes_trabajo', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
