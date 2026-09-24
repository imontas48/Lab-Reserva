<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\Software;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Obtiene las estadísticas para el dashboard
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $availableLabs = Lab::where('is_active', true)->count();
        $totalEquipment = Equipment::count();
        $availableSoftware = Software::count();

        // Activas = pendientes o confirmadas cuya franja no ha terminado.
        $activeReservations = $user->activeReservations()->count();

        // Próximas reservas del usuario (máximo 5): en curso y futuras,
        // incluidas las solicitudes pendientes para que sepa que existen.
        $upcomingReservations = Reservation::with(['lab', 'equipment.lab'])
            ->where('user_id', $user->id)
            ->whereIn('status', Reservation::BLOCKING_STATUSES)
            ->where('end_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get()
            ->map(fn (Reservation $reservation) => [
                'id' => $reservation->id,
                'type' => $reservation->type,
                'status' => $reservation->status,
                'purpose' => $reservation->purpose,
                'lab_name' => $reservation->lab?->name
                    ?? $reservation->equipment?->lab?->name
                    ?? 'N/A',
                'equipment_name' => $reservation->isLabReservation()
                    ? 'Laboratorio completo'
                    : ($reservation->equipment?->identifier ?? 'N/A'),
                'start_datetime' => $reservation->start_time,
                'end_datetime' => $reservation->end_time,
            ]);

        return response()->json([
            'stats' => [
                'available_labs' => $availableLabs,
                'total_equipment' => $totalEquipment,
                'active_reservations' => $activeReservations,
                'available_software' => $availableSoftware,
            ],
            'upcoming_reservations' => $upcomingReservations,
        ]);
    }
}
