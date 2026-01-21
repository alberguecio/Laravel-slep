<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportarProveedores extends Command
{
    protected $signature = 'exportar:proveedores';
    protected $description = 'Exporta datos de proveedores (oferentes) a archivos JSON y SQL';

    public function handle()
    {
        $this->info('=== Exportando datos de proveedores ===');
        
        // Exportar proveedores
        $this->info('1. Exportando proveedores...');
        $proveedores = DB::table('oferentes')->orderBy('id')->get();
        $proveedoresJson = json_encode($proveedores->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(storage_path('app/proveedores_export.json'), $proveedoresJson);
        $this->info("   ✓ Exportados {$proveedores->count()} proveedores");
        
        // Generar SQL
        $this->info('2. Generando archivo SQL...');
        $sql = "-- Exportación de datos de proveedores (oferentes)\n";
        $sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "-- ============================================\n";
        $sql .= "-- PROVEEDORES (OFERENTES)\n";
        $sql .= "-- ============================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $sql .= "TRUNCATE TABLE oferentes;\n\n";
        
        foreach ($proveedores as $prov) {
            $nombre = addslashes($prov->nombre);
            $rut = $prov->rut ? "'" . addslashes($prov->rut) . "'" : "NULL";
            $direccion = $prov->direccion ? "'" . addslashes($prov->direccion) . "'" : "NULL";
            $telefono = $prov->telefono ? "'" . addslashes($prov->telefono) . "'" : "NULL";
            $email = $prov->email ? "'" . addslashes($prov->email) . "'" : "NULL";
            
            $sql .= "INSERT INTO oferentes (id, nombre, rut, direccion, telefono, email, created_at, updated_at) VALUES (";
            $sql .= "{$prov->id}, '{$nombre}', {$rut}, {$direccion}, {$telefono}, {$email}, ";
            $sql .= $prov->created_at ? "'{$prov->created_at}'" : "NULL";
            $sql .= ", ";
            $sql .= $prov->updated_at ? "'{$prov->updated_at}'" : "NULL";
            $sql .= ");\n";
        }
        
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents(storage_path('app/proveedores_import.sql'), $sql);
        $this->info("   ✓ Archivo SQL generado");
        
        $this->info("\n=== Exportación completada ===");
        $this->info("\nArchivos generados en storage/app/:");
        $this->info("  - proveedores_export.json");
        $this->info("  - proveedores_import.sql");
        
        return 0;
    }
}






