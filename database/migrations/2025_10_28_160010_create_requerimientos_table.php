<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requerimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comuna_id')->nullable()->constrained('comunas');
            $table->foreignId('establecimiento_id')->nullable()->constrained('establecimientos');
            $table->text('descripcion');
            $table->timestamp('fecha_ingreso')->useCurrent();
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('usuario_creador_id')->nullable()->constrained('usuarios');
            $table->foreignId('usuario_mod_id')->nullable()->constrained('usuarios');
            $table->timestamp('fecha_mod')->useCurrent();
            $table->string('via_solicitud', 20);
            $table->date('fecha_email')->nullable();
            $table->string('numero_oficio', 50)->nullable();
            $table->date('fecha_oficio')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimientos');
    }
};

