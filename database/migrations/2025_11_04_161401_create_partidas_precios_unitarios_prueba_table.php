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
        Schema::create('partidas_precios_unitarios_prueba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->onDelete('cascade');
            $table->string('numero_partida', 50)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->string('partida', 500)->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->string('unidad', 50)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->decimal('precio', 15, 2);
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index('contrato_id');
            $table->index('numero_partida');
        });
        
        // Asegurar que la tabla use utf8mb4
        DB::statement('ALTER TABLE `partidas_precios_unitarios_prueba` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidas_precios_unitarios_prueba');
    }
};
