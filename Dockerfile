# ==============================================================================
# UpTracker Production Multi-Stage Dockerfile
# PHP 8.3 FPM + Nginx + Node 20 (Vite) + Laravel Reverb + Workers + Scheduler
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Compilación de Frontend y Assets Estáticos (Vite)
# ------------------------------------------------------------------------------
FROM node:20-alpine AS frontend-builder
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ------------------------------------------------------------------------------
# Stage 2: Instalación de Dependencias de PHP (Composer)
# ------------------------------------------------------------------------------
FROM composer:2 AS composer-builder
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ------------------------------------------------------------------------------
# Stage 3: Imagen de Ejecución en Producción (PHP 8.3 + Nginx + Supervisord)
# ------------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS runner

# Instalar dependencias del sistema y herramientas de servidor
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    bash \
    sqlite \
    sqlite-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers

# Instalar extensiones de PHP necesarias para Laravel y Reverb
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        pcntl \
        opcache \
        zip \
        exif \
        gd \
        intl

WORKDIR /var/www/html

# Copiar configuraciones de Nginx, PHP y Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-uptracker.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar código fuente de la aplicación
COPY . .

# Copiar artefactos de Composer (Stage 2) y Vite (Stage 1)
COPY --from=composer-builder /app/vendor /var/www/html/vendor
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Optimizar estructura de almacenamiento y permisos
RUN mkdir -p /var/www/html/storage/logs \
            /var/www/html/storage/framework/sessions \
            /var/www/html/storage/framework/views \
            /var/www/html/storage/framework/cache/data \
            /var/www/html/bootstrap/cache \
            /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/storage \
                                 /var/www/html/bootstrap/cache \
                                 /var/www/html/database \
    && chmod -R 775 /var/www/html/storage \
                    /var/www/html/bootstrap/cache

# Exponer el puerto HTTP (Nginx) y WebSocket (Laravel Reverb)
EXPOSE 80 8080

# Chequeo de salud nativo de Laravel (/up)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
