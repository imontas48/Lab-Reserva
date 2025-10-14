<?php
// Script temporal para probar el endpoint de reservas
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING RESERVATION RESOURCE ===\n\n";

// 1. Verificar que exista el equipment
$equipment = \App\Models\Equipment::find(3);
if (!$equipment) {
    echo "❌ Equipment 3 no existe\n";
    exit(1);
}

echo "✅ Equipment encontrado: {$equipment->identifier}\n\n";

// 2. Obtener reservas usando el servicio
$service = app(\App\Services\ReservationService::class);

$filters = [
    'status' => 'confirmed',
    'start_date' => '2025-10-12',
    'end_date' => '2025-10-18',
];

echo "📊 Buscando reservas con filtros:\n";
echo "   - Status: {$filters['status']}\n";
echo "   - Start: {$filters['start_date']}\n";
echo "   - End: {$filters['end_date']}\n\n";

try {
    $reservations = $service->getReservationsForEquipment($equipment, $filters);

    echo "✅ Reservas encontradas: {$reservations->count()}\n\n";

    if ($reservations->count() > 0) {
        echo "--- Detalles de Reservas ---\n";
        foreach ($reservations as $reservation) {
            echo "ID: {$reservation->id}\n";
            echo "User: {$reservation->user_id}\n";
            echo "Start: {$reservation->start_time}\n";
            echo "End: {$reservation->end_time}\n";
            echo "Status: {$reservation->status}\n";
            echo "---\n";
        }

        echo "\n--- Testing ReservationResource ---\n";
        $resource = new \App\Http\Resources\ReservationResource($reservations->first());
        $array = $resource->toArray(request());
        echo json_encode($array, JSON_PRETTY_PRINT);
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
