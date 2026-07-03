# Resultados del Test — Módulo "Reservas" (Vistas por Rol)

**Fecha de prueba:** 30/05/2026  
**Branch:** ProcesoDesarrollo1  
**Scope:** Nuevas vistas `/reservations/students` y `/reservations/teachers` + endpoint `GET /api/v1/reservations/by-role/{role}`

---

##  Qué funcionó correctamente

| # | Componente | Resultado |
|---|---|---|
| 1 | Ruta `GET /api/v1/reservations/by-role/{role}` | Registrada y resuelta correctamente antes de `{reservation}` |
| 2 | `ReservationService::getReservationsByUserRole()` | Devuelve 6/6 reservas con relaciones `user`, `equipment.lab` cargadas |
| 3 | `ReservationResource` | Incluye `is_future`, `is_active`, `is_past`, `duration_minutes` — campos requeridos por el frontend |
| 4 | `EquipmentResource` | Incluye la relación `lab` vía `whenLoaded` |
| 5 | Menú "Reservas" en AppLayout | Dropdown visible para todos los roles; subitems de admin solo visibles para admin |
| 6 | Rutas Vue `/reservations/students` y `/reservations/teachers` | `requiresAdmin: true` aplicado correctamente — no accesibles a student/teacher |
| 7 | Datos de prueba | 3 maestros + 3 estudiantes + 12 reservas creados sin conflictos, estados `confirmed` correctos |

---

## ️ Fallas y Observaciones para Mitigar

### FALLA-01 — Paginación no implementada en las vistas admin
**Severidad:** Media  
**Afecta:** `ReservationsStudentsView.vue`, `ReservationsTeachersView.vue`  
**Descripción:**  
El composable `fetchReservationsByRole` asigna `response.data.data` directamente a `reservations.value`, tomando solo los primeros 15 registros (una página). No existe UI para navegar a páginas siguientes ni se muestra el total de registros.  
**Impacto en producción:** Con muchos usuarios, el admin verá una lista incompleta sin saber que hay más registros.  
**Solución propuesta:**
- Exponer `meta` (total, current_page, last_page) del paginador en el composable.
- Agregar componente de paginación (`<Pagination>`) en ambas vistas.

---

### FALLA-02 — `useToast` importado pero no utilizado en las vistas admin
**Severidad:** Baja (linting)  
**Afecta:** `ReservationsStudentsView.vue`, `ReservationsTeachersView.vue`  
**Descripción:**  
`useToast` se importa en el `<script setup>` pero no se llama en ningún método. No genera errores en runtime pero es código muerto.  
**Solución propuesta:** Eliminar la importación o agregar un `toast.error()` en el bloque `catch` de `loadReservations`.

---

### FALLA-03 — El endpoint `cancel` no permite que el admin cancele reservas ajenas
**Severidad:** Media  
**Afecta:** `ReservationController::cancel()`  
**Descripción:**  
El método `cancel()` verifica `$reservation->user_id !== auth()->id()` y lanza un 403 si el usuario autenticado no es el dueño. Esto impide que un admin cancele reservas de otros usuarios.  
**Código afectado:** `app/Http/Controllers/Api/ReservationController.php` línea ~150.  
**Solución propuesta:**
```php
if ($reservation->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
    abort(403, 'No tienes permiso para cancelar esta reserva.');
}
```
O mejor aún: implementar la `ReservationPolicy` pendiente (`// TODO: Implementar autorización con Policy`).

---

### FALLA-04 — El endpoint `GET /api/v1/reservations` no está protegido solo para admins
**Severidad:** Media-Alta (seguridad)  
**Afecta:** `ReservationController::index()`  
**Descripción:**  
Cualquier usuario autenticado (student, teacher) puede llamar `GET /api/v1/reservations` y obtener **todas** las reservas del sistema. No existe una Policy ni verificación de rol explícita en ese método.  
**Nota:** El endpoint `indexByRole` nuevo SÍ verifica `role === 'admin'`.  
**Solución propuesta:** Agregar verificación al inicio de `index()`:
```php
if ($request->user()->role !== 'admin') {
    abort(403, 'Solo los administradores pueden ver todas las reservas.');
}
```
O implementar `$this->authorize('viewAny', reservations::class)` con la Policy correspondiente.

---

### FALLA-05 — `ReservationPolicy` no implementada (TODO pendiente)
**Severidad:** Media (deuda técnica)  
**Afecta:** `ReservationController` constructor  
**Descripción:**  
El constructor del controlador tiene un `TODO` comentado:
```php
// TODO: Implementar autorización con middleware o policies
// $this->authorizeResource(reservations::class, 'reservation');
```
Toda la autorización se hace con `abort(403, ...)` hardcodeados en lugar de usar la Policy de Laravel, violando la regla DEV-RULES §1.6.  
**Solución propuesta:** Crear `app/Policies/ReservationPolicy.php` con los métodos `viewAny`, `view`, `create`, `update`, `delete` y descomentar `authorizeResource`.

---

### FALLA-06 — Nombre inconsistente del composable en las vistas admin
**Severidad:** Baja  
**Descripción:**  
Las dos nuevas vistas usan `const { reservations, loading, error, fetchReservationsByRole } = useReservations()`. Sin embargo, el composable no usa estado local por instancia — si ambas vistas estuviesen montadas simultáneamente (ej. en un layout de tabs), compartirían el mismo `ref` internamente porque `useReservations` no implementa aislamiento de estado por llamada (los `ref` se crean dentro de la función, por lo que sí están aislados en realidad).  No es un problema real, solo una observación de arquitectura.

---

##  Resumen de Prioridades

| Prioridad | Falla | Acción |
|---|---|---|
|  Alta | FALLA-04 | Proteger `index()` — seguridad |
|  Media | FALLA-05 | Implementar `ReservationPolicy` completa |
|  Media | FALLA-03 | Permitir admin cancelar cualquier reserva |
|  Media | FALLA-01 | Agregar paginación en vistas admin |
|  Baja | FALLA-02 | Eliminar import `useToast` sin uso |
