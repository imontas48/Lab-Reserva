<?php
/**
 * Test temporal para verificar el endpoint de estadísticas del dashboard
 *
 * Para probar:
 * 1. Asegúrate de estar autenticado
 * 2. Visita: http://lab-reserva.test/test-dashboard-stats.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Route;

// Bootstrap de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Crear una request simulada
$request = Illuminate\Http\Request::create('/api/v1/dashboard/stats', 'GET');

// Procesar la request
$response = $kernel->handle($request);

// Mostrar el resultado
header('Content-Type: application/json');
echo json_encode([
    'status' => $response->getStatusCode(),
    'data' => json_decode($response->getContent(), true),
], JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);
