<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Equipos con sus reservas:\n\n";
\App\Models\Equipment::with('reservations')->get()->each(function($eq) {
    $count = $eq->reservations->count();
    echo "ID {$eq->id} - {$eq->identifier}: {$count} reservas\n";

    if ($count > 0) {
        foreach ($eq->reservations as $res) {
            echo "  → Reserva #{$res->id}: {$res->start_time} - {$res->end_time} ({$res->status})\n";
        }
    }
});
