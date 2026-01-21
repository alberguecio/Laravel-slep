FROM php:8.2-cli

# Instalar dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de composer primero (para cache de Docker)
COPY composer.json composer.lock ./

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copiar el resto de la aplicación
COPY . .

# Crear directorios necesarios y configurar permisos
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Exponer puerto (Render usa la variable $PORT)
EXPOSE 8000

# Comando para iniciar la aplicación
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
