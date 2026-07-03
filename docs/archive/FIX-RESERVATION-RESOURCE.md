#  Fix: ReservationResource Not Found

##  Problema

Al presionar "Continuar con este equipo", se producía un error 500:

```
Class "App\Http\Resources\ReservationResource" not found
File: C:\laragon\www\Lab-Reserva\app\Http\Controllers\Api\ReservationController.php
Line: 92
```

##  Diagnóstico

1. El `ReservationController` estaba intentando usar `ReservationResource::collection()` en el método `indexForEquipment()`
2. La clase `App\Http\Resources\ReservationResource` **no existía** en el proyecto
3. El controller ya tenía el import correcto en línea 8:
   ```php
   use App\Http\Resources\ReservationResource;
   ```
4. Pero la clase real no había sido creada

##  Solución

Se creó el archivo `app/Http/Resources/ReservationResource.php` con la siguiente estructura:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Campos básicos
            'id' => $this->id,
            'user_id' => $this->user_id,
            'equipment_id' => $this->equipment_id,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones (solo si están cargadas)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            
            // Campos calculados útiles para el frontend
            'duration_minutes' => $this->start_time && $this->end_time 
                ? $this->start_time->diffInMinutes($this->end_time)
                : null,
            
            'is_active' => $this->start_time && $this->end_time
                ? now()->between($this->start_time, $this->end_time)
                : false,
            
            'is_past' => $this->end_time 
                ? now()->greaterThan($this->end_time)
                : false,
            
            'is_future' => $this->start_time
                ? now()->lessThan($this->start_time)
                : false,
        ];
    }
}
```

##  Características del Resource

### Campos Básicos
- `id`, `user_id`, `equipment_id`, `status`
- Fechas en formato ISO 8601 para compatibilidad con FullCalendar

### Relaciones Condicionales
- `user`: Solo si se hace eager loading, devuelve id, name, email
- `equipment`: Solo si se hace eager loading, usa EquipmentResource

### Campos Calculados
- `duration_minutes`: Duración total de la reserva en minutos
- `is_active`: ¿Está activa ahora? (start <= NOW < end)
- `is_past`: ¿Ya terminó? (end < NOW)
- `is_future`: ¿Aún no empieza? (start > NOW)

##  Uso en el Controller

```php
public function indexForEquipment(
    Request $request,
    equipment $equipment
): AnonymousResourceCollection {
    $filters = [
        'status' => $request->input('status', 'confirmed'),
        'start_date' => $request->input('start_date'),
        'end_date' => $request->input('end_date'),
    ];

    $reservations = $this->reservationService->getReservationsForEquipment(
        $equipment,
        $filters
    );

    return ReservationResource::collection($reservations);
}
```

##  Testing

### Verificar Reservas Existentes
```bash
php check-reservations.php
```

Output esperado:
```
Equipos con sus reservas:

ID 1 - LAB-PC-001: 1 reservas
  → Reserva #1: 2025-10-14 19:47:11 - 2025-10-14 22:47:11 (confirmed)
ID 2 - PC-DEMO-001: 1 reservas
  → Reserva #2: 2025-10-14 23:47:11 - 2025-10-15 01:47:11 (confirmed)
ID 3 - PC-DEMO-002: 0 reservas
ID 4 - PC-DEMO-003: 0 reservas
```

### Probar el Resource
```bash
php test-reservation-endpoint.php
```

### Endpoint API
```
GET /api/v1/equipment/{equipment}/reservations
Query params:
  - start_date (opcional): YYYY-MM-DD
  - end_date (opcional): YYYY-MM-DD
  - status (default: confirmed)
```

##  Integración con Frontend

El composable `useReservations.js` ya está configurado para consumir este endpoint:

```javascript
const fetchReservationsForEquipment = async (equipmentId, startDate, endDate) => {
  loading.value = true;
  error.value = null;
  
  try {
    const params = {
      start_date: startDate,
      end_date: endDate,
      status: 'confirmed'
    };
    
    const response = await api.get(`/equipment/${equipmentId}/reservations`, { params });
    reservations.value = response.data.data; // Array de ReservationResource
    return reservations.value;
  } catch (err) {
    // ...
  }
};
```

El componente `ReservationCalendar.vue` transforma estos datos a formato FullCalendar:

```javascript
const calendarEvents = computed(() => {
  return reservations.value.map(reservation => ({
    id: reservation.id,
    title: 'Reservado',
    start: reservation.start_time, // Ya en ISO 8601
    end: reservation.end_time,     // Ya en ISO 8601
    backgroundColor: '#ef4444',
    borderColor: '#dc2626',
    textColor: '#ffffff'
  }));
});
```

##  Estado Final

-  `ReservationResource.php` creado
-  Controller importando correctamente
-  Formato compatible con FullCalendar (ISO 8601)
-  Campos calculados para facilitar lógica frontend
-  Relaciones condicionales para evitar N+1
-  Scripts de testing creados

##  Próximos Pasos

1. Refrescar la página en el navegador
2. Volver a intentar el flujo completo:
   - Seleccionar laboratorio
   - Seleccionar equipo disponible (PC-DEMO-002)
   - El calendario debería cargar sin errores (0 reservas)
3. Intentar con equipo que tiene reservas (LAB-PC-001):
   - Debería mostrar bloque rojo en el calendario

##  Notas

- El equipo PC-DEMO-002 (ID: 3) NO tiene reservas, por eso la API devuelve array vacío `{"data": []}`
- Esto es **correcto** y no debe producir error 500
- FullCalendar puede manejar arrays vacíos sin problema
