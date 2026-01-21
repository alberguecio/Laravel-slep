<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('ordenes_compra', 'factura')) {
                $table->string('factura', 50)->nullable()->after('descripcion');
            }
            if (!Schema::hasColumn('ordenes_compra', 'monto_factura')) {
                $table->decimal('monto_factura', 15, 2)->nullable()->after('factura');
            }
            if (!Schema::hasColumn('ordenes_compra', 'fecha_factura')) {
                $table->date('fecha_factura')->nullable()->after('monto_factura');
            }
            if (!Schema::hasColumn('ordenes_compra', 'fecha_recepcion_factura')) {
                $table->date('fecha_recepcion_factura')->nullable()->after('fecha_factura');
            }
            if (!Schema::hasColumn('ordenes_compra', 'mes_estimado_pago')) {
                $table->string('mes_estimado_pago', 20)->nullable()->after('fecha_recepcion_factura');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'mes_estimado_pago')) {
                $table->dropColumn('mes_estimado_pago');
            }
            if (Schema::hasColumn('ordenes_compra', 'fecha_recepcion_factura')) {
                $table->dropColumn('fecha_recepcion_factura');
            }
            if (Schema::hasColumn('ordenes_compra', 'fecha_factura')) {
                $table->dropColumn('fecha_factura');
            }
            if (Schema::hasColumn('ordenes_compra', 'monto_factura')) {
                $table->dropColumn('monto_factura');
            }
            if (Schema::hasColumn('ordenes_compra', 'factura')) {
                $table->dropColumn('factura');
            }
            if (Schema::hasColumn('ordenes_compra', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
        });
    }
};

