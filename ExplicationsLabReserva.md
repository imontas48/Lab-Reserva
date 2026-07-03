# Reporte Completo del Proyecto: Lab-Reserva

Este documento contiene una explicación detallada del sistema **Lab-Reserva**, abarcando su arquitectura, tecnologías implementadas, base de datos, flujos de trabajo, componentes y sistema de seguridad.

---

## 1. Proceso de Creación del Proyecto

El proyecto **Lab-Reserva** fue concebido y desarrollado bajo estrictas directivas de calidad, clean code y escalabilidad (documentadas en `CLAUDE.md`). Se definió una arquitectura de **SPA (Single Page Application)** separando claramente el Backend del Frontend.

- **Fase 1 (Fundamentos - Completada):** Configuración del proyecto, integración de API RESTful con Laravel, configuración de autenticación segura vía Laravel Sanctum, enrutamiento en Vue 3 y gestión del estado global con Pinia.
- **Fase 2 (En progreso):** Implementación de las vistas CRUD completas (Laboratorios, Equipos, Software y Reservas) y creación de componentes UI base (DataTables, Modales, Formularios).
- **Fase 3 y 4 (Planeadas):** Optimizaciones, testing (Vitest, Playwright), CI/CD y notificaciones en tiempo real.

El desarrollo ha priorizado la seguridad (protección CSRF automática y validaciones estrictas), el rendimiento (Lazy Loading, Vite Code Splitting) y la experiencia del desarrollador.

---

## 2. Stack Tecnológico

El ecosistema de la aplicación se divide en un stack moderno de tecnologías robustas:

### Backend (API RESTful)
- **Framework:** Laravel 11/12 LTS (PHP 8.2+)
- **Base de Datos:** MySQL / PostgreSQL
- **Autenticación:** Laravel Sanctum (Cookies HTTP-Only para SPA)
- **Transformación de Datos:** Laravel API Resources
- **Validación:** Form Requests dedicados

### Frontend (SPA)
- **Framework Core:** Vue 3 (Composition API + `<script setup>`)
- **Herramienta de Build:** Vite 7
- **Gestión de Estado:** Pinia 3
- **Enrutamiento:** Vue Router 4
- **Estilos:** Tailwind CSS 3 (Utility-first)
- **Cliente HTTP:** Axios (con interceptores para CSRF y errores)
- **Componentes extra:** FullCalendar (para reservas), Vue Datepicker, VeeValidate + Yup (para validación de formularios).

---

## 3. Base de Datos (Estructura y Modelos)

El esquema de la base de datos es relacional, optimizado y estructurado de la siguiente manera:

- **users:** Tabla principal de usuarios (Estudiantes, Profesores, Administradores).
- **labs:** Laboratorios disponibles en la institución.
- **equipment:** Equipos (computadoras, proyectores) físicos. *Un equipo pertenece a un laboratorio.*
- **software:** Catálogo de software instalable.
- **equipment_software:** Tabla pivote (N:M) que indica qué software está instalado en qué equipo.
- **reservations:** Registro de las reservas. Relaciona `user_id` con `equipment_id`, con estado (`confirmed`, `cancelled`, `completed`) y rangos de fecha/hora (`start_time`, `end_time`).
- **Tablas de Seguridad (Roles y Permisos):**
  - `roles`, `permissions`, `role_permissions` (N:M entre roles y permisos).
  - `user_roles` (Asignación de roles a usuarios).
  - `group_role_assignments` (Asignaciones grupales automáticas).
  - `permission_overrides` (Excepciones o permisos específicos otorgados/denegados directamente a un usuario).

---

## 4. Componentes y Arquitectura del Frontend

La interfaz de usuario está altamente componentizada y dividida para fomentar la reutilización:

- **Componentes Base (UI):** `AppTextField`, `AppSelect`, `AppDatePicker`, `AppButton`, `AppModal`, `DataTable`. Estos componentes envuelven a TailwindCSS para mantener un estilo cohesivo en toda la app.
- **Composables (Lógica reutilizable):** Archivos que extraen la lógica repetitiva (peticiones API, manejo de estado UI), por ejemplo, `useAuth`, `useApi`, `useForm`.
- **Pinia Stores:** El estado está dividido, por ejemplo `auth.js` administra todo el flujo de inicio de sesión, obtención del usuario actual, comprobación de roles, y limpieza de datos en el logout.
- **Router:** Protegido con *Navigation Guards* globales que interceptan la navegación si el usuario no tiene una sesión o carece de permisos de administrador (`requiresAuth`, `requiresAdmin`).

---

## 5. Módulos y Flujos de la Aplicación

