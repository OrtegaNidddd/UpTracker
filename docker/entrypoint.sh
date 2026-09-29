#!/bin/sh
set -e

# Asegurar directorios de almacenamiento y permisos
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/www/html/database

# Inicializar base de datos SQLite si está configurada y no existe
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo "Creando base de datos SQLite..."
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database 2>/dev/null || true
    chmod -R 775 /var/www/html/database 2>/dev/null || true
fi

# Ajustar permisos para el servidor web y limpiar caché heredada
rm -f /var/www/html/bootstrap/cache/*.php
rm -f /var/www/html/public/hot
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Generar APP_KEY si no está provista
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY no detectada. Generando llave de cifrado..."
    php artisan key:generate --force
fi

# Ejecutar migraciones automáticamente si no está deshabilitado
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Ejecutando migraciones de base de datos..."
    php artisan migrate --force --graceful
fi

# Optimizar cachés en producción
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizando cachés de Laravel..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "Iniciando UpTracker con Supervisord..."
exec "$@"
