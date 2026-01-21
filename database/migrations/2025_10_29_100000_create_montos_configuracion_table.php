<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('montos_configuracion')) {
            Schema::create('montos_configuracion', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 150);
                $table->string('codigo', 50)->unique();
                $table->decimal('monto', 15, 2)->default(0);
                $table->timestamps();
            });

            // Insertar montos iniciales según la captura
            DB::table('montos_configuracion')->insert([
                ['nombre' => 'Subvención Mantenimiento', 'codigo' => 'subvencion_mantenimiento', 'monto' => 468960215, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Subvención General (Aporte)', 'codigo' => 'subvencion_general', 'monto' => 537739785, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Mantención Jardines y Salas Cuna VTF', 'codigo' => 'mantencion_vtf', 'monto' => 87000000, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Subtítulo 31', 'codigo' => 'subtitulo31', 'monto' => 312600000, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Emergencia', 'codigo' => 'emergencia', 'monto' => 703277196, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Caja Chica', 'codigo' => 'caja_chica', 'monto' => 0, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('montos_configuracion');
    }
};

