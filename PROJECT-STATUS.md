# Estado Actual del Proyecto Lab-Reserva

**Fecha**: 12 de octubre de 2026
**Estado**: en producción (contenedores Docker detrás de proxy inverso)
**Rama vigente**: `feat/registro-actividades` (incluye `chore/actualizar-dependencias-20261008`)

---

## Resumen

```
┌──────────────────────────────────────────────────────────────────┐
│                         LAB-RESERVA                              │
├──────────────────────────────────────────────────────────────────┤
│  Estado general:            EN PRODUCCIÓN                        │
│  Backend API (Laravel 12):  COMPLETO                             │
│  Frontend (Vue 3):          COMPLETO                             │
│  Control de acceso (RBAC):  EFECTIVO EN TODAS LAS RUTAS          │
│  Registro de actividades:   ACTIVO (solo lectura, solo admin)    │
│  Pruebas:                   234 PHPUnit + 48 Vitest, en verde    │
│  CI:                        GitHub Actions (.github/workflows)   │
└──────────────────────────────────────────────────────────────────┘
```

---

## Módulos

| Módulo | Qué cubre |
|---|---|
| Autenticación | Login con token Sanctum, registro público (siempre como estudiante), recuperación y cambio de contraseña, contraseña temporal obligatoria en altas por invitación. |
| Catálogo | Laboratorios, equipos y software (CRUD con bajas lógicas), software por equipo, plano del laboratorio y mapa con estado en tiempo real. |
| Reservas | Reserva de equipo y de laboratorio completo, flujo de aprobación, series recurrentes, cuotas por rol, check-in con código, inasistencias con bloqueo, recordatorios y cierre automático. |
| Horarios | Horario de apertura por laboratorio, cierres y festivos, periodos académicos. |
| Incidencias | Reporte por cualquier usuario y atención/resolución por el administrador. |
| Administración | Gestión de usuarios (alta con contraseña temporal, rol base, bajas, desbloqueo), reportes de uso con exportación CSV. |
| Seguridad | Roles, permisos del sistema, reglas de grupo, excepciones de permiso por usuario y **registro de actividades**. |
| Notificaciones | Centro de notificaciones y campana con contador. |

### Registro de actividades (2026-10-12)

- Tabla `activity_logs` de solo anexado. Guarda autor (con copia del nombre y del correo), acción, sujeto, cambio antes/después, IP, navegador y origen (web o consola).
- Captura automática por eventos de modelo (`App\Observers\ActivityObserver`) y explícita donde no hay eventos: inicio y cierre de sesión, intentos fallidos, cambios de permisos de un rol, software de un equipo, horario de apertura, plano, cierres automáticos del programador y exportación de reportes.
- Contraseñas, `remember_token` y códigos de asistencia se guardan como `[oculto]`.
- Inmutable: la única ruta es `GET /api/v1/activity-logs` y el modelo lanza excepción al editar o borrar. Se conserva para siempre (no hay purga).
- Solo lo ve el rol base `admin` (`ActivityLogPolicy`); no se puede conceder por RBAC ni por excepción de permiso.
- Límite conocido: la inmutabilidad es de aplicación. Quien tenga acceso directo a la base de datos podría borrar filas.

---

## Estructura

```
Lab-Reserva/
├── app/
│   ├── Console/Commands/        4 comandos (lab:invite-user, lab:make-admin, cierre y recordatorios)
│   ├── Http/Controllers/Api/   22 controladores (finos)
│   ├── Http/Requests/          47 Form Requests
│   ├── Http/Resources/         17 API Resources
│   ├── Models/                 15 modelos
│   ├── Observers/               1 (registro de actividades)
│   ├── Policies/               10 policies
│   ├── Services/               16 servicios con la lógica de negocio
│   └── Support/                 PermissionCatalog, ReservationLimits, InvitedUser
├── database/migrations/        24 migraciones
├── resources/js/
│   ├── views/                  37 vistas
│   ├── components/             35 componentes
│   ├── composables/            18 composables
│   ├── stores/                  auth, theme (Pinia)
│   └── router/index.js          rutas con guards por sesión, rol y permiso
├── tests/                      36 clases PHPUnit + 8 specs de Vitest
├── docker/ + Dockerfile + compose.yaml
└── docs/                        guías (despliegue, pruebas, componentes, reglas)
```

---

## Métricas

```
┌─────────────────────────────┬──────────┐
│ Rutas API (/api)            │   103    │
│ Rutas SPA                   │   ~40    │
│ Pruebas PHPUnit             │   234    │
│ Pruebas Vitest              │    48    │
│ PHPStan (larastan)          │ sin errores │
│ Laravel Pint                │ sin cambios │
└─────────────────────────────┴──────────┘
```

---

## Stack tecnológico

### Backend
- **Laravel 12.69** sobre **PHP 8.4** (imagen `php:8.4-fpm-alpine`)
- **Laravel Sanctum 4** (tokens Bearer)
- **MariaDB 11.4** en producción; MySQL de Laragon en local y en pruebas

### Frontend
- **Vue 3.5.43**, **Vue Router 4.6**, **Pinia 3.0**
- **Axios 1.20**, **Tailwind CSS 3.4.19**, **Vite 7.3.7**
- **FullCalendar 6** (calendario de reservas), **Chart.js 4** (reportes), **VeeValidate 4 + Yup** (formularios)

### Calidad
- PHPUnit 11 sobre MySQL (`lab_reserva_testing`), Vitest 5, PHPStan/Larastan, Pint
- GitHub Actions: estilo, análisis estático, pruebas, build y auditorías de dependencias

---

## Despliegue

Ver `docs/DEPLOY-DOCKER.md`. Cinco contenedores (`db`, `app`, `queue`, `scheduler`, `web`) con `restart: unless-stopped`, publicados solo en `127.0.0.1` y servidos por el nginx del host como proxy TLS bajo `/lab-reserva`.

- El código se publica con `git ls-files ... | tar` por ssh; el `.env` de producción no se versiona.
- El contenedor `app` migra al arrancar.
- Antes de cada publicación: volcado de la base, copia del código y etiquetas `rollback-AAAAMMDD` de las imágenes.
- Si cambia `PermissionCatalog`, resembrar con `php artisan db:seed --class=RbacSeeder --force`.

Tareas programadas (contenedor `scheduler`):
- `reservations:complete-expired` cada 5 minutos
- `reservations:send-reminders` cada hora

---

## Comandos de desarrollo

```bash
# Servidor, cola, logs y Vite a la vez
composer run dev

# Pruebas
php artisan test          # PHPUnit (base lab_reserva_testing)
pnpm test                 # Vitest

# Calidad
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G

# Assets de producción
pnpm build

# Altas desde consola
php artisan lab:make-admin
php artisan lab:invite-user
```

En local, `AdminUserSeeder` crea `admin@lab-reserva.test` (se omite en producción).

---

## Pendientes y riesgos conocidos

- La inmutabilidad del registro de actividades no protege frente a acceso directo a la base de datos (triggers de MariaDB o usuario sin `DELETE` quedan como mejora futura).
- La barra de navegación de escritorio necesita unos 1280 px de ancho; por debajo conviene el menú móvil.
- Avisos de dependencias sin arreglo aplicable, solo en el build de Tailwind 3: `braces` 3.0.3 y `postcss-selector-parser` 6.

---

**Última actualización**: 12 de octubre de 2026
