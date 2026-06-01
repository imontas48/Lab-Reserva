# Reglas de Desarrollo — Lab-Reserva
> **System Prompt Permanente para el Asistente de IA**
> Versión: 1.0 | Stack: Laravel (LTS) + Vue 3 + Vite | DB: MySQL

Cuando generes cualquier código para este proyecto, estas reglas son **innegociables y de cumplimiento obligatorio**. No hay excepciones. Si una solicitud entra en conflicto con estas reglas, rechaza la implementación incorrecta y propón la alternativa canónica con su justificación.

---

## 0. Contexto del Proyecto

**Sistema:** Lab-Reserva — Plataforma de gestión de reservas para laboratorios de cómputo.

### Roles de Usuario
| Rol | Descripción |
|---|---|
| `admin` | Control total: CRUD de labs, equipos, software y todas las reservas. |
| `teacher` | Puede crear y gestionar sus propias reservas. |
| `student` | Puede crear y gestionar sus propias reservas. |

### Esquema de Base de Datos (MySQL)
```
users           → id, name, email, password, role (admin/teacher/student), timestamps
labs            → id, name, location, capacity, description, is_active, timestamps
equipment       → id, lab_id(FK), identifier, type, specifications, is_operational, timestamps
software        → id, name, version, ..., timestamps
equipment_software → equipment_id(FK), software_id(FK)  [tabla pivote]
reservations    → id, user_id(FK), equipment_id(FK), start_time, end_time, status(confirmed/cancelled/completed), timestamps
```

### Estructura de Carpetas Canónica
```
app/
  Http/
    Controllers/Api/   ← Solo flujo HTTP. Delegan en Services.
    Requests/          ← Un StoreXxxRequest y UpdateXxxRequest por recurso.
    Resources/         ← Un XxxResource por modelo expuesto en la API.
  Models/              ← Relaciones, scopes, casts, accessors/mutators.
  Policies/            ← Una Policy por modelo protegido.
  Services/            ← Toda la lógica de negocio.

resources/js/
  utils/api.js         ← Instancia Axios única (Sanctum + CSRF).
  composables/         ← use[Feature].js — estado reactivo + conexión al servicio.
  stores/              ← Pinia stores por dominio (auth.js, theme.js, etc.).
  components/
    forms/             ← BaseInput.vue, BaseSelect.vue (componentes base reutilizables).
    ui/                ← DataTable.vue, ThemeToggle.vue (componentes UI genéricos).
  layouts/             ← AppLayout.vue, AuthLayout.vue.
  views/               ← Organizadas por módulo: auth/, equipment/, labs/, reservations/, software/.
  router/index.js      ← Vue Router con lazy loading y navigation guards.
```

---

## 1. Reglas del Backend (Laravel)

### 1.1 Modelos — Solo Data Access

- Los modelos son la **única puerta de entrada** a la base de datos. Ningún otro artefacto (Controllers, Services) puede importar y usar Eloquent directamente **excepto** los Services, que lo hacen **a través de los métodos definidos en los Modelos**.
- **Permitido dentro de un Modelo:** relaciones (`belongsTo`, `hasMany`, `belongsToMany`), local scopes, accessors, mutators, casts y constantes de estado (ej. `STATUS_CONFIRMED = 'confirmed'`).
- **Prohibido dentro de un Modelo:** lógica de negocio, cálculos complejos, llamadas a otros servicios, decisiones de negocio.
- Las clases de modelo **deben** llevar nombre en **PascalCase singular** aunque la tabla sea plural (corregir `equipment` → `Equipment`, `labs` → `Lab`, `reservations` → `Reservation` en nuevos módulos). Respeta los nombres ya existentes para no romper código en producción, pero sigue PascalCase en cualquier modelo **nuevo**.

```php
// ✅ CORRECTO — Scope en el modelo
public function scopeOperational(Builder $query): Builder
{
    return $query->where('is_operational', true);
}

// ❌ INCORRECTO — Lógica de negocio en el modelo
public function createReservationFor(User $user, array $data): Reservation { ... }
```

### 1.2 Services — Toda la Lógica de Negocio

- Cada módulo **debe** tener su propio Service en `app/Services/[Feature]Service.php`.
- Un Service es un **Plain PHP Object** (sin herencia). Recibe dependencias por constructor.
- Los Services **orquestan** operaciones: validan condiciones de negocio, usan transacciones de DB cuando sea necesario (`DB::transaction`), lanzan excepciones descriptivas, y devuelven modelos Eloquent o DTOs.
- Un Service **nunca** retorna respuestas HTTP (`JsonResponse`, `Response`). Eso es responsabilidad del Controller.

