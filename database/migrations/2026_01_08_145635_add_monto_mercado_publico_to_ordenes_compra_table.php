<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->decimal('monto_mercado_publico', 15, 2)->nullable()->after('monto_total');
        });
        
        // Copiar monto_total a monto_mercado_publico para registros existentes
        DB::statement('UPDATE ordenes_compra SET monto_mercado_publico = monto_total WHERE monto_mercado_publico IS NULL');
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropColumn('monto_mercado_publico');
        });
    }
};
