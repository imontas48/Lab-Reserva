#  Frontend Vue 3 - Lab-Reserva

##  Resumen de Implementación

Hemos completado exitosamente la **Fase 1** del desarrollo del frontend, estableciendo una arquitectura sólida y escalable.

##  Componentes Implementados

### 1. **Cliente HTTP Axios** (`resources/js/utils/api.js`)

#### Características Implementadas:
-  Configuración pre-establecida con `baseURL` apuntando a la API
-  **`withCredentials: true`** - Esencial para Laravel Sanctum
-  **Interceptor de Peticiones (Request Interceptor)**:
  - Obtiene automáticamente el token CSRF antes de operaciones de mutación (POST, PUT, PATCH, DELETE)
  - Evita llamadas redundantes mediante caché del token
  - Excluye rutas específicas (login, register) para evitar bucles
-  **Interceptor de Respuestas (Response Interceptor)**:
  - Manejo centralizado de errores HTTP
  - **Error 401**: Redirige a login automáticamente
  - **Error 419**: Renueva token CSRF y reintenta la petición
  - **Error 422**: Captura errores de validación
  - **Errores 5xx**: Manejo de errores del servidor

#### Flujo de Protección CSRF:
```
1. Usuario intenta hacer POST/PUT/PATCH/DELETE
2. Interceptor detecta que no hay token CSRF
3. Axios llama a /sanctum/csrf-cookie (GET)
4. Laravel establece cookie XSRF-TOKEN
5. Axios lee automáticamente la cookie
6. Axios envía X-XSRF-TOKEN header en la petición original
7. Laravel valida el token
8. Petición completada 
```

#### Importancia de `withCredentials`:
- Sin esta opción, el navegador NO enviará cookies HTTP-only
- Laravel Sanctum almacena el token de sesión en cookies HTTP-only (seguro)
- Sin cookies, la autenticación NO funciona

---

### 2. **Store de Autenticación** (`resources/js/stores/auth.js`)

#### State:
- `user`: Objeto del usuario autenticado o `null`
- `errors`: Errores de validación de formularios
- `loading`: Estado de carga para operaciones async

#### Getters:
- `isAuthenticated`: Verifica si hay usuario autenticado
- `isAdmin`: Verifica permisos de administrador
- `isTeacher`: Verifica permisos de profesor
- `isStudent`: Verifica permisos de estudiante
- `userName`: Obtiene el nombre del usuario
- `userEmail`: Obtiene el email del usuario

#### Actions:
- **`getUser()`**: Obtiene datos del usuario desde `/api/user`
- **`login(credentials)`**: Autentica usuario
  - Flujo: POST /login → getUser() → Redirigir
- **`register(data)`**: Registra nuevo usuario
  - Flujo: POST /register → getUser() → Redirigir
- **`logout()`**: Cierra sesión
  - Flujo: POST /logout → Limpiar state → Redirigir
- **`checkAuth()`**: Verifica si hay sesión activa
  - Se llama al iniciar la app para restaurar autenticación
- **`clearErrors()`**: Limpia errores de validación

#### Integración con Sanctum:
- No almacena tokens JWT (Sanctum usa cookies)
- Las cookies son gestionadas automáticamente por el navegador
- Solo almacena datos del usuario para la UI

---

### 3. **Enrutamiento Vue Router** (`resources/js/router/index.js`)

#### Estructura de Rutas:

**Rutas Públicas:**
- `/login` - Página de inicio de sesión
- `/register` - Página de registro

**Rutas Protegidas:**
- `/dashboard` - Panel principal
- `/labs/*` - Gestión de laboratorios
- `/equipment/*` - Gestión de equipos
- `/software/*` - Gestión de software
- `/reservations/*` - Gestión de reservas
- `/profile` - Perfil de usuario

#### Navigation Guards:

##### `beforeEach` Guard:
```javascript
// 1. Primera navegación: Verificar sesión activa
if (primera vez) {
    await authStore.checkAuth();
}

// 2. Actualizar título de página
document.title = `${to.meta.title} | Lab-Reserva`;

// 3. Proteger rutas que requieren autenticación
if (requiresAuth && !isAuthenticated) {
    return next('/login');
}

// 4. Verificar permisos específicos (admin, teacher)
if (requiresAdmin && !isAdmin) {
    return next('/dashboard');
}

// 5. Redirigir usuarios autenticados lejos de login/register
if (guest && isAuthenticated) {
    return next('/dashboard');
}
```

#### Lazy Loading:
- Todos los componentes se cargan bajo demanda
- Mejora el tiempo de carga inicial
- Reduce el bundle size

---

### 4. **Layouts**

#### AuthLayout (`resources/js/layouts/AuthLayout.vue`)
- Diseño minimalista para login/register
- Background con gradiente
- Logo centrado
- Footer con copyright

#### AppLayout (`resources/js/layouts/AppLayout.vue`)
- Navbar con navegación principal
- Menú de usuario con logout
- Contenedor para vistas protegidas
- Diseño responsivo

---

### 5. **Vistas Implementadas**

#### Vistas de Autenticación:
-  `LoginView.vue` - Formulario de login con validación
-  `RegisterView.vue` - Formulario de registro con validación
-  Manejo de errores en tiempo real
-  Estados de carga (loading)
-  Integración completa con auth store

#### Vista Principal:
-  `DashboardView.vue` - Dashboard con estadísticas y acciones rápidas

#### Vistas Stub (Pendientes de implementación):
-  Labs (Index, Show, Create, Edit)
-  Equipment (Index, Show, Create, Edit)
-  Software (Index, Show, Create, Edit)
-  Reservations (Index, Show, Create)
-  Profile