```php
// ✅ CORRECTO — Service devuelve modelo o lanza excepción
public function createReservation(array $data, User $user): Reservation
{
    return DB::transaction(function () use ($data, $user) {
        if ($this->hasConflict($data)) {
            throw new \RuntimeException('Conflicto de horario detectado.');
        }
        return Reservation::create([...$data, 'user_id' => $user->id, 'status' => 'confirmed']);
    });
}
```

### 1.3 Controllers — Solo Flujo HTTP (Skinny Controllers)

- Los controllers solo hacen **tres cosas**: recibir la `Request`, invocar el `Service`, devolver la `Response`.
- **Cero consultas Eloquent** directas en el controller. **Cero lógica de negocio**.
- Inyecta el Service por constructor (Dependency Injection).
- Usa `try/catch` solo cuando el Service puede lanzar excepciones controladas que deban traducirse a códigos HTTP específicos.
- Todos los controllers de API van en `app/Http/Controllers/Api/` y extienden el `Controller` base.

```php
// ✅ CORRECTO — Controller ultra delgado
public function __construct(private readonly ReservationService $reservationService) {}

public function store(StoreReservationRequest $request): ReservationResource
{
    $reservation = $this->reservationService->createReservation(
        $request->validated(),
        $request->user()
    );
    return new ReservationResource($reservation);
}
```

### 1.4 Form Requests — Validación Estricta

- **Toda** validación de entrada ocurre exclusivamente en `Form Request`. Nunca en el controller ni en el Service.
- Crea un `Store[Feature]Request` y un `Update[Feature]Request` por recurso.
- El método `authorize()` debe delegar en la `Policy` correspondiente, no retornar `true` a ciegas.
- Los mensajes de error se personalizan en el método `messages()`.
- Las validaciones de integridad de negocio simples (ej. verificar que una FK existe y está activa) pueden incluirse como closures en `rules()`, pero las verificaciones **complejas** (race conditions, locks) van en el Service.

```php
// ✅ CORRECTO — authorize() delega en Policy
public function authorize(): bool
{
    return $this->user()->can('create', Reservation::class);
}
```

### 1.5 API Resources — Respuestas Estandarizadas

- Nunca devuelvas un modelo Eloquent directamente desde un controller. **Siempre** envuelve la respuesta en un `API Resource`.
- Usa `$this->whenLoaded('relation')` para incluir relaciones **solo si fueron cargadas** (evita N+1).
- Calcula campos derivados (`duration_minutes`, `is_active`, `is_past`) dentro del Resource, no en el frontend.
- Una colección se devuelve con `Resource::collection($paginator)`. El paginador incluye automáticamente `meta` y `links`.

### 1.6 Policies — Autorización por Modelo

- Cada modelo que requiera protección tiene su `[Model]Policy` en `app/Policies/`.
- Los métodos de Policy siguen la convención de Laravel: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.
- Las reglas de rol van aquí: solo `admin` puede hacer CRUD de labs/equipos; cualquier usuario autenticado puede crear reservas sobre su propio recurso.
- Registra las policies en `AppServiceProvider` si no se auto-descubren.

### 1.7 Rutas

- Todas las rutas de API viven en `routes/api.php` bajo el prefijo `/v1/`.
- Las rutas protegidas van dentro del grupo `middleware(['auth:sanctum'])`.
- Usa **siempre rutas nombradas** (`->name('api.resource.action')`).
- Las rutas anidadas o personalizadas se declaran **antes** del `apiResource` para evitar conflictos de resolución.
- **No** expongas ningún endpoint que no requiera al menos autenticación, salvo `login` y `register`.

### 1.8 Prevención N+1

- Todo `index()` que devuelva colecciones con relaciones **debe** usar `with()` (Eager Loading).
- Usa `select()` para traer solo las columnas necesarias cuando la tabla sea grande.
- Usa `withCount()` en lugar de cargar la relación completa cuando solo necesitas el conteo.

---

## 2. Reglas del Frontend (Vue 3 + Vite)

### 2.1 Regla Absoluta: `<script setup>` + Composition API

- **Queda terminantemente prohibido** usar Options API (`data()`, `methods:`, `computed:`, `mounted()` como opciones de objeto) en cualquier componente nuevo.
- Usa siempre `<script setup>` como punto de entrada del bloque `<script>`.
- Define props con `defineProps<{ ... }>()` (tipado con genérico TypeScript o JSDoc).
- Define eventos con `defineEmits<{ ... }>()`.
- Expón solo lo necesario al template con `defineExpose()` y únicamente cuando se requiera desde el padre.

### 2.2 Instancia Axios — `utils/api.js`

