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
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'rcs_numero')) {
                // Usar after() solo si la columna mes_estimado_pago existe
                if (Schema::hasColumn('ordenes_compra', 'mes_estimado_pago')) {
                    $table->string('rcs_numero', 50)->nullable()->after('mes_estimado_pago');
                } else {
                    $table->string('rcs_numero', 50)->nullable();
                }
            }
            if (!Schema::hasColumn('ordenes_compra', 'rcs_fecha')) {
                $table->date('rcs_fecha')->nullable()->after('rcs_numero');
            }
            if (!Schema::hasColumn('ordenes_compra', 'rcf_numero')) {
                $table->string('rcf_numero', 50)->nullable()->after('rcs_fecha');
            }
            if (!Schema::hasColumn('ordenes_compra', 'rcf_fecha')) {
                $table->date('rcf_fecha')->nullable()->after('rcf_numero');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropColumn(['rcs_numero', 'rcs_fecha', 'rcf_numero', 'rcf_fecha']);
        });
    }
};
