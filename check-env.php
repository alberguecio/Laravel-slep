<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "=== VERIFICACIÓN DE VARIABLES DE ENTORNO ===\n\n";
    
    echo "DB_CONNECTION: " . env('DB_CONNECTION', 'no definido') . "\n";
    echo "DB_HOST: " . env('DB_HOST', 'no definido') . "\n";
    echo "DB_PORT: " . env('DB_PORT', 'no definido') . "\n";
    echo "DB_DATABASE: " . env('DB_DATABASE', 'no definido') . "\n";
    echo "DB_USERNAME: " . env('DB_USERNAME', 'no definido') . "\n";
    echo "DB_PASSWORD: " . (env('DB_PASSWORD') ? '***definido***' : 'no definido') . "\n";
    
    // Verificar si hay DB_URL
    $dbUrl = env('DB_URL');
    if ($dbUrl) {
        echo "\nDB_URL está definido: " . substr($dbUrl, 0, 50) . "...\n";
        // Intentar parsear la URL
        $parsed = parse_url($dbUrl);
        if ($parsed) {
            echo "  Host parseado: " . ($parsed['host'] ?? 'no encontrado') . "\n";
            echo "  Puerto parseado: " . ($parsed['port'] ?? 'no encontrado') . "\n";
            echo "  Base de datos parseada: " . (ltrim($parsed['path'] ?? '', '/') ?: 'no encontrado') . "\n";
        }
    } else {
        echo "\nDB_URL no está definido\n";
    }
    
    echo "\n=== INTENTANDO CONEXIÓN ===\n";
    try {
        $pdo = DB::connection()->getPdo();
        echo "✓ Conexión exitosa\n";
        echo "  Driver: " . DB::connection()->getDriverName() . "\n";
    } catch (\Exception $e) {
        echo "✗ Error de conexión: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
