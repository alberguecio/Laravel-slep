<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ordenes_compra', 'rcs_numero')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcs_numero VARCHAR(50) NULL AFTER mes_estimado_pago');
        }
        if (!Schema::hasColumn('ordenes_compra', 'rcs_fecha')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcs_fecha DATE NULL AFTER rcs_numero');
        }
        if (!Schema::hasColumn('ordenes_compra', 'rcf_numero')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcf_numero VARCHAR(50) NULL AFTER rcs_fecha');
        }
        if (!Schema::hasColumn('ordenes_compra', 'rcf_fecha')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcf_fecha DATE NULL AFTER rcf_numero');
        }
        if (Schema::hasColumn('ordenes_compra', 'rcs_fecha') && !Schema::hasColumn('ordenes_compra', 'rcs_tipo_jefatura')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcs_tipo_jefatura VARCHAR(20) NULL AFTER rcs_fecha');
        }
        if (Schema::hasColumn('ordenes_compra', 'rcs_tipo_jefatura') && !Schema::hasColumn('ordenes_compra', 'rcs_jefatura_firma')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcs_jefatura_firma VARCHAR(100) NULL AFTER rcs_tipo_jefatura');
        }
        if (Schema::hasColumn('ordenes_compra', 'rcf_fecha') && !Schema::hasColumn('ordenes_compra', 'rcf_tipo_jefatura')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcf_tipo_jefatura VARCHAR(20) NULL AFTER rcf_fecha');
        }
        if (Schema::hasColumn('ordenes_compra', 'rcf_tipo_jefatura') && !Schema::hasColumn('ordenes_compra', 'rcf_jefatura_firma')) {
            DB::statement('ALTER TABLE ordenes_compra ADD COLUMN rcf_jefatura_firma VARCHAR(100) NULL AFTER rcf_tipo_jefatura');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer rollback para evitar problemas
    }
};
