FROM php:8.2-cli

# Variables de entorno
ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de composer primero (para cache de Docker)
COPY composer.json composer.lock ./

# Instalar dependencias de PHP (con manejo de errores)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts || \
    (composer clear-cache && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts)

# Copiar el resto de la aplicación
COPY . .

# Crear directorios necesarios y configurar permisos
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    || true

# Ejecutar scripts de composer después de copiar todo
RUN composer dump-autoload --optimize || true

# Exponer puerto (Render usa la variable $PORT)
EXPOSE 8000

# Comando para iniciar la aplicación
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
