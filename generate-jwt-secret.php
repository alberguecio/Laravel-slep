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
    
    // Escribir a archivo temporal para que el script shell lo lea
    $tmpFile = '/tmp/jwt_secret.txt';
    $written = @file_put_contents($tmpFile, $newSecret);
    if ($written === false) {
        // Intentar con directorio alternativo
        $tmpFile = sys_get_temp_dir() . '/jwt_secret.txt';
        $written = @file_put_contents($tmpFile, $newSecret);
    }
    
    if ($written !== false) {
        echo "✓ JWT_SECRET generado: $newSecret\n";
        echo "⚠️  IMPORTANTE: Copia este valor a la variable JWT_SECRET en Railway para persistencia:\n";
        echo "JWT_SECRET=$newSecret\n";
    } else {
        echo "✓ JWT_SECRET generado: $newSecret\n";
        echo "⚠️  No se pudo escribir archivo temporal, pero JWT_SECRET está en el entorno\n";
    }
} else {
    echo "✓ JWT_SECRET ya está configurado\n";
}

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Verificar que la configuración se cargó correctamente
    $configSecret = config('jwt.secret');
    if (empty($configSecret) && !empty(getenv('JWT_SECRET'))) {
        // Forzar actualización de configuración
        config(['jwt.secret' => getenv('JWT_SECRET')]);
        echo "✓ Configuración de JWT actualizada en memoria\n";
    }
} catch (\Exception $e) {
    echo "⚠️  Error al inicializar Laravel: " . $e->getMessage() . "\n";
    echo "   Continuando de todas formas...\n";
}