- **Existe una única instancia de Axios** configurada en `resources/js/utils/api.js`. Importa siempre desde ahí: `import api from '@/utils/api'`.
- Esta instancia ya maneja: `baseURL` desde `VITE_API_URL`, `withCredentials: true`, cabecera CSRF automática para Sanctum, interceptor de respuesta que redirige a login en 401.
- **Nunca** crees una instancia paralela de Axios. **Nunca** hardcodees la URL base.
- **Nunca** hagas llamadas HTTP directamente desde un componente `.vue`.

### 2.3 Composables — `composables/use[Feature].js`

- Cada módulo de negocio tiene su composable: `useEquipment.js`, `useLabs.js`, `useReservations.js`, `useSoftware.js`.
- Un composable es una función que **retorna** estado reactivo (`ref`, `reactive`) y métodos.
- El composable es el único lugar que importa `api.js` y ejecuta llamadas HTTP.
- Exporta siempre: `data` (o nombre semántico), `isLoading`, `error`, `validationErrors`, y los métodos de acción (`fetch[Resource]`, `create[Resource]`, `update[Resource]`, `delete[Resource]`).
- Maneja errores: captura `422` para poblar `validationErrors` y otros códigos para poblar `error`.

```js
// ✅ CORRECTO — Estructura de composable
export function useReservations() {
  const reservations = ref([]);
  const isLoading = ref(false);
  const error = ref(null);
  const validationErrors = ref({});

  async function fetchReservations(params = {}) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await api.get('/reservations', { params });
      reservations.value = data.data;
    } catch (err) {
      if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors;
      } else {
        error.value = err.response?.data?.message ?? 'Error inesperado.';
      }
    } finally {
      isLoading.value = false;
    }
  }

  return { reservations, isLoading, error, validationErrors, fetchReservations };
}
```

### 2.4 Stores Pinia — `stores/[domain].js`

- Los Pinia stores son para **estado global y persistente** que debe ser compartido entre módulos no relacionados directamente (ej. usuario autenticado, tema, notificaciones).
- **No** uses un store de Pinia como sustituto de un composable para datos de módulo. Los datos de lab, equipo o reserva van en composables, no en stores.
- Stores existentes: `auth.js` (usuario autenticado, roles), `theme.js` (modo oscuro/claro).
- Usa la **Setup Store API** (`defineStore('id', () => { ... })`) para consistencia con Composition API.

### 2.5 Componentes — Solo UI

- Un componente `.vue` importa el composable, lo usa para obtener datos, y **delega toda llamada HTTP** al composable.
- **Máximo 150 líneas** de código en el bloque `<script setup>`. Si superas esto, extrae lógica al composable o divide el componente.
- La comunicación entre componentes es **unidireccional**: `props` hacia abajo, `emits` hacia arriba.
- No accedas a componentes hijo/padre con `ref` + `defineExpose` salvo para operaciones de UI puras (focus, scroll).

### 2.6 Componentes Base Existentes — Úsalos Obligatoriamente

Antes de crear HTML crudo para un formulario, verifica y usa los componentes existentes:

| Componente | Archivo | Uso |
|---|---|---|
| `BaseInput` | `components/forms/BaseInput.vue` | Inputs de texto, email, número, password, fecha. Soporta `v-model`, `label`, `error`, `required`. |
| `BaseSelect` | `components/forms/BaseSelect.vue` | Selects con opciones. Soporta `v-model`, `label`, `options`, `error`. |
| `DataTable` | `components/ui/DataTable.vue` | Tablas de datos con columnas configurables. |
| `ThemeToggle` | `components/ui/ThemeToggle.vue` | Toggle de modo oscuro/claro. |

```vue
<!-- ✅ CORRECTO — Usa componentes base -->
<BaseInput v-model="form.name" label="Nombre" :error="errors.name" required />

<!-- ❌ INCORRECTO — HTML crudo cuando existe un componente -->
<input type="text" v-model="form.name" class="border rounded p-2" />
```

### 2.7 Estilos — TailwindCSS First

- **TailwindCSS es la única solución de estilos** aceptada. No uses CSS arbitrario salvo que sea absolutamente imposible con Tailwind.
- El bloque `<style>` debe ser `<style scoped>` y contener como máximo overrides muy específicos que Tailwind no puede lograr.
- Respeta el sistema de colores, espaciado y tipografía ya establecido en la aplicación.
- Los colores semánticos del sistema son: `primary` (azul), estados con `green`/`red`/`yellow`.
- Soporte **dark mode** es obligatorio en cualquier componente nuevo. Usa la variante `dark:` de Tailwind.

### 2.8 Router — Lazy Loading Obligatorio

- Todos los componentes de vista (`views/`) se importan con `() => import('@/views/...')` para code-splitting automático.
- Cada ruta debe tener `meta.title` y `meta.requiresAuth` definidos.
- Las rutas de administración deben incluir `meta.requiresAdmin: true` y ser protegidas en el navigation guard.

### 2.9 Notificaciones

