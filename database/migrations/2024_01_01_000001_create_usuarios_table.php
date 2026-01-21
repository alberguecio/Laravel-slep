<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('email', 100)->unique();
            $table->string('password_hash', 255);
            $table->string('rol', 20)->default('usuario');
            $table->string('estado', 20)->default('activo');
            $table->string('cargo', 100)->nullable();
            $table->json('permisos')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

