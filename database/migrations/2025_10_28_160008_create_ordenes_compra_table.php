<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50);
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos');
            $table->foreignId('oferente_id')->nullable()->constrained('oferentes');
            $table->decimal('monto_total', 15, 2)->nullable();
            $table->string('estado', 20)->nullable();
            $table->date('fecha')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};

