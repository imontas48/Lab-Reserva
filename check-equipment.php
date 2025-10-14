<?php
// Script temporal para verificar equipos
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Total equipos: " . \App\Models\Equipment::count() . "\n";
echo "Operacionales: " . \App\Models\Equipment::where('is_operational', true)->count() . "\n";
echo "\nEquipos disponibles:\n";
\App\Models\Equipment::where('is_operational', true)->get()->each(function($eq) {
    echo "  - {$eq->identifier} (Lab: {$eq->lab_id})\n";
});
