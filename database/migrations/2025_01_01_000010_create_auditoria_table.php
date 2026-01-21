<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->integer('usuario_id')->nullable();
            $table->string('entidad', 50)->nullable();
            $table->integer('entidad_id')->nullable();
            $table->string('accion', 20)->nullable();
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            $table->timestamp('fecha')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};


