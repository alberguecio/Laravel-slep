<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('tipo', 50)->nullable();
            $table->string('fondo', 50)->nullable();
            $table->foreignId('oferente_id')->nullable()->constrained('oferentes');
            $table->decimal('monto_asignado', 15, 2)->nullable();
            $table->string('estado', 20)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items');
            $table->string('codigo_idi', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};

