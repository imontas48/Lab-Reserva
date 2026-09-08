<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Requiere que el servidor ejecute `php artisan schedule:run` cada minuto.
|
*/

// Cada cinco minutos: una reserva que acaba de terminar deja de bloquear el
// equipo casi de inmediato, sin necesidad de una tarea por minuto.
// withoutOverlapping evita que dos ejecuciones se pisen si una se alarga.
Schedule::command('reservations:complete-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping();
