#!/bin/sh
set -e

echo "=== Configurando permisos de storage ==="
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache

echo "=== Limpiando cache ==="
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "=== Ejecutando migraciones ==="
php artisan migrate --force || echo "⚠️  Error en migraciones"

echo "=== Generando JWT_SECRET si no existe ==="
php artisan jwt:secret --always-no 2>/dev/null || php artisan jwt:secret --force 2>/dev/null || echo "⚠️  No se pudo generar JWT_SECRET"

echo "=== Verificando variables de entorno ==="
php check-env.php 2>/dev/null || echo "⚠️  Error en verificación de env"

echo "=== Verificando estado de base de datos ==="
php check-db-status.php 2>/dev/null || echo "⚠️  Error en diagnóstico"

echo "=== Verificando usuario inicial ==="
php create-admin-user.php 2>/dev/null || echo "⚠️  No se pudo crear usuario inicial"

echo "=== Iniciando servidor en puerto ${PORT:-8000} ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
