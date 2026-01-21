<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (\Exception $e) {
    echo "✗ Error al inicializar Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // Verificar si ya existen usuarios
    $userCount = DB::table('usuarios')->count();
    
    if ($userCount === 0) {
        // Crear usuario administrador inicial
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
        
        echo "✓ Usuario administrador creado exitosamente\n";
        echo "  Email: admin@slepchiloe.gob.cl\n";
        echo "  Contraseña: admin123\n";
        echo "  ⚠️  IMPORTANTE: Cambia esta contraseña después del primer inicio de sesión\n";
    } else {
        echo "✓ Ya existen usuarios en la base de datos ($userCount usuarios)\n";
    }
} catch (\Exception $e) {
    echo "⚠️  No se pudo crear usuario inicial: " . $e->getMessage() . "\n";
    echo "   Esto es normal si las migraciones aún no se han ejecutado.\n";
}
