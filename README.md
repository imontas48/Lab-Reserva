# Lab-Reserva

## Descripción del Sistema
Lab-Reserva es un sistema web integral diseñado para la gestión eficiente de reservas en laboratorios de cómputo. Permite a los estudiantes y profesores visualizar la disponibilidad en tiempo real y reservar equipos u horarios, mientras que proporciona a los administradores herramientas centralizadas para gestionar laboratorios, inventario de equipos, software y horarios.

## Objetivo
El objetivo principal de Lab-Reserva es optimizar y digitalizar el proceso de uso de los laboratorios de cómputo, eliminando conflictos de horarios, mejorando el control de los recursos (equipos y software) y facilitando el acceso equitativo para toda la comunidad académica a través de una interfaz moderna y accesible.

## Tecnologías Utilizadas

### Backend (API REST)
- **PHP 8.2+**
- **Laravel** - Framework principal PHP
- **Laravel Sanctum** - Autenticación basada en tokens para APIs
- **MySQL** - Sistema de gestión de base de datos

### Frontend (SPA)
- **Vue 3** - Framework progresivo de JavaScript (Composition API + `<script setup>`)
- **Vite** - Herramienta de compilación rápida y servidor de desarrollo
- **Pinia** - Gestión de estado global
- **Vue Router** - Enrutamiento del lado del cliente
- **Tailwind CSS** - Framework CSS utility-first para diseño responsivo
- **Axios** - Cliente HTTP para comunicación con la API
- **SweetAlert2 & Vue Toastification** - Notificaciones, modales y alertas

---

## Instrucciones de Instalación y Ejecución

### Prerrequisitos
Asegúrate de tener instalados los siguientes componentes en tu entorno de desarrollo:
- **PHP** >= 8.2
  - *Nota: Asegúrate de tener habilitada la extensión `fileinfo` en tu `php.ini`.*
- **Composer**
- **Node.js** >= 18.x
- **pnpm** (Gestor de paquetes principal del proyecto)
- **Base de datos** (MySQL 8.0+)

### 1. Clonar el Repositorio

```bash
git clone <repository-url>
cd Lab-Reserva
```

### 2. Configurar el Backend (Laravel)

Instalar las dependencias de PHP usando Composer:
```bash
composer install
```

Configurar las variables de entorno:
```bash
cp .env.example .env
php artisan key:generate
```

Edita el archivo `.env` para configurar la conexión a la base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lab_reserva
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

Ejecutar las migraciones y seeders para estructurar e inicializar la base de datos:
```bash
php artisan migrate:fresh --seed
```

Crear el enlace simbólico para el almacenamiento público (si el proyecto maneja subida de archivos/imágenes):
```bash
php artisan storage:link
```

### 3. Configurar el Frontend (Vue 3 + Vite)

Instalar las dependencias de Node.js utilizando `pnpm`:
```bash
pnpm install
```

### 4. Ejecutar la Aplicación

Deberás ejecutar dos procesos en terminales separadas:

**Terminal 1 (Servidor Backend):**
Si usas Laragon o XAMPP, asegúrate de que tu entorno local esté corriendo (Apache/Nginx). Alternativamente, puedes usar el servidor integrado de PHP:
```bash
php artisan serve
```

**Terminal 2 (Servidor Frontend Vite):**
```bash
pnpm run dev
```

La aplicación estará disponible en la URL que indique Vite (usualmente `http://localhost:5173`) o podrá ser servida directamente desde el backend a través de la URL de tu entorno local (ej. `http://lab-reserva.test` usando Laragon).

---

## Scripts Disponibles (Frontend)

- `pnpm run dev` - Inicia el servidor de desarrollo Vite con Hot-Module Replacement (HMR).
- `pnpm run build` - Compila y minimiza los assets para producción.

## Contribución
Este proyecto sigue directrices estrictas de **Clean Code** y una arquitectura basada en componentes modulares en el Frontend y Controladores delgados / Servicios en el Backend. Consulta la documentación interna ubicada en la carpeta `docs/` y el archivo `ExplicationsLabReserva.md` antes de realizar aportes estructurales importantes.

## Licencia
**Este proyecto NO es software libre ni de código abierto.** El código se publica únicamente para su lectura y consulta con fines académicos y de referencia. Queda prohibido copiarlo, modificarlo, redistribuirlo, desplegarlo o usarlo con fines comerciales o institucionales sin autorización expresa y por escrito del titular. Consulta el archivo [`LICENSE`](LICENSE) para conocer los términos completos en español e inglés.

Copyright © 2026 Idelfin Montas Espinal. Todos los derechos reservados.
