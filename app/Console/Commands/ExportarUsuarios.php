<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportarUsuarios extends Command
{
    protected $signature = 'exportar:usuarios';
    protected $description = 'Exporta datos de usuarios a archivos JSON y SQL';

    public function handle()
    {
        $this->info('=== Exportando datos de usuarios ===');
        
        // Exportar usuarios
        $this->info('1. Exportando usuarios...');
        $usuarios = DB::table('usuarios')->orderBy('id')->get();
        $usuariosJson = json_encode($usuarios->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(storage_path('app/usuarios_export.json'), $usuariosJson);
        $this->info("   ✓ Exportados {$usuarios->count()} usuarios");
        
        // Generar SQL
        $this->info('2. Generando archivo SQL...');
        $sql = "-- Exportación de datos de usuarios\n";
        $sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "-- ============================================\n";
        $sql .= "-- USUARIOS\n";
        $sql .= "-- ============================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $sql .= "TRUNCATE TABLE usuarios;\n\n";
        
        foreach ($usuarios as $user) {
            $nombre = addslashes($user->nombre);
            $email = addslashes($user->email);
            $password_hash = addslashes($user->password_hash);
            $rol = addslashes($user->rol ?? 'usuario');
            $estado = addslashes($user->estado ?? 'activo');
            $cargo = $user->cargo ? "'" . addslashes($user->cargo) . "'" : "NULL";
            $permisos = $user->permisos ? "'" . addslashes($user->permisos) . "'" : "NULL";
            $fecha_creacion = $user->fecha_creacion ? "'{$user->fecha_creacion}'" : "NULL";
            $ultimo_acceso = $user->ultimo_acceso ? "'{$user->ultimo_acceso}'" : "NULL";
            $created_at = $user->created_at ? "'{$user->created_at}'" : "NULL";
            $updated_at = $user->updated_at ? "'{$user->updated_at}'" : "NULL";
            
            $sql .= "INSERT INTO usuarios (id, nombre, email, password_hash, rol, estado, cargo, permisos, fecha_creacion, ultimo_acceso, created_at, updated_at) VALUES (";
            $sql .= "{$user->id}, '{$nombre}', '{$email}', '{$password_hash}', '{$rol}', '{$estado}', {$cargo}, {$permisos}, {$fecha_creacion}, {$ultimo_acceso}, {$created_at}, {$updated_at}";
            $sql .= ");\n";
        }
        
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents(storage_path('app/usuarios_import.sql'), $sql);
        $this->info("   ✓ Archivo SQL generado");
        
        $this->info("\n=== Exportación completada ===");
        $this->info("\nArchivos generados en storage/app/:");
        $this->info("  - usuarios_export.json");
        $this->info("  - usuarios_import.sql");
        
        return 0;
    }
}






