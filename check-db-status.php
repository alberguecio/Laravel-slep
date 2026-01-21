<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    
    // Bootstrap Laravel
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "=== DIAGNÓSTICO DE BASE DE DATOS ===\n\n";
    
    // Verificar conexión
    echo "1. Verificando conexión a la base de datos...\n";
    try {
        DB::connection()->getPdo();
        echo "   ✓ Conexión exitosa\n\n";
    } catch (\Exception $e) {
        echo "   ✗ Error de conexión: " . $e->getMessage() . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ Error al inicializar Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // Verificar si existe la tabla usuarios
    echo "2. Verificando tabla 'usuarios'...\n";
    if (Schema::hasTable('usuarios')) {
        echo "   ✓ Tabla 'usuarios' existe\n";
        
        $userCount = DB::table('usuarios')->count();
        echo "   Total de usuarios: $userCount\n\n";
        
        if ($userCount > 0) {
            echo "3. Listando usuarios existentes:\n";
            $usuarios = DB::table('usuarios')->select('id', 'nombre', 'email', 'rol', 'estado')->get();
            foreach ($usuarios as $user) {
                echo "   - ID: {$user->id}, Email: {$user->email}, Rol: {$user->rol}, Estado: {$user->estado}\n";
            }
            echo "\n";
            
            // Verificar usuario admin específico
            $admin = DB::table('usuarios')->where('email', 'admin@slepchiloe.gob.cl')->first();
            if ($admin) {
                echo "4. Usuario admin encontrado:\n";
                echo "   Email: {$admin->email}\n";
                echo "   Estado: {$admin->estado}\n";
                echo "   Rol: {$admin->rol}\n";
                
                // Verificar contraseña
                echo "\n5. Verificando contraseña 'admin123'...\n";
                if (Hash::check('admin123', $admin->password_hash)) {
                    echo "   ✓ Contraseña correcta\n";
                } else {
                    echo "   ✗ Contraseña NO coincide\n";
                    echo "   Hash almacenado: " . substr($admin->password_hash, 0, 20) . "...\n";
                }
            } else {
                echo "4. ✗ Usuario admin@slepchiloe.gob.cl NO encontrado\n";
            }
        } else {
            echo "3. ✗ No hay usuarios en la base de datos\n";
            echo "\n4. Intentando crear usuario administrador...\n";
            
            try {
                DB::table('usuarios')->insert([
                    'nombre' => 'Administrador',
                    'email' => 'admin@slepchiloe.gob.cl',
                    'password_hash' => Hash::make('admin123'),
                    'rol' => 'administrador',
                    'estado' => 'activo',
                    'fecha_creacion' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "   ✓ Usuario administrador creado exitosamente\n";
                echo "   Email: admin@slepchiloe.gob.cl\n";
                echo "   Contraseña: admin123\n";
            } catch (\Exception $e) {
                echo "   ✗ Error al crear usuario: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   ✗ Tabla 'usuarios' NO existe\n";
        echo "   Las migraciones no se han ejecutado.\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
