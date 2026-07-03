# LabsCreateEditView - Formulario Unificado de Gestión

**Archivo:** `resources/js/views/labs/LabsCreateEditView.vue`  
**Composable:** `useLabs.js`  
**Rutas:**
- `/labs/create` → Modo creación
- `/labs/:id/edit` → Modo edición

**Fecha:** Octubre 2025  
**Estado:**  Completo y Funcional

---

##  Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Características Principales](#características-principales)
3. [Arquitectura del Componente](#arquitectura-del-componente)
4. [Flujo de Datos](#flujo-de-datos)
5. [Validación de Formularios](#validación-de-formularios)
6. [Estados Visuales](#estados-visuales)
7. [Casos de Uso](#casos-de-uso)
8. [Guía de Implementación](#guía-de-implementación)
9. [Testing](#testing)
10. [Métricas](#métricas)

---

##  Descripción General

**LabsCreateEditView** es un componente de formulario unificado que maneja tanto la **creación** como la **edición** de laboratorios, siguiendo el principio DRY (Don't Repeat Yourself). Este patrón permite:

-  **Reutilización de código**: Una sola vista para dos funcionalidades
-  **Mantenibilidad**: Cambios en un solo lugar afectan ambos modos
-  **Consistencia UX**: Misma interfaz para crear y editar
-  **Reducción de bugs**: Menos código duplicado = menos puntos de fallo

### Propósito

Este componente sirve como **blueprint (plantilla)** para todos los formularios de gestión de la aplicación. Las mismas técnicas y patrones pueden aplicarse a:

- Software (create/edit)
- Equipment (create/edit)
- Reservations (create/edit)
- Users (create/edit)

---

##  Características Principales

### 1. Modo Dual (Create/Edit)

```javascript
const isEditing = computed(() => !!route.params.id);
```

**Lógica:**
- Si `route.params.id` existe → **Modo Edición**
- Si `route.params.id` es `undefined` → **Modo Creación**

**Impacto en la UI:**
- Título dinámico: "Crear Nuevo Laboratorio" vs "Editar Laboratorio"
- Texto del botón: "Crear Laboratorio" vs "Guardar Cambios"
- Breadcrumb: Muestra "Crear" o "Editar"
- Carga inicial: Solo en modo edición se cargan datos

---

### 2. Validación Integrada

```javascript
const validationErrors = ref({});

const getFieldError = (fieldName) => {
    if (!validationErrors.value[fieldName]) return '';
    
    const errors = validationErrors.value[fieldName];
    return Array.isArray(errors) ? errors[0] : errors;
};
```

**Características:**
-  Errores de validación del backend (422)
-  Mensajes específicos por campo
-  Integración con `BaseInput` component
-  Transiciones suaves (error-fade)

---

### 3. Estados de Carga

El componente maneja **3 estados de carga** distintos:

#### Estado 1: Carga Inicial (Modo Edición)
```vue
<div v-if="initialLoading" class="animate-pulse">
    <!-- Skeleton loader -->
</div>
```

Se muestra mientras se cargan los datos del laboratorio desde la API.

#### Estado 2: Carga de Envío
```vue
<button :disabled="loading">
    <svg v-if="loading" class="animate-spin">...</svg>
    <span>{{ loading ? 'Guardando...' : 'Guardar Cambios' }}</span>
</button>
```

Se muestra mientras se envía el formulario (POST/PUT).

#### Estado 3: Error de Carga
```vue
<div v-else-if="loadError">
    <p>{{ loadError }}</p>
    <button @click="loadLabData">Reintentar</button>
</div>
```

Se muestra si falla la carga inicial de datos.

---

### 4. Manejo de Errores

El componente maneja **2 tipos de errores**:

#### Error de Carga (GET)
```javascript
const loadError = ref(null);

try {
    const lab = await fetchLabById(route.params.id);
} catch (err) {
    loadError.value = err.response?.data?.message || 'Error al cargar';
}
```

**Presentación:**
- Banner rojo con mensaje de error
- Botón "Reintentar" para volver a intentar
- Botón "Volver al Listado" para cancelar

---

#### Error de Validación (POST/PUT - 422)
```javascript
try {
    await createLab(form.value);
} catch (err) {
    // validationErrors se llenan automáticamente en el composable
    // Los BaseInput muestran los errores individualmente
}
```

**Presentación:**
- Mensaje general arriba del formulario
- Mensajes específicos debajo de cada campo
- Border rojo en campos con error
- Iconos visuales de error

---

### 5. Navegación Automática

```javascript
router.push({ 
    name: 'labs.index',
    query: { 
        success: isEditing.value ? 'updated' : 'created',
        name: result.name 
    }
});
```

Después de una operación exitosa:
- Redirige a `/labs` (listado)
- Incluye query params para mostrar mensaje de éxito
- Permite al IndexView mostrar un toast/alert de confirmación

---

## ️ Arquitectura del Componente

### Estructura de Archivos

```
resources/js/
├── composables/
│   └── useLabs.js              ← Lógica de negocio
├── components/
│   └── forms/
│       └── BaseInput.vue        ← Componente reutilizable
└── views/
    └── labs/
        ├── LabsIndexView.vue    ← Listado
        ├── LabsCreateEditView.vue ← Formulario unificado
        └── LabsShowView.vue     ← Detalle
```

### Separación de Responsabilidades

| Capa | Responsabilidad | Archivo |
|------|----------------|---------|
| **Vista** | Presentación UI, eventos de usuario | `LabsCreateEditView.vue` |
| **Lógica** | Peticiones API, estado reactivo | `useLabs.js` |
| **Componente** | Input reutilizable con validación | `BaseInput.vue` |
| **Rutas** | Navegación y protección | `router/index.js` |

---

##  Flujo de Datos

### Flujo Completo: Modo Creación

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario navega a /labs/create                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Router carga LabsCreateEditView.vue                      │
│    - isEditing = false (no hay route.params.id)             │
│    - Formulario vacío                                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Usuario llena el formulario                              │
│    - v-model vincula inputs con form.value                  │
│    - Validación visual en tiempo real (required, types)     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Usuario hace clic en "Crear Laboratorio"                 │
│    - handleSubmit() se ejecuta                               │
│    - clearErrors() limpia errores previos                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Composable: createLab(form.value)                        │
│    - POST /api/labs                                          │
│    - loading = true (botón deshabilitado + spinner)         │
└─────────────────────────────────────────────────────────────┘
                            ↓
                    ┌───────┴────────┐
                    │                │
            ┌───────▼──────┐  ┌──────▼────────┐
            │   200 OK     │  │  422 Unproc.  │
            └───────┬──────┘  └──────┬────────┘
                    │                │
    ┌───────────────▼─────┐  ┌──────▼───────────────────┐
    │ 6A. ÉXITO           │  │ 6B. ERROR VALIDACIÓN     │
    │ - labs.value.unshift│  │ - validationErrors =     │
    │ - router.push       │  │   { name: ['required'] } │
    │   (/labs?success)   │  │ - error = 'Corrige...'   │
    └─────────────────────┘  └──────────────────────────┘
                                         │
                            ┌────────────▼──────────────┐
                            │ 7. BaseInput muestra      │
                            │    errores en campos      │
                            │    - Border rojo          │
                            │    - Mensaje específico   │
                            └───────────────────────────┘
```

---

### Flujo Completo: Modo Edición

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario navega a /labs/5/edit                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Router carga LabsCreateEditView.vue                      │
│    - isEditing = true (route.params.id = 5)                 │
│    - onMounted → loadLabData()                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Composable: fetchLabById(5)                              │
│    - GET /api/labs/5                                         │
│    - initialLoading = true (skeleton loader)                │
└─────────────────────────────────────────────────────────────┘
                            ↓
                    ┌───────┴────────┐
                    │                │
            ┌───────▼──────┐  ┌──────▼────────┐
            │   200 OK     │  │  404 Not Found│
            └───────┬──────┘  └──────┬────────┘
                    │                │
    ┌───────────────▼─────┐  ┌──────▼───────────────────┐
    │ 4A. ÉXITO           │  │ 4B. ERROR                │
    │ - form.value = {    │  │ - loadError = mensaje    │
    │     name: '...',    │  │ - Muestra banner rojo    │
    │     location: '...'}│  │ - Botón "Reintentar"     │
    │ - Formulario poblado│  └──────────────────────────┘
    └─────────────────────┘
                ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Usuario modifica campos                                  │
│    - v-model actualiza form.value                           │
└─────────────────────────────────────────────────────────────┘
                ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Usuario hace clic en "Guardar Cambios"                   │
│    - handleSubmit() detecta isEditing = true                │
│    - updateLab(5, form.value)                                │
│    - PUT /api/labs/5                                         │
└─────────────────────────────────────────────────────────────┘
                ↓
        (Mismo flujo que creación)
```

---

##  Validación de Formularios

### Validación del Backend (Laravel)

El backend retorna errores en formato:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "El campo nombre es obligatorio."
    ],
    "location": [
      "El campo ubicación es obligatorio."
    ],
    "capacity": [
      "El campo capacidad debe ser un número.",
      "El campo capacidad debe ser al menos 1."
    ]
  }
}
```

### Procesamiento en el Composable

```javascript
// useLabs.js
catch (err) {
    if (err.response?.status === 422) {
        validationErrors.value = err.response.data.errors || {};
        error.value = 'Por favor, corrige los errores en el formulario';
    }
}
```

### Presentación en la Vista

```vue
<!-- Error general -->
<div v-if="error">{{ error }}</div>

<!-- Error por campo -->
<BaseInput
    v-model="form.name"
    :error="getFieldError('name')"
/>
```

```javascript
const getFieldError = (fieldName) => {
    if (!validationErrors.value[fieldName]) return '';
    
    const errors = validationErrors.value[fieldName];
    return Array.isArray(errors) ? errors[0] : errors;
};
```

**Características:**
-  Solo muestra el **primer error** de cada campo
-  Extrae el mensaje correctamente (array → string)
-  Retorna string vacío si no hay error (BaseInput lo maneja)

---

##  Estados Visuales

### Estado 1: Formulario Vacío (Creación)

```
┌─────────────────────────────────────────────┐
│ Crear Nuevo Laboratorio                     │
├─────────────────────────────────────────────┤
│                                             │
│ Nombre del Laboratorio *                    │
│ ┌─────────────────────────────────────────┐ │
│ │ Ej: Laboratorio de Redes Avanzadas     │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ Ubicación *                                 │
│ ┌─────────────────────────────────────────┐ │
│ │ Ej: Edificio C, Piso 2, Aula 201       │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│        [ Cancelar ]  [ Crear Laboratorio ]  │
└─────────────────────────────────────────────┘
```

---

### Estado 2: Cargando Datos (Edición)

```
┌─────────────────────────────────────────────┐
│ Editar Laboratorio                          │
├─────────────────────────────────────────────┤
│                                             │
│ ▓▓▓▓▓▓▓▓ (animación pulsante)              │
│ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓                 │
│                                             │
│ ▓▓▓▓▓▓▓▓                                    │
│ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓                 │
└─────────────────────────────────────────────┘
```

---

### Estado 3: Formulario Poblado (Edición)

```
┌─────────────────────────────────────────────┐
│ Editar Laboratorio                          │
├─────────────────────────────────────────────┤
│                                             │
│ Nombre del Laboratorio *                    │
│ ┌─────────────────────────────────────────┐ │
│ │ Laboratorio de Redes                    │ │ ← Valor existente
│ └─────────────────────────────────────────┘ │
│                                             │
│ Ubicación *                                 │
│ ┌─────────────────────────────────────────┐ │
│ │ Edificio C, Piso 2                      │ │ ← Valor existente
│ └─────────────────────────────────────────┘ │
│                                             │
│        [ Cancelar ]  [ Guardar Cambios ]    │
└─────────────────────────────────────────────┘
```

---

### Estado 4: Errores de Validación

```
┌─────────────────────────────────────────────┐
│ Crear Nuevo Laboratorio                     │
├─────────────────────────────────────────────┤
│ ️ Por favor, corrige los errores...        │ ← Error general
├─────────────────────────────────────────────┤
│                                             │
│ Nombre del Laboratorio *                    │
│ ┌─────────────────────────────────────────┐ │
│ │                                         │ │ ← Border rojo
│ └─────────────────────────────────────────┘ │
│ ️ El campo nombre es obligatorio.          │ ← Error específico
│                                             │
│ Capacidad *                                 │
│ ┌─────────────────────────────────────────┐ │
│ │ -5                                      │ │ ← Border rojo
│ └─────────────────────────────────────────┘ │
│ ️ La capacidad debe ser al menos 1.        │ ← Error específico
│                                             │
│        [ Cancelar ]  [ Crear Laboratorio ]  │
└─────────────────────────────────────────────┘
```

---

### Estado 5: Guardando (Loading)

```
┌─────────────────────────────────────────────┐
│ Crear Nuevo Laboratorio                     │
├─────────────────────────────────────────────┤
│                                             │
│ Nombre del Laboratorio *                    │
│ ┌─────────────────────────────────────────┐ │
│ │ Laboratorio de IA                       │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│        [ Cancelar ]  [ ⟳ Guardando... ]     │ ← Botón deshabilitado
└─────────────────────────────────────────────┘
                                   ↑
                             Spinner girando
```

---

### Estado 6: Error de Carga

```
┌─────────────────────────────────────────────┐
│ Editar Laboratorio                          │
├─────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────┐ │
│ │  Error al cargar los datos            │ │ ← Banner rojo
│ │ No se pudieron cargar los datos del...  │ │
│ │                                          │ │
│ │ [ Reintentar ] [ Volver al Listado ]    │ │
│ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘
```

---

##  Casos de Uso

### Caso 1: Crear un Nuevo Laboratorio (Happy Path)

**Precondiciones:**
- Usuario autenticado con rol `admin`
- Backend API funcionando correctamente

**Pasos:**
1. Usuario navega a `/labs/create`
2. Formulario se muestra vacío
3. Usuario llena todos los campos:
   - Nombre: "Laboratorio de IoT"
   - Ubicación: "Edificio D, Piso 1"
   - Capacidad: 25
   - Descripción: "Laboratorio equipado con sensores y actuadores"
4. Usuario hace clic en "Crear Laboratorio"
5. Botón muestra spinner + "Guardando..."
6. API retorna 200 OK con el lab creado
7. Usuario es redirigido a `/labs?success=created&name=Laboratorio%20de%20IoT`

**Resultado Esperado:**
-  Laboratorio creado en la base de datos
-  Redirección exitosa al listado
-  (IndexView) Mensaje de éxito mostrado

---

### Caso 2: Editar un Laboratorio Existente (Happy Path)

**Precondiciones:**
- Usuario autenticado con rol `admin`
- Laboratorio con ID=5 existe en la base de datos

**Pasos:**
1. Usuario navega a `/labs/5/edit`
2. Skeleton loader se muestra mientras carga
3. API retorna datos del laboratorio
4. Formulario se puebla con los datos existentes
5. Usuario modifica el campo "Capacidad" de 20 a 30
6. Usuario hace clic en "Guardar Cambios"
7. Botón muestra spinner + "Guardando..."
8. API retorna 200 OK con el lab actualizado
9. Usuario es redirigido a `/labs?success=updated&name=...`

**Resultado Esperado:**
-  Laboratorio actualizado en la base de datos
-  Redirección exitosa al listado
-  Mensaje de éxito mostrado

---

### Caso 3: Error de Validación al Crear

**Pasos:**
1. Usuario navega a `/labs/create`
2. Usuario deja campos vacíos o con valores inválidos
3. Usuario hace clic en "Crear Laboratorio"
4. API retorna 422 Unprocessable Entity
5. `validationErrors` se llenan en el composable
6. Banner general de error aparece arriba
7. Cada BaseInput muestra su error específico con border rojo

**Resultado Esperado:**
-  No se crea el laboratorio
-  Errores visualizados claramente
-  Usuario puede corregir y reintentar

---

### Caso 4: Error de Carga en Modo Edición

**Pasos:**
1. Usuario navega a `/labs/999/edit` (ID no existe)
2. Skeleton loader se muestra
3. API retorna 404 Not Found
4. `loadError` se llena con el mensaje
5. Banner rojo se muestra con el error
6. Botones "Reintentar" y "Volver al Listado" aparecen

**Resultado Esperado:**
-  Error manejado correctamente
-  Usuario puede reintentar o volver
-  No hay crash de la aplicación

---

### Caso 5: Usuario Cancela la Operación

**Pasos:**
1. Usuario está llenando el formulario (crear o editar)
2. Usuario hace clic en "Cancelar"
3. Router navega a `/labs` (listado)

**Resultado Esperado:**
-  Navegación inmediata
-  Cambios no guardados (descartados)
-  No se hace petición a la API

---

## ️ Guía de Implementación

### Para Replicar en Otros Recursos

Si quieres crear un formulario similar para `Software`, `Equipment`, etc:

#### Paso 1: Crear el Composable

```bash
# Copiar useLabs.js como plantilla
cp useLabs.js useSoftware.js
```

Reemplazar:
- `labs` → `software`
- `/labs` → `/software`
- `Lab` → `Software`

#### Paso 2: Crear la Vista

```bash
# Copiar LabsCreateEditView.vue como plantilla
cp LabsCreateEditView.vue SoftwareCreateEditView.vue
```

Ajustar:
- `useLabs` → `useSoftware`
- Campos del formulario (name, version, etc.)
- Títulos y textos

#### Paso 3: Actualizar Rutas

```javascript
{
    path: '/software/create',
    name: 'software.create',
    component: () => import('@/views/software/SoftwareCreateEditView.vue'),
    meta: {
        title: 'Crear Software',
        requiresAuth: true,
        requiresAdmin: true,
    }
},
{
    path: '/software/:id/edit',
    name: 'software.edit',
    component: () => import('@/views/software/SoftwareCreateEditView.vue'),
    meta: {
        title: 'Editar Software',
        requiresAuth: true,
        requiresAdmin: true,
    }
}
```

#### Paso 4: Testing

Probar manualmente:
1. Crear nuevo registro
2. Editar registro existente
3. Validación de campos
4. Errores de red
5. Redirección después del éxito

---

##  Testing

### Test Cases Recomendados

#### TC1: Detección de Modo
```javascript
// Modo Creación
// URL: /labs/create
// route.params.id → undefined
// isEditing.value → false

// Modo Edición
// URL: /labs/5/edit
// route.params.id → "5"
// isEditing.value → true
```

---

#### TC2: Carga de Datos (Modo Edición)
```javascript
// GIVEN: Usuario en /labs/5/edit
// WHEN: Componente se monta
// THEN: 
//   - fetchLabById(5) se llama
//   - initialLoading = true
//   - form.value se puebla con datos de la API
//   - initialLoading = false
```

---

#### TC3: Envío de Formulario (Creación)
```javascript
// GIVEN: Formulario llenado correctamente
// WHEN: handleSubmit() se ejecuta
// THEN:
//   - createLab(form.value) se llama
//   - loading = true
//   - API retorna 200
//   - router.push a /labs
```

---

#### TC4: Errores de Validación
```javascript
// GIVEN: Campos con valores inválidos
// WHEN: handleSubmit() se ejecuta
// THEN:
//   - API retorna 422
//   - validationErrors se llenan
//   - getFieldError('name') retorna mensaje
//   - BaseInput muestra error
```

---

#### TC5: Manejo de Errores de Red
```javascript
// GIVEN: API no disponible
// WHEN: handleSubmit() se ejecuta
// THEN:
//   - error.value se llena
//   - Banner de error se muestra
//   - Usuario puede ver mensaje
```

---

#### TC6: Navegación
```javascript
// GIVEN: Usuario hace clic en "Cancelar"
// WHEN: Router navega
// THEN:
//   - Navegación a /labs
//   - Sin peticiones a la API
```

---

### Testing Manual

**Checklist:**

- [ ] Crear laboratorio con datos válidos
- [ ] Crear laboratorio con datos inválidos (validación)
- [ ] Crear laboratorio sin conexión (error de red)
- [ ] Editar laboratorio existente
- [ ] Editar laboratorio con ID inválido (404)
- [ ] Cancelar creación (navegación)
- [ ] Cancelar edición (navegación)
- [ ] Botón disabled durante carga
- [ ] Spinner visible durante operación
- [ ] Redirección después del éxito
- [ ] Query params correctos en redirección

---

##  Métricas

### Métricas del Código

| Métrica | Valor |
|---------|-------|
| **Líneas de Código (Total)** | ~450 líneas |
| **Líneas de Template** | ~250 líneas |
| **Líneas de Script** | ~180 líneas |
| **Líneas de Styles** | ~20 líneas |
| **Componentes Importados** | 1 (BaseInput) |
| **Composables Usados** | 3 (useRoute, useRouter, useLabs) |
| **Refs** | 3 (initialLoading, loadError, form) |
| **Computed Properties** | 1 (isEditing) |
| **Métodos** | 3 (getFieldError, loadLabData, handleSubmit) |

---

### Métricas del Composable (useLabs.js)

| Métrica | Valor |
|---------|-------|
| **Líneas de Código** | ~350 líneas |
| **Refs** | 4 (labs, loading, error, validationErrors) |
| **Métodos Públicos** | 8 métodos |
| **Endpoints API** | 5 endpoints |

---

### Métricas de Funcionalidad

| Funcionalidad | Estado |
|---------------|--------|
| **Modo Dual (Create/Edit)** |  Implementado |
| **Carga Inicial (Edit)** |  Implementado |
| **Skeleton Loader** |  Implementado |
| **Validación Backend** |  Integrado |
| **Manejo de Errores** |  Completo |
| **Estados de Carga** |  3 estados |
| **Breadcrumb Navegación** |  Implementado |
| **Redirección Automática** |  Con query params |
| **Botón con Spinner** |  Implementado |
| **Textarea Custom** |  Implementado |
| **Dark Mode** |  Compatible |
| **Responsive Design** |  Mobile-first |

---

### Cobertura de Casos de Uso

| Caso de Uso | Cubierto |
|-------------|----------|
| **Crear laboratorio válido** |  |
| **Crear con validación fallida** |  |
| **Crear sin conexión** |  |
| **Editar laboratorio existente** |  |
| **Editar ID no existente** |  |
| **Cancelar operación** |  |
| **Redirección después de éxito** |  |
| **Total** | **7/7 (100%)** |

---

##  Patrones y Mejores Prácticas

### Patrón 1: Composable Pattern

 **DO:**
```javascript
const { loading, error, createLab } = useLabs();
```

 **DON'T:**
```javascript
// Lógica directamente en el componente
const createLab = async () => {
    const response = await axios.post('/api/labs', ...);
    // ...
};
```

**Ventajas:**
- Reutilización de lógica
- Testing más fácil
- Separación de responsabilidades

---

### Patrón 2: Computed para Lógica Reactiva

 **DO:**
```javascript
const isEditing = computed(() => !!route.params.id);
```

 **DON'T:**
```javascript
const isEditing = ref(false);
onMounted(() => {
    isEditing.value = !!route.params.id;
});
```

**Ventajas:**
- Actualización automática
- No necesita watchers
- Más declarativo

---

### Patrón 3: Helper Functions para Lógica Compleja

 **DO:**
```javascript
const getFieldError = (fieldName) => {
    if (!validationErrors.value[fieldName]) return '';
    return validationErrors.value[fieldName][0];
};
```

**Ventajas:**
- Template más limpio
- Lógica reutilizable
- Testeable

---

### Patrón 4: v-if para Estados Mutuamente Excluyentes

 **DO:**
```vue
<div v-if="initialLoading">Loading...</div>
<div v-else-if="loadError">Error...</div>
<form v-else>...</form>
```

**Ventajas:**
- Solo un estado activo a la vez
- Mejor performance
- Lógica clara

---

### Patrón 5: Transiciones para Feedback Visual

 **DO:**
```vue
<transition name="error-fade">
    <p v-if="error">{{ error }}</p>
</transition>
```

**Ventajas:**
- UX más suave
- Profesional
- Fácil de implementar

---

##  Próximos Pasos

### Mejoras Futuras

1. **BaseTextarea Component**
   - Componente dedicado para textarea
   - Contador de caracteres
   - Auto-resize

2. **Confirmación de Cancelación**
   - Modal "¿Descartar cambios?"
   - Solo si hay cambios sin guardar

3. **Validación en Tiempo Real**
   - Integrar VeeValidate
   - Validación mientras el usuario escribe
   - Esquemas de validación con Yup

4. **Mensajes de Éxito**
   - Toast notifications
   - Integrar en IndexView
   - Leer query params

5. **Auto-save**
   - Guardar en localStorage
   - Recuperar si el usuario recarga

---

##  Conclusión

**LabsCreateEditView** es un formulario robusto, profesional y extensible que sirve como **blueprint** para todos los formularios de gestión de la aplicación.

### Logros

 **Modo dual** (create/edit) sin duplicar código  
 **Validación completa** con errores del backend  
 **Manejo de errores** en todos los escenarios  
 **Estados de carga** visuales y claros  
 **Navegación automática** después del éxito  
 **Accesibilidad** garantizada (BaseInput)  
 **Dark mode** compatible  
 **Responsive design** mobile-first  
 **Documentación** completa y detallada  

### Aprendizajes

- El patrón de **vista unificada** reduce mantenimiento
- Los **composables** simplifican la lógica de negocio
- **BaseInput** abstrae la complejidad de validación
- **Computed properties** mantienen el código reactivo y limpio
- **Estados visuales** mejoran la experiencia del usuario

---

**Última actualización:** Octubre 2025  
**Versión:** 1.0.0  
**Estado:**  Producción Ready

---

**Documentos Relacionados:**
- [BASEINPUT-GUIDE.md](./BASEINPUT-GUIDE.md) - Guía del componente BaseInput
- [BASEINPUT-TEST-VIEW.md](./BASEINPUT-TEST-VIEW.md) - Vista de prueba de BaseInput
- [CODE-REFACTORING-GUIDE.md](./CODE-REFACTORING-GUIDE.md) - Patrones de código limpio
