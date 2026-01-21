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
        // Corregir el nombre de "Puqueldén" a "Puqueldón"
        DB::table('comunas')
            ->where('nombre', 'Puqueldén')
            ->orWhere('nombre', 'LIKE', '%Puqueldén%')
            ->update(['nombre' => 'Puqueldón']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el cambio (aunque no debería ser necesario)
        DB::table('comunas')
            ->where('nombre', 'Puqueldón')
            ->update(['nombre' => 'Puqueldén']);
    }
};
