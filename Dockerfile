# syntax=docker/dockerfile:1.7
#
# Imagen de produccion de Lab-Reserva en tres etapas:
#   assets -> compila el frontend (Vue + Vite) con pnpm.
#   app    -> PHP-FPM con vendor/ sin dependencias de desarrollo y los assets ya compilados.
#             Es la misma imagen para php-fpm, el worker de colas y el scheduler.
#   web    -> nginx con SOLO public/ para servir estaticos y reenviar PHP a `app`.
#
# El .env NO entra en la imagen (.dockerignore): toda la configuracion llega por
# variables de entorno desde compose.yaml, y el entrypoint la cachea al arrancar.

# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

# pnpm 11 lee la version del lockfile actual; corepack se evita porque su
# verificacion de firmas ha roto builds en el pasado.
RUN npm install -g pnpm@11 --no-fund --no-audit

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

# Prefijo bajo el que se sirve la app ('' en la raiz, '/lab-reserva' en el
# despliegue compartido). Vite lo necesita al compilar: ASSET_URL fija la base
# de los chunks (laravel-vite-plugin la lee) y VITE_BASE_PATH la del router y
# del cliente HTTP (resources/js/utils/basePath.js).
ARG APP_BASE_PATH=""
ENV ASSET_URL=${APP_BASE_PATH} \
    VITE_BASE_PATH=${APP_BASE_PATH}

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN pnpm build

# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS app

# install-php-extensions resuelve por si solo las dependencias de compilacion
# de cada extension y las limpia despues: la imagen queda pequena.
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql bcmath opcache pcntl \
    && apk add --no-cache su-exec

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Primero solo las dependencias: esta capa se reutiliza mientras no cambie el lock.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
        --no-interaction --no-progress

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/lab-reserva.ini /usr/local/etc/php/conf.d/99-lab-reserva.ini
COPY docker/php/entrypoint.sh /usr/local/bin/lab-reserva-entrypoint
RUN chmod +x /usr/local/bin/lab-reserva-entrypoint

ENTRYPOINT ["lab-reserva-entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
