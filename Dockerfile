FROM php:8.2-cli

# Variables de entorno
ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de composer primero (para cache de Docker)
COPY composer.json composer.lock ./

# Instalar dependencias de PHP SIN scripts (los scripts requieren el código completo)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts --no-autoloader

# Copiar explícitamente las carpetas críticas primero
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY database/ ./database/
COPY routes/ ./routes/
COPY public/ ./public/
COPY resources/ ./resources/
COPY artisan ./

# Crear directorios opcionales si no existen
RUN mkdir -p storage || true

# Verificar que los archivos críticos existen
RUN ls -la routes/ && echo "✓ routes/ existe" || echo "✗ ERROR: routes/ no encontrado"

# Crear directorios necesarios y configurar permisos ANTES de ejecutar scripts
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generar autoloader SIN ejecutar scripts (los scripts se ejecutarán al iniciar la app)
RUN composer dump-autoload --optimize --no-dev --no-scripts || true

# Exponer puerto (Render usa la variable $PORT)
EXPOSE 8000

# Copiar scripts para crear usuario inicial y diagnóstico
COPY create-admin-user.php ./
COPY check-db-status.php ./
COPY check-env.php ./

# Script de inicio que asegura permisos, ejecuta migraciones y crea usuario inicial
RUN echo '#!/bin/sh\n\
set -e\n\
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache\n\
chmod -R 777 storage bootstrap/cache\n\
php artisan config:clear 2>/dev/null || true\n\
php artisan cache:clear 2>/dev/null || true\n\
echo "=== Ejecutando migraciones ==="\n\
php artisan migrate --force || echo "⚠️  Error en migraciones"\n\
echo "=== Verificando variables de entorno ==="\n\
php check-env.php || echo "⚠️  Error en verificación de env"\n\
echo "=== Verificando estado de base de datos ==="\n\
php check-db-status.php || echo "⚠️  Error en diagnóstico"\n\
echo "=== Verificando usuario inicial ==="\n\
php create-admin-user.php || echo "⚠️  No se pudo crear usuario inicial"\n\
echo "=== Iniciando servidor ==="\n\
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' > /start.sh && chmod +x /start.sh

# Comando para iniciar la aplicación
CMD ["/start.sh"]
