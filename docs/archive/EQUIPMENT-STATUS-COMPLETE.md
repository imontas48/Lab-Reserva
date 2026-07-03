#  Sistema de Estados Dinámicos para Equipos - COMPLETADO

##  Resumen de Implementación

Se ha implementado exitosamente un **sistema de estados dinámicos** para equipos que calcula automáticamente el estado actual basándose en:
- Campo `is_operational` del equipo
- Reservas activas (en curso en este momento)
- Reservas futuras programadas
- Disponibilidad sin conflictos

##  Estados Implementados

### 1.  Available (Disponible)
- **Color**: Verde (`bg-green-100 text-green-800`)
- **Ícono**: Check
- **Condición**: Equipo operacional SIN reservas activas o futuras
- **Acciones permitidas**: Puede ser reservado

### 2.  In Use (En uso hasta...)
- **Color**: Azul (`bg-blue-100 text-blue-800`)
- **Ícono**: Clock
- **Condición**: Tiene reserva activa ahora (`start_time <= NOW < end_time`)
- **Detalles**: Muestra hora de fin (ej: "En uso hasta 14:30")
- **Acciones permitidas**: NO puede ser reservado

### 3.  Reserved (Reservado para...)
- **Color**: Amarillo (`bg-yellow-100 text-yellow-800`)
- **Ícono**: Calendar
- **Condición**: Tiene reserva futura (`start_time > NOW`)
- **Detalles**: Muestra fecha/hora de inicio (ej: "Reservado para 15/01 16:00")
- **Acciones permitidas**: NO puede ser reservado

### 4.  Out of Service (Fuera de servicio)
- **Color**: Rojo (`bg-red-100 text-red-800`)
- **Ícono**: Wrench
- **Condición**: `is_operational = false`
- **Detalles**: "Equipo en mantenimiento"
- **Acciones permitidas**: NO puede ser reservado

##  Archivos Modificados

### Backend

#### 1. `app/Models/Equipment.php`
```php
// Método principal que calcula el estado
public function getCurrentStatus(): array

// Valida disponibilidad en un rango de tiempo
public function isAvailableInRange($startTime, $endTime): bool

// Query scope para filtrar equipos disponibles
public function scopeCurrentlyAvailable($query)
```

#### 2. `app/Http/Resources/EquipmentResource.php`
```php
'status' => $this->getCurrentStatus(),
'is_available' => $this->getCurrentStatus()['status'] === 'available'
```

#### 3. `app/Http/Controllers/Api/EquipmentController.php`
```php
// Eager loading de reservations para optimización N+1
$equipment->load(['reservations' => function ($query) {
    $query->where('start_time', '>=', now()->subHours(2))
          ->where('status', '!=', 'cancelled')
          ->orderBy('start_time', 'asc');
}]);
```

### Frontend

#### 4. `resources/js/views/reservations/ReservationsCreateView.vue`

**Select Dropdown (Paso 2):**
```vue
<option
  v-for="eq in labEquipment"
  :key="eq.id"
  :value="eq.id"
  :disabled="eq.status?.status !== 'available'"
>
  {{ eq.name || eq.identifier }} - {{ eq.status?.details }}
</option>
```

**Preview Card con Badge Dinámico:**
```vue
<span
  v-if="selectedEquipmentPreview?.status"
  :class="{
    'bg-green-100 text-green-800': selectedEquipmentPreview.status.color === 'green',
    'bg-blue-100 text-blue-800': selectedEquipmentPreview.status.color === 'blue',
    'bg-yellow-100 text-yellow-800': selectedEquipmentPreview.status.color === 'yellow',
    'bg-red-100 text-red-800': selectedEquipmentPreview.status.color === 'red'
  }"
  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
>
  <svg><!-- Ícono dinámico --></svg>
  {{ selectedEquipmentPreview.status.details }}
</span>
```

**Validación Mejorada:**
```javascript
if (equipment.status?.status !== 'available') {
  const statusMessages = {
    'in_use': 'Este equipo está actualmente en uso',
    'reserved': 'Este equipo ya tiene una reserva programada',
    'out_of_service': 'Este equipo está fuera de servicio'
  };
  toast.error(statusMessages[equipment.status?.status] || 'No disponible');
  return;
}
```

**Mensaje de Advertencia:**
```vue
<div v-else class="mt-4 rounded-lg bg-yellow-50 border border-yellow-200 p-3">
  <p class="text-sm text-yellow-800">
    Este equipo no está disponible en este momento. Por favor selecciona otro.
  </p>
</div>
```

**Breadcrumb con Estado (Paso 3):**
```vue
<span
  v-if="selectedEquipment.status"
  :class="{/* Colores dinámicos */}"
  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1"
>
  {{ selectedEquipment.status.details }}
</span>
```

### Utilidades

#### 5. `database/seeders/ReservationSeeder.php`
Seeder que crea datos de prueba demostrando los 4 estados:
- Crea equipos si no hay suficientes
- Genera reserva ACTIVA (en curso)
- Genera reserva FUTURA
- Deja un equipo DISPONIBLE
- Marca un equipo como FUERA DE SERVICIO

#### 6. `docs/EQUIPMENT-STATUS-TESTING.md`
Documentación completa sobre:
- Descripción de cada estado
- Cómo probar el sistema
- Ejemplos de API responses
- Casos de uso
- Próximos pasos

##  Datos de Prueba Creados

Se ejecutó `php artisan db:seed --class=ReservationSeeder` que creó:

