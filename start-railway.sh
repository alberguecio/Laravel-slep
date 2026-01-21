#!/bin/sh
set -e

echo "=== Limpiando caché de configuración ==="
php artisan config:clear || true

echo "=== Ejecutando migraciones ==="
php artisan migrate --force || true

echo "=== Generando JWT_SECRET si no existe ==="
# Ejecutar el script PHP
php generate-jwt-secret.php

# Leer JWT_SECRET del archivo temporal si fue generado
if [ -f /tmp/jwt_secret.txt ]; then
    JWT_SECRET=$(cat /tmp/jwt_secret.txt)
    export JWT_SECRET="$JWT_SECRET"
    echo "✓ JWT_SECRET exportado al entorno: ${JWT_SECRET:0:20}..."
    rm -f /tmp/jwt_secret.txt
fi

echo "=== Regenerando caché de configuración ==="
php artisan config:cache || true

echo "=== Creando usuario administrador ==="
php create-admin-user.php || true

echo "=== Iniciando servidor ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
