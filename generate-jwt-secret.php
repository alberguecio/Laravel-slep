<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Str;

// Verificar JWT_SECRET ANTES de inicializar Laravel
$jwtSecret = getenv('JWT_SECRET');

if (empty($jwtSecret)) {
    // Generar nuevo JWT_SECRET
    $newSecret = Str::random(64);
    
    // Establecer en el entorno ANTES de inicializar Laravel
    putenv("JWT_SECRET=$newSecret");
    $_ENV['JWT_SECRET'] = $newSecret;
    
    echo "✓ JWT_SECRET generado: $newSecret\n";
    echo "⚠️  IMPORTANTE: Copia este valor a la variable JWT_SECRET en Railway para persistencia:\n";
    echo "   JWT_SECRET=$newSecret\n";
} else {
    echo "✓ JWT_SECRET ya está configurado\n";
}

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Limpiar caché de configuración para que Laravel recargue JWT_SECRET
    if (file_exists(base_path('bootstrap/cache/config.php'))) {
        @unlink(base_path('bootstrap/cache/config.php'));
    }
    
    // Verificar que la configuración se cargó correctamente
    $configSecret = config('jwt.secret');
    if (empty($configSecret)) {
        // Forzar actualización de configuración
        config(['jwt.secret' => getenv('JWT_SECRET')]);
        echo "✓ Configuración de JWT actualizada\n";
    }
} catch (\Exception $e) {
    echo "⚠️  Error al inicializar Laravel: " . $e->getMessage() . "\n";
    echo "   Continuando de todas formas...\n";
}
