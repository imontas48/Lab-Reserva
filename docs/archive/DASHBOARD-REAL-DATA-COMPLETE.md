# Dashboard con Datos Reales - Implementación Completa

## 📋 Resumen

Se ha implementado la funcionalidad para cargar datos reales en el dashboard, reemplazando los valores estáticos por datos dinámicos obtenidos de la base de datos.

## 🎯 Cambios Realizados

### 1. Backend - Controlador de Dashboard

**Archivo:** `app/Http/Controllers/Api/DashboardController.php`

Se creó un nuevo controlador que proporciona un endpoint para obtener las estadísticas del dashboard:

```php
GET /api/v1/dashboard/stats
```

**Estadísticas que devuelve:**
- **Laboratorios Disponibles**: Cuenta laboratorios con `is_available = true`
- **Equipos Registrados**: Total de equipos en el sistema
- **Reservas Activas**: Reservas confirmadas del usuario con fecha actual o futura
- **Software Disponible**: Software con `is_available = true`
- **Próximas Reservas**: Lista de las próximas 5 reservas del usuario con detalles

**Respuesta JSON:**
```json
{
  "stats": {
    "available_labs": 12,
    "total_equipment": 48,
    "active_reservations": 3,
    "available_software": 24
  },
  "upcoming_reservations": [
    {
      "id": 1,
      "lab_name": "Laboratorio A",
      "equipment_name": "Computadora 1",
      "start_datetime": "2025-10-15 10:00:00",
      "end_datetime": "2025-10-15 12:00:00"
    }
  ]
}
```

### 2. Backend - Rutas API

**Archivo:** `routes/api.php`

Se agregó la ruta protegida:

```php
Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats'])
    ->name('api.dashboard.stats');
```

### 3. Frontend - Vista del Dashboard

**Archivo:** `resources/js/views/DashboardView.vue`

Se actualizó el componente Vue para:

#### Estado Reactivo
```javascript
const loading = ref(true);
const stats = ref({
  available_labs: 0,
  total_equipment: 0,
  active_reservations: 0,
  available_software: 0,
});
const upcomingReservations = ref([]);
```

#### Funcionalidades
- **Indicador de carga**: Muestra un spinner mientras se cargan los datos
- **Carga automática**: Al montar el componente, llama al endpoint del dashboard
- **Estadísticas dinámicas**: Las tarjetas muestran los valores reales de la base de datos
- **Próximas reservas**: Lista dinámica de las próximas reservas del usuario
- **Formato de fechas**: Función para formatear fechas en español

#### Características de UI
- Estado de carga con spinner animado
- Mensajes cuando no hay reservas próximas
- Enlaces a detalles de cada reserva
- Responsive design mantenido

## 🔍 Detalles Técnicos

### Campos de Base de Datos Utilizados

**Tabla `reservations`:**
- `start_time` (DATETIME)
- `end_time` (DATETIME)
- `status` (ENUM: 'confirmed', 'cancelled', 'completed')
- `user_id` (FK a users)
- `equipment_id` (FK a equipment)

**Relaciones:**
- `Reservations` → `Equipment` → `Lab`
- Permite obtener el nombre del laboratorio a través del equipo

### Lógica de Negocio

**Reservas Activas:**
- Estado = 'confirmed'
- Fecha de fin >= fecha actual
- Pertenecen al usuario autenticado

**Próximas Reservas:**
- Estado = 'confirmed'
- Fecha de inicio >= fecha actual
- Ordenadas por fecha de inicio ascendente
- Limitadas a 5 resultados

## 🧪 Pruebas

### Prueba del Endpoint

1. **Iniciar sesión** en la aplicación
2. **Navegar al dashboard**: `http://lab-reserva.test/dashboard`
3. **Verificar** que se muestren los datos correctos

### Prueba Manual del API

Puedes probar el endpoint directamente usando:

```bash
# Con autenticación Sanctum
curl -X GET http://lab-reserva.test/api/v1/dashboard/stats \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

O visitar el archivo de prueba:
```
http://lab-reserva.test/test-dashboard-stats.php
```

## 📊 Flujo de Datos

1. **Usuario accede al dashboard** → Component Vue mounted
2. **Vue llama** → `loadDashboardStats()`
3. **API Request** → `GET /api/v1/dashboard/stats`
4. **Backend consulta** → Base de datos (Labs, Equipment, Software, Reservations)
5. **Backend responde** → JSON con estadísticas
6. **Vue actualiza** → Estado reactivo
7. **UI renderiza** → Datos reales

## 🎨 Componentes del Dashboard

### Tarjetas de Estadísticas (4)
1. **Laboratorios Disponibles** (azul)
2. **Equipos Registrados** (verde)
3. **Mis Reservas Activas** (morado)
4. **Software Disponible** (naranja)

### Acciones Rápidas (3)
1. Nueva Reserva
2. Buscar Laboratorio
3. Ver Equipos

### Próximas Reservas
- Lista dinámica con información de cada reserva
- Botón para crear nueva reserva si no hay ninguna
- Enlaces a detalles de cada reserva

## 🔒 Seguridad

- ✅ Endpoint protegido con autenticación Sanctum
- ✅ Solo muestra datos del usuario autenticado
- ✅ Validación de permisos en backend
- ✅ Consultas optimizadas con Eloquent

## 📝 Notas Importantes

1. **Nombres de campos**: Se usan `start_time` y `end_time` (no `start_datetime` y `end_datetime`)
2. **Estados de reserva**: 'confirmed', 'cancelled', 'completed' (no 'approved')
3. **Relación con Lab**: A través de Equipment (Reservations → Equipment → Lab)
4. **Sin campo purpose**: La tabla reservations no tiene este campo actualmente

## 🚀 Próximos Pasos Sugeridos

1. Agregar caché para las estadísticas (Redis)
2. Implementar actualización automática cada X minutos
3. Agregar gráficos de estadísticas
4. Notificaciones de reservas próximas
5. Exportar estadísticas a PDF

## 📚 Archivos Modificados

- ✅ `app/Http/Controllers/Api/DashboardController.php` (NUEVO)
- ✅ `routes/api.php` (MODIFICADO)
- ✅ `resources/js/views/DashboardView.vue` (MODIFICADO)
- ✅ `test-dashboard-stats.php` (NUEVO - archivo de prueba)

---

**Fecha de Implementación**: 14 de octubre de 2025
**Estado**: ✅ Completo y funcional