```
 Estados calculados:
 LAB-PC-001: En uso hasta 22:47
 PC-DEMO-001: Reservado para 14/10 23:47
 PC-DEMO-002: Disponible
 PC-DEMO-003: Equipo en mantenimiento
```

##  UX Implementada

### Vista de Selección de Equipos (Paso 2)

1. **Select Dropdown:**
   - Muestra estado al lado del nombre del equipo
   - Opciones deshabilitadas si no están disponibles
   - Texto claro: "PC-001 - Disponible" vs "PC-002 - En uso hasta 14:30"

2. **Tarjeta de Preview:**
   - Badge con color dinámico según estado
   - Ícono contextual (check/clock/calendar/wrench)
   - Detalles específicos del estado

3. **Botón de Acción:**
   - Se muestra SOLO si el equipo está disponible
   - Si NO está disponible, aparece mensaje de advertencia

4. **Breadcrumb (Paso 3):**
   - Muestra nombre del equipo seleccionado
   - Badge pequeño con estado actual
   - Color y texto coherentes con el resto de la UI

##  Ejemplo de API Response

```json
GET /api/v1/labs/1/equipment

{
  "data": [
    {
      "id": 1,
      "identifier": "LAB-PC-001",
      "type": "Computadora",
      "is_operational": true,
      "status": {
        "status": "in_use",
        "details": "En uso hasta 22:47",
        "until": "2024-10-14T22:47:00.000000Z",
        "color": "blue",
        "icon": "clock"
      },
      "is_available": false
    },
    {
      "id": 2,
      "identifier": "PC-DEMO-001",
      "type": "Computadora",
      "is_operational": true,
      "status": {
        "status": "reserved",
        "details": "Reservado para 14/10 23:47",
        "next_reservation": "2024-10-14T23:47:00.000000Z",
        "color": "yellow",
        "icon": "calendar"
      },
      "is_available": false
    },
    {
      "id": 3,
      "identifier": "PC-DEMO-002",
      "type": "Computadora",
      "is_operational": true,
      "status": {
        "status": "available",
        "details": "Disponible",
        "color": "green",
        "icon": "check"
      },
      "is_available": true
    },
    {
      "id": 4,
      "identifier": "PC-DEMO-003",
      "type": "Computadora",
      "is_operational": false,
      "status": {
        "status": "out_of_service",
        "details": "Equipo en mantenimiento",
        "color": "red",
        "icon": "wrench"
      },
      "is_available": false
    }
  ]
}
```

##  Características Destacadas

### 1. **Cálculo Dinámico en Tiempo Real**
- NO se almacena en base de datos
- Se calcula en cada request basándose en NOW()
- Siempre preciso y actualizado

### 2. **Optimización de Queries**
- Eager loading de reservations en el controller
- Filtra solo reservas relevantes (últimas 2 horas + futuras)
- Evita problema N+1

### 3. **Retrocompatibilidad**
- Campo `is_available` legacy sigue funcionando
- Basado en `status.status === 'available'`

### 4. **Validación Robusta**
- Backend: `isAvailableInRange()` detecta solapamientos
- Frontend: Validación antes de abrir modal
- Mensajes de error contextuales según el estado

### 5. **UI/UX Consistente**
- Colores coherentes en toda la aplicación
- Íconos contextuales
- Texto descriptivo claro

##  Cómo Probar

### 1. Iniciar Servidores
```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite
npm run dev
```

### 2. Crear Datos de Prueba
```bash
php artisan db:seed --class=ReservationSeeder
```

### 3. Navegar al Frontend
```
http://localhost:8000/reservations/create
```

### 4. Flujo de Prueba

**Paso 1: Seleccionar Lab**
- Click en "Computo 1" (o el lab creado)

**Paso 2: Observar Estados**
-  Ver equipo "En uso hasta HH:mm" (deshabilitado)
-  Ver equipo "Reservado para dd/mm HH:mm" (deshabilitado)
-  Ver equipo "Disponible" (seleccionable)
-  Ver equipo "Equipo en mantenimiento" (deshabilitado)

**Paso 3: Seleccionar Equipo Disponible**
- Click en select → elegir equipo verde
- Ver tarjeta de preview con badge verde "Disponible"
- Click en "Continuar con este equipo"

**Paso 4: Verificar Breadcrumb**
- Ver nombre del equipo con badge de estado
- Calendario debe cargarse correctamente

**Paso 5: Intentar Seleccionar NO Disponible**
- Volver atrás
- Intentar seleccionar equipo azul/amarillo/rojo
- Ver mensaje de advertencia
- Botón "Continuar" NO aparece

##  Próximos Pasos Sugeridos

- [ ] Endpoint `GET /equipment/{id}/check-availability?start=X&end=Y`
- [ ] Vista "Mis Reservas" con listado y cancelación
- [ ] Dashboard de administrador con estadísticas de estados
- [ ] Notificaciones cuando equipo reservado queda disponible
- [ ] Filtros en listado de equipos por estado
- [ ] Búsqueda de equipos disponibles en rango de tiempo específico

##  Conclusión

El sistema de estados dinámicos está **100% funcional** y proporciona:
-  Feedback visual inmediato sobre disponibilidad
-  Prevención de reservas en equipos no disponibles
-  Información contextual precisa (hasta cuándo está en uso, para cuándo está reservado)
-  UX mejorada con colores, íconos y mensajes claros
-  Código mantenible y escalable
-  Optimización de queries para performance

El usuario ahora puede ver de un vistazo el estado de TODOS los equipos sin necesidad de consultar calendarios o hacer clicks innecesarios.
