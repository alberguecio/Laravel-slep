#!/bin/bash

# Script postdeploy para configurar Laravel en Elastic Beanstalk
cd /var/app/current

# 1. Crear archivo .env desde variables de entorno
APP_KEY=$(/opt/elasticbeanstalk/bin/get-config environment -k APP_KEY)
APP_ENV=$(/opt/elasticbeanstalk/bin/get-config environment -k APP_ENV)
APP_DEBUG=$(/opt/elasticbeanstalk/bin/get-config environment -k APP_DEBUG)
LOG_CHANNEL=$(/opt/elasticbeanstalk/bin/get-config environment -k LOG_CHANNEL)
DB_CONNECTION=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_CONNECTION)
DB_HOST=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_HOST)
DB_PORT=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_PORT)
DB_DATABASE=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_DATABASE)
DB_USERNAME=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_USERNAME)
DB_PASSWORD=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_PASSWORD)
JWT_SECRET=$(/opt/elasticbeanstalk/bin/get-config environment -k JWT_SECRET)

cat > .env << EOF
APP_NAME=SLEP_Chiloe
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=http://slep-chiloe-env.eba-wqngtwgc.sa-east-1.elasticbeanstalk.com

LOG_CHANNEL=${LOG_CHANNEL:-errorlog}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=file
CACHE_DRIVER=file
JWT_SECRET=${JWT_SECRET}
EOF

chown webapp:webapp .env
chmod 600 .env

# 2. Crear directorios de storage
mkdir -p storage/framework/{sessions,views,cache/data} storage/app/public storage/logs
chown -R webapp:webapp storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 3. Crear archivo de log
touch storage/logs/laravel.log
chown webapp:webapp storage/logs/laravel.log
chmod 664 storage/logs/laravel.log

# 4. Crear config/cache.php si no existe
if [ ! -f config/cache.php ]; then
    cat > config/cache.php << 'CACHEEOF'
<?php
use Illuminate\Support\Str;
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],
    ],
    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
];
CACHEEOF
    chown webapp:webapp config/cache.php
    chmod 644 config/cache.php
fi

# 5. Configurar nginx document root
if [ -f /etc/nginx/conf.d/elasticbeanstalk/php.conf ]; then
    # Verificar si ya tiene location /
    if ! grep -q "^location / {" /etc/nginx/conf.d/elasticbeanstalk/php.conf; then
        # Agregar location / antes del location ~ \.(php|phar)
        sed -i.bak '/^location ~ \\.(php|phar)/i\
location / {\
    try_files $uri $uri/ /index.php?$query_string;\
}\
' /etc/nginx/conf.d/elasticbeanstalk/php.conf
    fi
    
    # Asegurar que el root esté correcto
    sed -i.bak 's|^root /var/www/html;|root /var/app/current/public;|g' /etc/nginx/conf.d/elasticbeanstalk/php.conf
    sed -i.bak 's|^root /usr/share/nginx/html;|root /var/app/current/public;|g' /etc/nginx/conf.d/elasticbeanstalk/php.conf
    
    # Recargar nginx si la configuración es válida
    nginx -t 2>/dev/null && systemctl reload nginx 2>/dev/null || true
fi

# 6. Crear enlace simbólico de storage
if [ ! -L public/storage ]; then
    php artisan storage:link 2>/dev/null || true
fi

# 7. Limpiar cache
php artisan config:clear 2>/dev/null || true