- Usa **exclusivamente** `vue-toastification` para notificaciones al usuario. Ya está configurado globalmente.
- Importa con: `import { useToast } from 'vue-toastification'` o a través del composable `useToast.js` del proyecto.
- Tipos: `toast.success()`, `toast.error()`, `toast.warning()`, `toast.info()`.

### 2.10 Manejo de Fechas

- Todas las fechas vienen del backend en formato **ISO 8601** (`start_time`, `end_time`).
- Usa el objeto nativo `Intl.DateTimeFormat` o una librería ligera para formatear fechas en la UI. No uses moment.js.

---

## 3. Convenciones de Nomenclatura

| Artefacto | Convención | Ejemplo |
|---|---|---|
| Modelo Laravel | PascalCase singular | `Reservation`, `Equipment` |
| Controller | PascalCase + Controller | `ReservationController` |
| Service | PascalCase + Service | `ReservationService` |
| Form Request | `Store/Update` + Feature + `Request` | `StoreReservationRequest` |
| API Resource | PascalCase + Resource | `ReservationResource` |
| Policy | PascalCase + Policy | `ReservationPolicy` |
| Migration | `timestamp_verb_table_table.php` | `2024_01_01_create_reservations_table` |
| Componente Vue | PascalCase | `ReservationForm.vue`, `EquipmentCard.vue` |
| Composable | `use` + PascalCase + `.js` | `useReservations.js` |
| Store Pinia | `use` + PascalCase + `Store` | `useAuthStore` |
| Variable reactiva | camelCase | `isLoading`, `reservations` |
| Ruta API nombrada | `api.resource.action` | `api.reservations.cancel` |

---

## 4. Flujo Obligatorio de Entrega de Código

Para cada funcionalidad nueva, entrega los artefactos **en este orden exacto**:

1. **Migración** (`database/migrations/`) si hay cambio de esquema.
2. **Modelo** (`app/Models/`) con relaciones, scopes y casts.
3. **Policy** (`app/Policies/`) con autorización por rol.
4. **Form Requests** (`app/Http/Requests/`) — `Store` y `Update`.
5. **API Resource** (`app/Http/Resources/`).
6. **Service** (`app/Services/`) con toda la lógica de negocio.
7. **Controller** (`app/Http/Controllers/Api/`) ultra delgado.
8. **Ruta** (`routes/api.php`) con nombre y middleware.
9. **Composable** (`resources/js/composables/`).
10. **Componentes Vue** (`resources/js/views/` y `components/`).

---

## 5. Seguridad — Lista de Verificación Permanente

Antes de declarar completa cualquier funcionalidad, verifica:

- [ ] **Mass Assignment:** `$fillable` definido explícitamente en el modelo. Nunca usar `$guarded = []`.
- [ ] **Autorización:** Cada endpoint protegido invoca su Policy (`$this->authorize()` o `authorizeResource()`).
- [ ] **CORS:** Solo el frontend autorizado puede consumir la API (configurado en `config/cors.php`).
- [ ] **CSRF:** Las mutaciones usan el flujo Sanctum + cookie XSRF-TOKEN (ya manejado por `utils/api.js`).
- [ ] **SQL Injection:** Usa siempre Query Builder o Eloquent. Nunca concatenes strings en consultas.
- [ ] **XSS:** No renderices HTML sin sanitizar. Usa `{{ }}` (auto-escaped) en Vue, no `v-html` con datos del usuario.
- [ ] **Exposición de datos:** Los API Resources no exponen campos sensibles (`password`, `remember_token`).
- [ ] **Validación en Form Request:** Toda entrada externa se valida antes de llegar al Service.

---

## 6. Antipatrones — Está Prohibido

| Antipatrón | Alternativa Correcta |
|---|---|
| Lógica de negocio en el Controller | Mover al Service correspondiente |
| Consultas Eloquent en el Controller | Mover al Service; el Service usa métodos del Modelo |
| Validación en el Controller (`$request->validate()`) | Usar Form Request (`StoreXxxRequest`) |
| Devolver `$model->toArray()` o `$model` directamente | Envolver en API Resource |
| `$guarded = []` en el modelo | Definir `$fillable` explícitamente |
| Options API en componentes Vue | `<script setup>` + Composition API |
| Axios directo en un componente `.vue` | Llamada HTTP solo en el composable del módulo |
| Crear nueva instancia `axios.create()` | Importar siempre desde `@/utils/api` |
| Estado global en `ref()` fuera de un store/composable | Pinia store o composable con retorno explícito |
| `v-html` con datos del usuario | Escapar contenido o usar componentes seguros |
| Rutas sin nombre (`->name()`) | Nombrar siempre las rutas |
| `console.log` en código entregado | Eliminar antes de entregar; usar manejo de errores real |
