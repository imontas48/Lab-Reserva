<?php

/**
 * Script de prueba para verificar los datos en la base de datos
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Verificación de Datos en la Base de Datos ===\n\n";

// Contar registros
$labsCount = App\Models\labs::count();
$equipmentCount = App\Models\equipment::count();
$reservationsCount = App\Models\reservations::count();
$softwareCount = App\Models\software::count();

echo "Total de Laboratorios: " . $labsCount . "\n";
echo "Total de Equipos: " . $equipmentCount . "\n";
echo "Total de Reservaciones: " . $reservationsCount . "\n";
echo "Total de Software: " . $softwareCount . "\n\n";

// Verificar laboratorios activos
$activeLabs = App\Models\labs::where('is_active', true)->count();
echo "Laboratorios Activos (is_active = true): " . $activeLabs . "\n\n";

// Ver detalles de los laboratorios
echo "=== Detalles de Laboratorios ===\n";
$labs = App\Models\labs::all();
foreach ($labs as $lab) {
    echo "ID: {$lab->id}, Nombre: {$lab->name}, Activo: " . ($lab->is_active ? 'Sí' : 'No') . "\n";
}

echo "\n=== Detalles de Equipos ===\n";
$equipment = App\Models\equipment::all();
foreach ($equipment as $eq) {
    echo "ID: {$eq->id}, Nombre: {$eq->name}, Lab ID: {$eq->lab_id}\n";
}

echo "\n=== Detalles de Reservaciones ===\n";
$reservations = App\Models\reservations::all();
foreach ($reservations as $res) {
    echo "ID: {$res->id}, User ID: {$res->user_id}, Equipment ID: {$res->equipment_id}, ";
    echo "Estado: {$res->status}, Inicio: {$res->start_time}, Fin: {$res->end_time}\n";
}

echo "\n=== Detalles de Software ===\n";
$software = App\Models\software::all();
foreach ($software as $sw) {
    echo "ID: {$sw->id}, Nombre: {$sw->name}\n";
}

echo "\n=== Fin de Verificación ===\n";
