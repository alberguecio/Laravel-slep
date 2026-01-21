<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportarEstablecimientos extends Command
{
    protected $signature = 'exportar:establecimientos';
    protected $description = 'Exporta datos de establecimientos y comunas a archivos JSON y SQL';

    public function handle()
    {
        $this->info('=== Exportando datos de establecimientos ===');
        
        // 1. Exportar comunas
        $this->info('1. Exportando comunas...');
        $comunas = DB::table('comunas')->get();
        $comunasJson = json_encode($comunas->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(storage_path('app/comunas_export.json'), $comunasJson);
        $this->info("   ✓ Exportadas {$comunas->count()} comunas");
        
        // 2. Exportar establecimientos
        $this->info('2. Exportando establecimientos...');
        $establecimientos = DB::table('establecimientos')->orderBy('id')->get();
        $establecimientosJson = json_encode($establecimientos->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(storage_path('app/establecimientos_export.json'), $establecimientosJson);
        $this->info("   ✓ Exportados {$establecimientos->count()} establecimientos");
        
        // 3. Generar SQL
        $this->info('3. Generando archivo SQL...');
        $sql = "-- Exportación de datos de establecimientos\n";
        $sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "-- ============================================\n";
        $sql .= "-- COMUNAS\n";
        $sql .= "-- ============================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $sql .= "TRUNCATE TABLE comunas;\n\n";
        
        foreach ($comunas as $comuna) {
            $nombre = addslashes($comuna->nombre);
            $sql .= "INSERT INTO comunas (id, nombre, created_at, updated_at) VALUES ";
            $sql .= "({$comuna->id}, '{$nombre}', ";
            $sql .= $comuna->created_at ? "'{$comuna->created_at}'" : "NULL";
            $sql .= ", ";
            $sql .= $comuna->updated_at ? "'{$comuna->updated_at}'" : "NULL";
            $sql .= ");\n";
        }
        
        $sql .= "\n-- ============================================\n";
        $sql .= "-- ESTABLECIMIENTOS\n";
        $sql .= "-- ============================================\n\n";
        $sql .= "TRUNCATE TABLE establecimientos;\n\n";
        
        foreach ($establecimientos as $est) {
            $nombre = addslashes($est->nombre);
            $rbd = $est->rbd ? "'" . addslashes($est->rbd) . "'" : "NULL";
            $comuna_id = $est->comuna_id ? $est->comuna_id : "NULL";
            $subvencion_mantenimiento = $est->subvencion_mantenimiento ?? 0;
            $aporte_subvencion_general = $est->aporte_subvencion_general ?? 0;
            $tipo = isset($est->tipo) ? "'" . addslashes($est->tipo) . "'" : "'Regular'";
            $ruralidad = isset($est->ruralidad) && $est->ruralidad ? "'" . addslashes($est->ruralidad) . "'" : "NULL";
            $matricula = isset($est->matricula) && $est->matricula ? $est->matricula : "NULL";
            $director = isset($est->director) && $est->director ? "'" . addslashes($est->director) . "'" : "NULL";
            $telefono = isset($est->telefono) && $est->telefono ? "'" . addslashes($est->telefono) . "'" : "NULL";
            $email = isset($est->email) && $est->email ? "'" . addslashes($est->email) . "'" : "NULL";
            
            $sql .= "INSERT INTO establecimientos (id, nombre, comuna_id, rbd, tipo, ruralidad, ";
            $sql .= "subvencion_mantenimiento, aporte_subvencion_general, matricula, director, telefono, email, ";
            $sql .= "created_at, updated_at) VALUES (";
            $sql .= "{$est->id}, '{$nombre}', {$comuna_id}, {$rbd}, {$tipo}, {$ruralidad}, ";
            $sql .= "{$subvencion_mantenimiento}, {$aporte_subvencion_general}, {$matricula}, {$director}, {$telefono}, {$email}, ";
            $sql .= $est->created_at ? "'{$est->created_at}'" : "NULL";
            $sql .= ", ";
            $sql .= $est->updated_at ? "'{$est->updated_at}'" : "NULL";
            $sql .= ");\n";
        }
        
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents(storage_path('app/establecimientos_import.sql'), $sql);
        $this->info("   ✓ Archivo SQL generado");
        
        $this->info("\n=== Exportación completada ===");
        $this->info("\nArchivos generados en storage/app/:");
        $this->info("  - comunas_export.json");
        $this->info("  - establecimientos_export.json");
        $this->info("  - establecimientos_import.sql");
        
        return 0;
    }
}






