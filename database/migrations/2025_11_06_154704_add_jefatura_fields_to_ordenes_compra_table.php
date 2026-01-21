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
            if (Schema::hasColumn('ordenes_compra', 'rcs_fecha')) {
                if (!Schema::hasColumn('ordenes_compra', 'rcs_tipo_jefatura')) {
                    $table->string('rcs_tipo_jefatura', 20)->nullable()->after('rcs_fecha');
                }
                if (!Schema::hasColumn('ordenes_compra', 'rcs_jefatura_firma')) {
                    $table->string('rcs_jefatura_firma', 100)->nullable()->after('rcs_tipo_jefatura');
                }
            }
            if (Schema::hasColumn('ordenes_compra', 'rcf_fecha')) {
                if (!Schema::hasColumn('ordenes_compra', 'rcf_tipo_jefatura')) {
                    $table->string('rcf_tipo_jefatura', 20)->nullable()->after('rcf_fecha');
                }
                if (!Schema::hasColumn('ordenes_compra', 'rcf_jefatura_firma')) {
                    $table->string('rcf_jefatura_firma', 100)->nullable()->after('rcf_tipo_jefatura');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropColumn(['rcs_tipo_jefatura', 'rcs_jefatura_firma', 'rcf_tipo_jefatura', 'rcf_jefatura_firma']);
        });
    }
};
