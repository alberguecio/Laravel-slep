<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comuna_id')->nullable()->constrained('comunas');
            $table->foreignId('establecimiento_id')->nullable()->constrained('establecimientos');
            $table->foreignId('convenio_id')->nullable()->constrained('convenios');
            $table->foreignId('oferente_id')->nullable()->constrained('oferentes');
            $table->string('numero_ot', 20)->nullable();
            $table->date('fecha_ot')->nullable();
            $table->date('fecha_envio_oc')->nullable();
            $table->string('mes', 20)->nullable();
            $table->decimal('sin_iva', 15, 2)->nullable();
            $table->decimal('monto', 15, 2)->nullable();
            $table->string('orden_compra', 50)->nullable();
            $table->date('fecha_oc')->nullable();
            $table->date('fecha_recepcion')->nullable();
            $table->string('factura', 50)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra');
            $table->foreignId('contrato_id')->nullable()->constrained('contratos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};