### A. Módulo de Autenticación
- **Flujo:** El usuario envía credenciales a `/api/v1/login` -> Interceptor de Axios maneja automáticamente la obtención del Token CSRF -> Laravel Sanctum valida credenciales y devuelve una cookie HTTP-Only -> Pinia Store actualiza el estado local (`isAuthenticated = true`) -> Vue Router redirige al Dashboard.

### B. Módulo de Dashboard
- **Funcionalidad:** Vista inicial. Al montar, ejecuta un request `GET /api/v1/dashboard/stats`.
- **Datos mostrados:** Cuenta de Labs disponibles, total de Equipos, Reservas Activas, total de Software y una lista de las próximas 5 reservas de la persona autenticada.

### C. Módulo de Laboratorios y Equipos
- **Funcionalidad:** Permite a los administradores gestionar los laboratorios físicos y a qué computadoras/recursos corresponden.
- **Controladores Flacos:** Las peticiones a `/api/v1/labs` y `/api/v1/equipment` interactúan con clases de servicio (`LabService`, `EquipmentService`) que manejan la lógica fuerte de base de datos y validan disponibilidades.

### D. Módulo de Reservas
- **Flujo de Creación:** Un estudiante selecciona un laboratorio -> Elige un equipo disponible y un rango de horas -> Envía solicitud -> El `ReservationService` en Backend valida que el rango de horas NO colisione con otras reservas del mismo equipo (evitando Overbooking) -> Inserta en DB con estado `confirmed`.
- **Gestión:** Usuarios pueden ver `/my-reservations` y cancelar.

---

## 6. Explicación de los Tipos de Usuarios (Roles)

1. **Estudiante (Student):** 
   - Es el rol base. Puede visualizar la disponibilidad de los laboratorios y realizar reservas en base a la política de horarios. Solo tiene acceso a visualizar y gestionar sus **propias** reservas.
2. **Profesor (Teacher):**
   - Tiene permisos similares a los estudiantes, pero con mayores privilegios o prioridad al reservar equipos. Puede reservar computadoras específicas que tengan el software requerido para sus clases (por ejemplo, AutoCAD, MATLAB).
3. **Administrador (Admin):**
   - Acceso total. Puede crear, editar y eliminar Laboratorios, Software y Equipos. Gestiona todas las reservas de la plataforma, tiene la potestad de cancelar cualquier reserva y configurar roles y permisos de los usuarios.

---

## 7. Sistema de Permisos y Roles (ACL)

El sistema cuenta con un Control de Acceso avanzado, separado del modelo básico de usuarios:
- **Roles:** Agrupaciones lógicas (Admin, Teacher, Student).
- **Permisos:** Acciones granulares (ej. `create-reservation`, `delete-lab`, `manage-users`).
- **Asignación:** A través de la tabla `user_roles`. 
- **Overrides (Sobreescrituras):** Permite que, aunque un profesor no tenga permiso global para una acción, se le pueda habilitar vía `permission_overrides` para un escenario particular, dando una flexibilidad enorme al administrador.

Las **Policies** de Laravel (`ReservationPolicy`, `LabPolicy`) evalúan estos permisos antes de permitir que el controlador procese la acción.

---

## 8. Elementos para un Diagrama de Flujo (Creación de Reserva)

Para crear un diagrama de flujo de la funcionalidad principal (Reservar un equipo), utiliza estos nodos y caminos:

1. **Inicio:** Usuario en la plataforma.
2. **Decisión:** ¿Está el usuario autenticado?
   - *NO:* Redirigir al Login -> Autenticación exitosa -> Volver al inicio.
   - *SÍ:* Continuar a Vista de Laboratorios.
3. **Acción (UI):** Usuario selecciona un laboratorio, un equipo y fecha/hora.
4. **Validación (Frontend):** VeeValidate comprueba formato de fecha y campos.
5. **Acción (Red):** Petición POST a `/api/v1/reservations`.
6. **Validación (Backend - Controller/Request):** `StoreReservationRequest` comprueba los tipos de datos enviados y CSRF.
7. **Proceso Lógico (Backend - Service):** `ReservationService` consulta a Base de Datos: *¿El equipo 'X' está disponible en el rango Y a Z?*
8. **Decisión:** 
   - *No disponible:* Devolver HTTP 422 o 409 (Conflicto). Vue muestra alerta "Horario Ocupado".
   - *Disponible:* `ReservationService` guarda la reserva.
9. **Final:** Devolver respuesta exitosa (HTTP 201). Vue actualiza store, redirige al Dashboard de reservas y notifica éxito (Toast Notification).
