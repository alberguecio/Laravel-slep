<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarEstablecimientos extends Command
{
    protected $signature = 'importar:establecimientos {archivo?}';
    protected $description = 'Importa datos de establecimientos desde un archivo SQL';

    public function handle()
    {
        $archivo = $this->argument('archivo') ?? storage_path('app/establecimientos_import.sql');
        
        if (!file_exists($archivo)) {
            $this->error("El archivo no existe: {$archivo}");
            return 1;
        }
        
        $this->info("Leyendo archivo: {$archivo}");
        $sql = file_get_contents($archivo);
        
        if (empty($sql)) {
            $this->error("El archivo está vacío");
            return 1;
        }
        
        $this->warn("⚠️  ADVERTENCIA: Esto va a TRUNCAR las tablas 'comunas' y 'establecimientos'");
        $this->warn("⚠️  Todos los datos existentes en estas tablas serán eliminados.");
        
        if (!$this->confirm('¿Estás seguro de que deseas continuar?', false)) {
            $this->info('Operación cancelada.');
            return 0;
        }
        
        try {
            $this->info("Ejecutando SQL...");
            $pdo = DB::connection()->getPdo();
            
            // Ejecutar el SQL completo
            $pdo->exec($sql);
            
            // Verificar resultados
            $comunasCount = DB::table('comunas')->count();
            $establecimientosCount = DB::table('establecimientos')->count();
            
            $this->info("✓ Importación completada exitosamente!");
            $this->info("  - Comunas importadas: {$comunasCount}");
            $this->info("  - Establecimientos importados: {$establecimientosCount}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error al importar: " . $e->getMessage());
            return 1;
        }
    }
}






