# Testing Equipment Status System

## Sistema de Estados Dinámicos para Equipos

El sistema calcula automáticamente el estado de cada equipo basándose en:
- Campo `is_operational` del equipo
- Reservas activas (start_time <= NOW < end_time)
- Reservas futuras (start_time > NOW)

## Estados Posibles

### 1. **Available** (Disponible) ✅
- Color: Verde
- Ícono: check
- Condición: Equipo operacional Y sin reservas activas/futuras
- Ejemplo: `{"status":"available","details":"Disponible","color":"green","icon":"check"}`

### 2. **In Use** (En uso hasta...) 🔵
- Color: Azul
- Ícono: clock
- Condición: Tiene reserva activa en este momento
- Ejemplo: `{"status":"in_use","details":"En uso hasta 14:30","until":"2024-01-15T14:30:00","color":"blue","icon":"clock"}`

### 3. **Reserved** (Reservado para...) 🟡
- Color: Amarillo
- Ícono: calendar
- Condición: Tiene reserva futura próxima
- Ejemplo: `{"status":"reserved","details":"Reservado para 15/01 16:00","next_reservation":"2024-01-15T16:00:00","color":"yellow","icon":"calendar"}`

### 4. **Out of Service** (Fuera de servicio) 🔴
- Color: Rojo
- Ícono: wrench
- Condición: is_operational = false
- Ejemplo: `{"status":"out_of_service","details":"Equipo en mantenimiento","color":"red","icon":"wrench"}`

## Archivos Modificados

### Backend

1. **app/Models/Equipment.php**
   - `getCurrentStatus()`: Calcula estado dinámico
   - `isAvailableInRange($start, $end)`: Valida disponibilidad en rango
   - `scopeCurrentlyAvailable()`: Query scope para equipos disponibles

2. **app/Http/Resources/EquipmentResource.php**
   - Campo `status`: Incluye resultado de `getCurrentStatus()`
   - Campo `is_available`: Compatibilidad con código legacy

3. **app/Http/Controllers/Api/EquipmentController.php**
   - `indexByLab()`: Eager load de reservations para optimización

### Frontend

1. **resources/js/views/reservations/ReservationsCreateView.vue**
   - Select dropdown: Muestra `status.details` en lugar de texto hardcoded
   - Preview card: Badge dinámico con colores según `status.color`
   - Breadcrumb: Badge de estado en paso 3
   - Validación: Usa `status.status !== 'available'` con mensajes específicos
   - Mensajes de error contextuales según tipo de estado

## Cómo Probar

### Preparación de Datos

```bash
# 1. Crear reserva ACTIVA (en curso)
php artisan tinker

$equipment = \App\Models\Equipment::first();
$user = \App\Models\User::first();

\App\Models\Reservations::create([
    'user_id' => $user->id,
    'equipment_id' => $equipment->id,
    'start_time' => now()->subHours(1), // Empezó hace 1 hora
    'end_time' => now()->addHours(2),   // Termina en 2 horas
    'purpose' => 'Prueba estado IN_USE',
    'status' => 'confirmed'
]);

# 2. Crear reserva FUTURA
$equipment2 = \App\Models\Equipment::skip(1)->first();

\App\Models\Reservations::create([
    'user_id' => $user->id,
    'equipment_id' => $equipment2->id,
    'start_time' => now()->addHours(3),  // Empieza en 3 horas
    'end_time' => now()->addHours(5),    // Termina en 5 horas
    'purpose' => 'Prueba estado RESERVED',
    'status' => 'confirmed'
]);

# 3. Marcar equipo como FUERA DE SERVICIO
$equipment3 = \App\Models\Equipment::skip(2)->first();
$equipment3->update(['is_operational' => false]);

# 4. Verificar estados
\App\Models\Equipment::all()->each(function($eq) {
    dump($eq->identifier . ': ' . json_encode($eq->getCurrentStatus()));
});
```

### Pruebas Frontend

1. **Ir a `/reservations/create`**
2. **Seleccionar un laboratorio**
3. **Observar select de equipos:**
   - ✅ Equipos disponibles: "PC-001 - Disponible"
   - 🔵 Equipos en uso: "PC-002 - En uso hasta 14:30"
   - 🟡 Equipos reservados: "PC-003 - Reservado para 15/01 16:00"
   - 🔴 Equipos fuera de servicio: "PC-004 - Equipo en mantenimiento" (deshabilitado)

4. **Seleccionar equipo disponible:**
   - Ver badge verde con "Disponible"
   - Botón "Continuar con este equipo" activo

5. **Seleccionar equipo NO disponible:**
   - Ver badge amarillo/azul/rojo según estado
   - Mensaje de advertencia explicando por qué no está disponible
   - Botón "Continuar" NO aparece

6. **Avanzar al calendario:**
   - Breadcrumb muestra nombre del equipo + badge de estado
   - Calendario permite seleccionar horarios futuros

## Validaciones Implementadas

### Backend
- `isAvailableInRange()` detecta solapamientos de reservas
- `scopeCurrentlyAvailable()` filtra equipos disponibles en queries

### Frontend
- Dropdown deshabilita opciones con `status !== 'available'`
- Validación en `confirmEquipmentSelection()` con mensajes contextuales:
  - `in_use`: "Este equipo está actualmente en uso"
  - `reserved`: "Este equipo ya tiene una reserva programada"
  - `out_of_service`: "Este equipo está fuera de servicio"

## Optimización de Consultas

El controller hace **eager loading** de reservations:
```php
$equipment->load(['reservations' => function ($query) {
    $query->where('start_time', '>=', now()->subHours(2))
          ->where('status', '!=', 'cancelled')
          ->orderBy('start_time', 'asc');
}]);
```

Esto evita el problema N+1 al calcular estados para múltiples equipos.

## Casos de Uso

### Caso 1: Estudiante busca equipo para ahora
- Ve inmediatamente qué equipos están "Disponibles" vs "En uso"
- No puede seleccionar equipos ocupados

### Caso 2: Profesor planifica reserva para mañana
- Ve que equipo está "Reservado para 15/01 10:00"
- Puede elegir otro horario o equipo

### Caso 3: Técnico marca equipo en mantenimiento
- Marca `is_operational = false`
- Estado cambia automáticamente a "Fuera de servicio"
- No aparece en listados de equipos disponibles

### Caso 4: Administrador monitorea equipos
- Vista rápida de todos los estados en tiempo real
- No necesita consultar calendario para cada equipo

## API Response Example

```json
GET /api/v1/labs/1/equipment

{
  "data": [
    {
      "id": 1,
      "identifier": "PC-LAB1-001",
      "type": "Computadora",
      "lab_id": 1,
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
      "id": 2,
      "identifier": "PC-LAB1-002",
      "type": "Computadora",
      "lab_id": 1,
      "is_operational": true,
      "status": {
        "status": "in_use",
        "details": "En uso hasta 14:30",
        "until": "2024-01-15T14:30:00.000000Z",
        "color": "blue",
        "icon": "clock"
      },
      "is_available": false
    }
  ]
}
```

## Próximos Pasos

- [ ] Agregar endpoint `GET /equipment/{id}/check-availability?start=X&end=Y`
- [ ] Implementar vista "Mis Reservas" para usuarios
- [ ] Dashboard de administrador con estadísticas de estados
- [ ] Notificaciones cuando equipo reservado queda disponible
