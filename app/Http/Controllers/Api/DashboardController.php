<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\equipment;
use App\Models\labs;
use App\Models\reservations;
use App\Models\software;
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
        $availableLabs = labs::where('is_active', true)->count();

        // Contar equipos registrados
        $totalEquipment = equipment::count();

        // Contar reservas activas del usuario actual
        // Activas = confirmadas y no canceladas, con fecha futura o actual
        $activeReservations = reservations::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->where(function($query) {
                $query->where('end_time', '>=', now())
                      ->orWhereNull('end_time');
            })
            ->count();

        // Contar software disponible
        $availableSoftware = software::count();

        // Obtener próximas reservas del usuario (máximo 5)
        // Incluye reservas activas (en curso) y futuras
        $upcomingReservations = reservations::with(['equipment.lab'])
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
                        ? $reservation->equipment->name
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
