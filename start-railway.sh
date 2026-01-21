#!/bin/sh

echo "=== Limpiando caché de configuración ==="
php artisan config:clear 2>/dev/null || true

echo "=== Ejecutando migraciones ==="
php artisan migrate --force 2>/dev/null || echo "⚠️  Error en migraciones (continuando...)"

echo "=== Generando JWT_SECRET si no existe ==="
# Ejecutar el script PHP
php generate-jwt-secret.php 2>&1 || echo "⚠️  Error generando JWT_SECRET (continuando...)"

# Leer JWT_SECRET del archivo temporal si fue generado
JWT_FILE="/tmp/jwt_secret.txt"
if [ ! -f "$JWT_FILE" ]; then
    JWT_FILE="/var/tmp/jwt_secret.txt"
fi

if [ -f "$JWT_FILE" ]; then
    JWT_SECRET=$(cat "$JWT_FILE" 2>/dev/null)
    if [ -n "$JWT_SECRET" ]; then
        export JWT_SECRET="$JWT_SECRET"
        # Mostrar primeros 20 caracteres de forma compatible con sh
        JWT_PREVIEW=$(echo "$JWT_SECRET" | cut -c1-20)
        echo "✓ JWT_SECRET exportado al entorno: ${JWT_PREVIEW}..."
    fi
    rm -f "$JWT_FILE" 2>/dev/null || true
fi

echo "=== Regenerando caché de configuración ==="
php artisan config:cache 2>/dev/null || echo "⚠️  Error regenerando caché (continuando...)"

echo "=== Creando usuario administrador ==="
php create-admin-user.php 2>/dev/null || echo "⚠️  Error creando usuario (continuando...)"

echo "=== Iniciando servidor en puerto ${PORT:-8000} ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
