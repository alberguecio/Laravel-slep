<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->year('anio_ejecucion')->nullable()->after('codigo_idi');
        });
        
        // Establecer 2025 como valor por defecto para todos los proyectos existentes
        DB::table('proyectos')->update(['anio_ejecucion' => 2025]);
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('anio_ejecucion');
        });
    }
};