#### Vista de Error:
-  `NotFoundView.vue` - Página 404

---

##  Estructura de Archivos

```
resources/js/
├── app.js                      # Punto de entrada de la aplicación
├── bootstrap.js                # Configuración inicial
├── router/
│   └── index.js               # Configuración de Vue Router
├── stores/
│   └── auth.js                # Store de autenticación (Pinia)
├── utils/
│   └── api.js                 # Cliente Axios configurado
├── layouts/
│   ├── AuthLayout.vue         # Layout para autenticación
│   └── AppLayout.vue          # Layout para app principal
└── views/
    ├── auth/
    │   ├── LoginView.vue
    │   └── RegisterView.vue
    ├── labs/
    │   ├── LabsIndexView.vue
    │   ├── LabsShowView.vue
    │   ├── LabsCreateView.vue
    │   └── LabsEditView.vue
    ├── equipment/
    │   ├── EquipmentIndexView.vue
    │   ├── EquipmentShowView.vue
    │   ├── EquipmentCreateView.vue
    │   └── EquipmentEditView.vue
    ├── software/
    │   ├── SoftwareIndexView.vue
    │   ├── SoftwareShowView.vue
    │   ├── SoftwareCreateView.vue
    │   └── SoftwareEditView.vue
    ├── reservations/
    │   ├── ReservationsIndexView.vue
    │   ├── ReservationsShowView.vue
    │   └── ReservationsCreateView.vue
    ├── DashboardView.vue
    ├── ProfileView.vue
    └── NotFoundView.vue
```

---

##  Configuración

### Variables de Entorno (`.env`):
```env
APP_URL=http://lab-reserva.test
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173,127.0.0.1:3000,127.0.0.1:5173

VITE_APP_NAME="${APP_NAME}"
VITE_APP_URL="${APP_URL}"
VITE_API_URL="${APP_URL}/api/v1"
```

### Dependencias Instaladas:
-  `vue@3.5.22` - Framework principal
-  `vue-router@4` - Enrutamiento
-  `pinia@3.0.3` - Gestión de estado
-  `axios@1.12.2` - Cliente HTTP
-  `@vitejs/plugin-vue@6.0.1` - Plugin de Vite para Vue
-  `tailwindcss@3.4.18` - Estilos

---

##  Próximos Pasos

### Fase 2: Implementación de Vistas CRUD
1. **Laboratorios**: Listar, crear, editar, eliminar
2. **Equipos**: Listar, crear, editar, eliminar
3. **Software**: Listar, crear, editar, eliminar
4. **Reservas**: Listar, crear, ver detalles

### Fase 3: Componentes Reutilizables
1. **BaseTable**: Tabla genérica con paginación
2. **BaseForm**: Formulario genérico con validación
3. **BaseModal**: Modal reutilizable
4. **BaseButton**: Botones con estados (loading, disabled)
5. **BaseDatePicker**: Selector de fechas
6. **BaseSelect**: Select personalizado

### Fase 4: Optimizaciones
1. **Composables**: Extraer lógica reutilizable
2. **Paginación**: Implementar en tablas
3. **Búsqueda y filtros**: Añadir a listas
4. **Notificaciones**: Toast messages para feedback
5. **Validación**: Formularios con VeeValidate

---

##  Comandos Útiles

### Desarrollo:
```bash
npm run dev           # Iniciar servidor de desarrollo Vite
php artisan serve     # Iniciar servidor Laravel
```

### Producción:
```bash
npm run build         # Compilar para producción
```

### Testing:
```bash
npm run test          # Ejecutar tests (cuando se implementen)
```

---

##  Puntos Clave de Arquitectura

### 1. **Separación de Responsabilidades**
- **API Client**: Solo maneja comunicación HTTP
- **Store**: Solo maneja estado de la aplicación
- **Router**: Solo maneja navegación
- **Components**: Solo maneja presentación

### 2. **Seguridad**
- Cookies HTTP-only (no accesibles desde JavaScript)
- Protección CSRF automática
- Tokens de sesión seguros
- Validación en frontend y backend

### 3. **Escalabilidad**
- Lazy loading de componentes
- Code splitting automático
- Estructura modular
- Fácil de extender

### 4. **Mantenibilidad**
- Código documentado
- Estructura clara
- Convenciones consistentes
- Separación de concerns

---

##  Seguridad Laravel Sanctum

### Configuración Requerida en Backend:

#### `config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort()
))),
```

#### `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'supports_credentials' => true,
```

#### Middleware en `app/Http/Kernel.php`:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    // ...
],
```

---

##  Características Destacadas

1. **Auto-renovación de CSRF Token**: Si expira, se renueva automáticamente
2. **Manejo Global de Errores**: Todos los errores HTTP manejados centralizadamente
3. **Restauración de Sesión**: Al recargar la página, verifica si hay sesión activa
4. **Redirección Inteligente**: Guarda la ruta a la que intentaba acceder
5. **Loading States**: Feedback visual durante operaciones asíncronas
6. **Validación en Tiempo Real**: Limpia errores al escribir en el formulario

---

##  Documentación de Referencia

- [Vue 3 Documentation](https://vuejs.org/)
- [Vue Router Documentation](https://router.vuejs.org/)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Axios Documentation](https://axios-http.com/)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Tailwind CSS Documentation](https://tailwindcss.com/)

---

**Estado del Proyecto**:  **Fase 1 Completada**

**Próximo Milestone**: Implementación de vistas CRUD completas

---

*Documentación generada por el Arquitecto de Software - Lab-Reserva Team*
