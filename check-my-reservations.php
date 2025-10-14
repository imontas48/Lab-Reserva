<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICANDO DATOS PARA MIS RESERVAS ===\n\n";

// 1. Obtener el usuario
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ No hay usuarios en la base de datos\n";
    exit(1);
}

echo "✅ Usuario: {$user->name} (ID: {$user->id})\n\n";

// 2. Obtener reservas del usuario
$reservations = \App\Models\Reservations::where('user_id', $user->id)
    ->with(['equipment.lab'])
    ->orderBy('start_time', 'desc')
    ->get();

echo "📊 Total de reservas del usuario: {$reservations->count()}\n\n";

if ($reservations->count() === 0) {
    echo "⚠️  El usuario no tiene reservas. Crea algunas con:\n";
    echo "   php artisan db:seed --class=ReservationSeeder\n";
    exit(0);
}

echo "--- DETALLES DE RESERVAS ---\n\n";

foreach ($reservations as $res) {
    echo "Reserva #{$res->id}\n";
    echo "  Equipo: {$res->equipment->identifier}\n";
    echo "  Lab: {$res->equipment->lab->name}\n";
    echo "  Inicio: {$res->start_time}\n";
    echo "  Fin: {$res->end_time}\n";
    echo "  Estado: {$res->status}\n";
    
    // Calcular estados
    $now = now();
    $isActive = $now->between($res->start_time, $res->end_time);
    $isFuture = $now->lessThan($res->start_time);
    $isPast = $now->greaterThan($res->end_time);
    
    echo "  Estado calculado: ";
    if ($res->status === 'cancelled') {
        echo "Cancelada\n";
    } elseif ($isActive) {
        echo "ACTIVA (En uso ahora) 🔵\n";
    } elseif ($isFuture) {
        echo "PROGRAMADA (Futura) 🟢\n";
    } elseif ($isPast) {
        echo "COMPLETADA (Pasada) ⚫\n";
    }
    echo "\n";
}

echo "\n=== PROBANDO ReservationResource ===\n\n";

$firstReservation = $reservations->first();
$resource = new \App\Http\Resources\ReservationResource($firstReservation);
$array = $resource->toArray(request());

echo "JSON Response:\n";
echo json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
