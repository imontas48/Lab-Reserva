#!/bin/sh
# Arranque comun de los contenedores PHP (php-fpm, worker de colas y scheduler).
#
# 1. Espera a que la base de datos acepte conexiones.
# 2. Solo el contenedor con LAB_RESERVA_ROLE=app ejecuta las migraciones: si lo
#    hicieran los tres a la vez se pisarian entre si.
# 3. Cachea configuracion, rutas, vistas y eventos con las variables de entorno
#    actuales. Se hace en cada arranque porque cada contenedor tiene su propio
#    sistema de ficheros y porque asi un cambio en .env solo exige reiniciar.
# 4. Marca /tmp/ready para el healthcheck de compose: queue, scheduler y web
#    no arrancan hasta que app ha migrado.
set -eu

cd /var/www/html

: "${DB_HOST:=db}"
: "${DB_PORT:=3306}"
: "${LAB_RESERVA_ROLE:=worker}"

artisan() {
    su-exec www-data php artisan "$@"
}

echo "[entrypoint] esperando a la base de datos ${DB_HOST}:${DB_PORT}..."
intentos=0
until php -r 'new PDO(
        sprintf("mysql:host=%s;port=%s", getenv("DB_HOST"), getenv("DB_PORT")),
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD")
    );' >/dev/null 2>&1; do
    intentos=$((intentos + 1))
    if [ "$intentos" -ge 60 ]; then
        echo "[entrypoint] la base de datos no respondio en 60 intentos, abortando" >&2
        exit 1
    fi
    sleep 2
done

if [ "$LAB_RESERVA_ROLE" = "app" ]; then
    echo "[entrypoint] ejecutando migraciones..."
    artisan migrate --force --no-interaction
fi

artisan config:cache --no-ansi
artisan route:cache --no-ansi
artisan view:cache --no-ansi
artisan event:cache --no-ansi

touch /tmp/ready
echo "[entrypoint] listo (${LAB_RESERVA_ROLE}); ejecutando: $*"

exec "$@"
