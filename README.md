# Lab-Reserva<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



Sistema web de gestión de reservas para laboratorios de cómputo. Permite a estudiantes y profesores visualizar disponibilidad y reservar equipos online, mientras que los administradores gestionan laboratorios, equipos y horarios de manera centralizada.<p align="center">

<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>

## 🚀 Stack Tecnológico<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>

<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>

### Backend<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>

- **Laravel 12 LTS** - Framework PHP</p>

- **Laravel Sanctum** - Autenticación API

- **MySQL/PostgreSQL** - Base de datos## About Laravel



### FrontendLaravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- **Vue 3** - Framework JavaScript (Composition API + `<script setup>`)

- **Vite** - Build tool y dev server- [Simple, fast routing engine](https://laravel.com/docs/routing).

- **Pinia** - Gestión de estado- [Powerful dependency injection container](https://laravel.com/docs/container).

- **Tailwind CSS** - Framework CSS utility-first- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.

- **Axios** - Cliente HTTP- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

- Database agnostic [schema migrations](https://laravel.com/docs/migrations).

## 📋 Prerequisitos- [Robust background job processing](https://laravel.com/docs/queues).

- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

- **PHP**: >= 8.2

- **Composer**: Última versiónLaravel is accessible, powerful, and provides tools required for large, robust applications.

- **Node.js**: >= 18.x

- **NPM**: >= 9.x## Learning Laravel

- **MySQL/PostgreSQL**: 8.0+ / 13+

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

### ⚠️ Configuración Crítica de PHP

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

**IMPORTANTE:** Debes habilitar la extensión `fileinfo` en PHP:

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

1. Abre tu `php.ini` (en Laragon: Click derecho > PHP > php.ini)

2. Busca `;extension=fileinfo`## Laravel Sponsors

3. Quita el `;` para descomentarla: `extension=fileinfo`

4. Guarda y reinicia el servidorWe would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).



## 📦 Instalación### Premium Partners



### 1. Clonar el repositorio- **[Vehikl](https://vehikl.com)**

- **[Tighten Co.](https://tighten.co)**

```bash- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**

git clone <repository-url>- **[64 Robots](https://64robots.com)**

cd Lab-Reserva- **[Curotec](https://www.curotec.com/services/technologies/laravel)**

```- **[DevSquad](https://devsquad.com/hire-laravel-developers)**

- **[Redberry](https://redberry.international/laravel-development)**

### 2. Instalar dependencias de Backend- **[Active Logic](https://activelogic.com)**



```bash## Contributing

composer install --ignore-platform-req=ext-fileinfo

```Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).



> **Nota:** Una vez habilitada la extensión `fileinfo`, ejecuta `composer install` sin el flag.## Code of Conduct



### 3. Configurar variables de entornoIn order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).



```bash## Security Vulnerabilities

cp .env.example .env

php artisan key:generateIf you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

```

## License

Edita el archivo `.env` y configura tu base de datos:

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lab_reserva
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Configurar Sanctum (después de habilitar fileinfo)

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 5. Ejecutar migraciones

```bash
php artisan migrate
```

### 6. Instalar dependencias de Frontend

```bash
npm install
```

### 7. Compilar assets de desarrollo

```bash
npm run dev
```

## 🎯 Estructura del Proyecto

### Backend (Laravel)

```
app/
├── Http/
│   ├── Controllers/      # Controladores (FLACOS - solo orquestan)
│   ├── Requests/         # Form Requests (validación)
│   ├── Resources/        # API Resources (transformación de datos)
│   └── Middleware/       # Middleware personalizado
├── Models/               # Modelos Eloquent (relaciones, scopes, casts)
├── Policies/             # Policies (autorización)
└── Services/             # Services (lógica de negocio)
```

### Frontend (Vue 3)

```
resources/
├── css/
│   └── app.css          # Tailwind directives
└── js/
    ├── components/       # Componentes Vue (pequeños y reutilizables)
    ├── composables/      # Composables (lógica reutilizable)
    ├── stores/           # Stores de Pinia (estado global)
    ├── utils/            # Utilidades y helpers
    │   └── api.js       # Cliente Axios configurado
    └── app.js           # Punto de entrada Vue
```

## 🏗️ Arquitectura y Reglas (CLAUDE.md)

Este proyecto sigue **estrictamente** las reglas definidas en `CLAUDE.md`:

### Principios Fundamentales

1. **Clean Code**: SRP, DRY, nombres descriptivos
2. **Seguridad primero**: Validación exhaustiva, autorización explícita
3. **Código auto-explicativo**: Comentarios explican el "porqué", no el "qué"

### Laravel - Backend

- ✅ **Controladores flacos**: Solo reciben Request, invocan Service, devuelven Response
- ✅ **Services**: Toda la lógica de negocio reside aquí
- ✅ **Form Requests**: Validación obligatoria en clases dedicadas
- ✅ **API Resources**: Transformación estandarizada de respuestas
- ✅ **Policies**: Autorización con Policies de Laravel
- ✅ **Eager Loading**: Evitar N+1 con `with()`

### Vue 3 - Frontend

- ✅ **Composition API + `<script setup>`**: Única forma permitida
- ✅ **Componentes pequeños**: Máximo ~150 líneas
- ✅ **Props down, Emits up**: Comunicación unidireccional
- ✅ **Pinia Stores**: Estado compartido modularizado
- ✅ **Composables**: Lógica reutilizable extraída
- ✅ **Tailwind CSS**: Utility-first styling
- ✅ **Estilos scoped**: Evitar colisiones globales

## 🛠️ Comandos Útiles

### Desarrollo

```bash
# Servidor de desarrollo Laravel (requiere Laragon o similar)
php artisan serve

# Compilación en tiempo real de assets (Vite)
npm run dev

# Compilación para producción
npm run build
```

### Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Refrescar DB y ejecutar seeders
php artisan migrate:fresh --seed

# Crear migración
php artisan make:migration create_table_name

# Crear seeder
php artisan make:seeder TableSeeder
```

### Generación de Código

```bash
# Modelo con migración, factory, seeder, policy, controller y resource
php artisan make:model ModelName -mfsp --resource --api

# Controller
php artisan make:controller ControllerName

# Form Request
php artisan make:request StoreResourceRequest

# API Resource
php artisan make:resource ResourceCollection

# Policy
php artisan make:policy ResourcePolicy

# Service (manual - crear en app/Services/)
# No hay comando artisan, crear manualmente
```

### Testing

```bash
# Ejecutar tests
php artisan test

# Ejecutar tests con coverage
php artisan test --coverage
```

## 🔐 Autenticación con Sanctum

Laravel Sanctum proporciona autenticación API mediante tokens. Flujo básico:

1. Usuario se autentica: `POST /api/login`
2. Sanctum genera token
3. Frontend incluye token en headers: `Authorization: Bearer {token}`
4. Rutas protegidas con middleware: `auth:sanctum`

## 📝 Convenciones de Código

### Nombres

- **Clases**: `PascalCase` (ej. `ReservationService`)
- **Métodos/Funciones**: `camelCase` (ej. `createReservation()`)
- **Variables**: `camelCase` (ej. `$userId`)
- **Constantes**: `UPPER_SNAKE_CASE` (ej. `MAX_RESERVATIONS`)
- **Componentes Vue**: `PascalCase` (ej. `ReservationForm.vue`)

### Comentarios

```php
/**
 * Crea una nueva reserva
 * 
 * Valida disponibilidad del equipo y crea la reserva
 * si el laboratorio está disponible en el horario solicitado.
 * 
 * @param array $data Datos de la reserva
 * @return Reservation
 * @throws ReservationException Si el equipo no está disponible
 */
public function createReservation(array $data): Reservation
```

## 🤝 Contribución

1. Lee y comprende `CLAUDE.md` completamente
2. Sigue las reglas de Clean Code
3. Crea branch para feature: `git checkout -b feature/nombre-feature`
4. Commit con mensajes descriptivos: `git commit -m "feat: agregar validación de disponibilidad"`
5. Push y crea Pull Request

## 📄 Licencia

[Definir licencia]

## 👥 Autores

- Desarrollado siguiendo las directivas de **El Arquitecto** (Claude Sonnet)
- Basado en las reglas estrictas de `CLAUDE.md`

---

**⚠️ IMPORTANTE:** Antes de comenzar el desarrollo, asegúrate de habilitar la extensión `fileinfo` en PHP y ejecutar `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` para finalizar la configuración de Sanctum.
