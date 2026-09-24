# Despliegue en Docker (servidor compartido con otro sitio)

Este documento describe el despliegue de referencia: Lab-Reserva corre en
contenedores en un servidor Ubuntu 24.04 (en los ejemplos, `deploy@servidor`)
que ya sirve otro sitio PHP en `https://portal.ejemplo.edu` con nginx + php-fpm
nativos. Se publica en **https://portal.ejemplo.edu/lab-reserva** reutilizando
el dominio y el certificado del sitio principal. Ninguna de las dos
instalaciones comparte nada mas: Lab-Reserva lleva su propia MariaDB en un
contenedor y en el nginx del host solo se anade una linea `include` al vhost
del sitio principal. Para una guia completa, con el escenario de subdominio
propio, ver `docs/Guia-Instalacion-Lab-Reserva.docx`.

## Como funciona el subdirectorio

- El nginx del host recorta `/lab-reserva/` y reenvia al contenedor con la
  cabecera `X-Forwarded-Prefix: /lab-reserva`. Laravel confia en ella
  (`TRUSTED_PROXIES` en `bootstrap/app.php`) y `url()`, `asset()` y las
  redirecciones salen con el prefijo.
- El frontend lo fija en tiempo de compilacion: `APP_BASE_PATH` en el `.env`
  del servidor llega como build arg y se convierte en `ASSET_URL` (base de los
  chunks de Vite) y `VITE_BASE_PATH` (router y cliente HTTP, ver
  `resources/js/utils/basePath.js`). Cambiar el prefijo exige `compose build`.
- Cookies aisladas del sitio principal: `SESSION_PATH=/lab-reserva`,
  `SESSION_SECURE_COOKIE=true` y nombre de sesion derivado de `APP_NAME`.
- `location ^~ /lab-reserva/` en el host gana a las `location ~ \.php$` del
  sitio principal; sin el `^~`, `/lab-reserva/index.php` acabaria en su php-fpm.

## Endurecimiento aplicado

- Contenedor `web` solo en `127.0.0.1:8081`; `db` sin puerto publicado.
- `no-new-privileges` en todos los contenedores; php y nginx sin capacidades
  salvo CHOWN/SETUID/SETGID.
- En el nginx del host: rate limit (10 req/min por IP, zona `autenticacion`) en
  login, register, forgot-password y reset-password, por delante del throttle
  de Laravel; HSTS, nosniff, X-Frame-Options, Referrer-Policy,
  Permissions-Policy y una CSP en modo bloqueo (`script-src 'self'`).
- Ficheros ocultos (`/.env`, `/.git`) devuelven 403 desde el contenedor.
- `APP_DEBUG=false`, `server_tokens off` en ambos nginx, `expose_php=Off`.

## Piezas

| Fichero | Para que |
|---|---|
| `Dockerfile` | Imagen `app` (php-fpm 8.4 + vendor + assets) e imagen `web` (nginx con `public/`). |
| `compose.yaml` | Servicios `db`, `app`, `queue`, `scheduler`, `web`. |
| `docker/php/entrypoint.sh` | Espera la BD, migra (solo `app`), cachea config/rutas/vistas. |
| `docker/php/lab-reserva.ini` | php.ini de produccion (OPcache sin validar timestamps). |
| `docker/nginx/default.conf` | nginx del contenedor `web`. |
| `docker/host-nginx/*.conf` | Vhost y snippet del nginx **del host** (proxy inverso). |

En el servidor el proyecto vive en `~/lab-reserva`. El `.env` de produccion esta
solo ahi y **no se versiona** (CLAUDE.md seccion 6). Variables que difieren del
`.env.example`: `APP_ENV=production`, `APP_DEBUG=false`, `DB_HOST=db`,
`LOG_CHANNEL=stderr` (los logs se leen con `docker compose logs`),
`TRUSTED_PROXIES=*`, `WEB_PORT=8081` y `DB_ROOT_PASSWORD` (solo la usa MariaDB).

## Operacion diaria

```bash
ssh deploy@servidor
cd ~/lab-reserva

docker compose ps                      # estado
docker compose logs -f --tail=100 app  # logs (tambien queue, scheduler, web, db)
docker compose restart                 # aplicar un cambio de .env
docker compose exec app su-exec www-data php artisan lab:make-admin correo@dominio --name="Nombre"
docker compose exec app su-exec www-data php artisan lab:invite-user correo@dominio --name="Nombre" --role=teacher
docker compose exec app su-exec www-data php artisan tinker
```

Crear el primer administrador: el seeder de admin se omite en produccion a
proposito. Hay dos vias: `lab:make-admin`, que pide la contrasena por consola,
o `lab:invite-user ... --role=admin`, que genera una contrasena temporal, la
imprime una sola vez y obliga a cambiarla en el primer inicio de sesion (es el
mismo camino que usa el boton "Nuevo usuario" del panel de administracion).

Si una version nueva amplia el catalogo de permisos (`app/Support/PermissionCatalog.php`),
tras `up -d` hay que resembrar el RBAC, que es idempotente:

```bash
docker compose exec app su-exec www-data php artisan db:seed --class=RbacSeeder --force
```

## Publicar una version nueva

Desde la copia de trabajo en Windows (el codigo se envia con `tar` sobre ssh,
respetando `.gitignore`, asi que ni `.env` ni `node_modules` viajan):

```bash
cd /c/laragon/www/lab-reserva
git ls-files -co --exclude-standard -z | tar --null -czf - -T - \
  | ssh deploy@servidor 'tar -xzf - -C ~/lab-reserva'
ssh deploy@servidor 'cd ~/lab-reserva && docker compose build && docker compose up -d'
```

`up -d` recrea solo los contenedores cuya imagen cambio; el `entrypoint` migra
al arrancar `app`. Los datos persisten en los volumenes `lab-reserva_db-data` y
`lab-reserva_app-storage`.

## Copia de seguridad de la base de datos

```bash
docker compose exec db sh -c 'mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  | gzip > ~/lab-reserva-db-$(date +%Y%m%d).sql.gz
```

## Ficheros del nginx del host

| Fichero del repo | Instalado en |
|---|---|
| `docker/host-nginx/lab-reserva-upstream.conf` | `/etc/nginx/conf.d/lab-reserva-upstream.conf` |
| `docker/host-nginx/lab-reserva-proxy.conf` | `/etc/nginx/snippets/lab-reserva-proxy.conf` |
| `docker/host-nginx/lab-reserva-location.conf` | `/etc/nginx/snippets/lab-reserva-location.conf` |

El vhost del sitio principal (`/etc/nginx/sites-available/portal.ejemplo.edu`)
solo lleva la linea `include snippets/lab-reserva-location.conf;` dentro del
bloque 443. Conviene guardar una copia del vhost antes de ese cambio. Para
retirar Lab-Reserva del dominio basta quitar esa linea y recargar nginx.

Tras cambiar cualquiera de los tres ficheros: copiarlos, `sudo nginx -t` y
`sudo systemctl reload nginx`. Si `nginx -t` falla, nada se recarga y el sitio
principal sigue sirviendo con la configuracion anterior.
