<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Obtiene las estadísticas para el dashboard
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        // Contar laboratorios disponibles (is_active = true)
        $availableLabs = Lab::where('is_active', true)->count();

        // Contar equipos registrados
        $totalEquipment = Equipment::count();

        // Contar reservas activas del usuario actual
        // Activas = confirmadas y no canceladas, con fecha futura o actual
        $activeReservations = Reservation::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->where(function($query) {
                $query->where('end_time', '>=', now())
                      ->orWhereNull('end_time');
            })
            ->count();

        // Contar software disponible
        $availableSoftware = Software::count();

        // Obtener próximas reservas del usuario (máximo 5)
        // Incluye reservas activas (en curso) y futuras
        $upcomingReservations = Reservation::with(['equipment.lab'])
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->where('end_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_name' => $reservation->equipment && $reservation->equipment->lab
                        ? $reservation->equipment->lab->name
                        : 'N/A',
                    'equipment_name' => $reservation->equipment
                        ? $reservation->equipment->identifier
                        : 'N/A',
                    'start_datetime' => $reservation->start_time,
                    'end_datetime' => $reservation->end_time,
                ];
            });

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
