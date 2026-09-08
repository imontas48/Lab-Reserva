<?php

namespace Tests\Feature\Reservations;

use App\Models\Equipment;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Contrato de fecha y hora del sistema: el cliente envia ISO-8601, el backend
 * lo normaliza al huso de la aplicacion y las columnas DATETIME guardan siempre
 * ese mismo huso.
 *
 * Sin normalizar, MySQL convertia la cadena ISO aplicando el huso de la sesion:
 * "2026-09-09T10:30:00+00:00" se comparaba como 2026-09-09 06:30:00 con una
 * sesion en -04:00, cuatro horas por debajo del valor almacenado. La
 * comparacion nunca casaba, asi que la deteccion de solapamiento estaba rota en
 * el backend, no solo en el frontend.
 */
class DateTimeContractTest extends TestCase
{
    public function test_el_mismo_instante_en_distintos_formatos_produce_el_mismo_registro(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAsStudent();

        // Mismo instante expresado de tres formas: es exactamente la situacion
        // del frontend, donde el calendario manda hora local sin offset y el
        // formulario manda UTC con sufijo Z.
        $instante = Carbon::tomorrow()->setTimeFromTimeString('14:00:00');

        $formatos = [
            $instante->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            $instante->copy()->setTimezone('America/Santo_Domingo')->toIso8601String(),
            $instante->copy()->format('Y-m-d H:i:s'),
        ];

        foreach ($formatos as $i => $formato) {
            $otroEquipo = Equipment::factory()->create();

            $this->postJson('/api/v1/reservations', [
                'equipment_id' => $otroEquipo->id,
                'start_time' => $formato,
                'end_time' => Carbon::parse($formato)->addHour()->toIso8601String(),
            ])->assertCreated();
        }

        // Los tres registros apuntan al mismo instante.
        $inicios = Reservation::pluck('start_time')
            ->map(fn ($t) => $t->utc()->toIso8601String())
            ->unique();

        $this->assertCount(1, $inicios, 'Los tres formatos debian producir el mismo instante.');
        $this->assertSame($instante->utc()->toIso8601String(), $inicios->first());
    }

    public function test_la_api_devuelve_siempre_iso8601(): void
    {
        $duenio = $this->actingAsStudent();
        $reserva = Reservation::factory()->forUser($duenio)->future()->create();

        $response = $this->getJson("/api/v1/reservations/{$reserva->id}")->assertOk();

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $response->json('data.start_time')
        );
    }

    public function test_una_fecha_ilegible_da_error_de_validacion_y_no_una_excepcion(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', [
            'equipment_id' => $equipment->id,
            'start_time' => 'no-es-una-fecha',
            'end_time' => 'tampoco',
        ])->assertStatus(422)->assertJsonValidationErrors(['start_time']);
    }

    public function test_el_solapamiento_se_detecta_a_traves_de_husos_distintos(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAsStudent();

        $inicio = Carbon::tomorrow()->setTimeFromTimeString('10:00:00');

        $this->postJson('/api/v1/reservations', [
            'equipment_id' => $equipment->id,
            'start_time' => $inicio->toIso8601String(),
            'end_time' => $inicio->copy()->addHour()->toIso8601String(),
        ])->assertCreated();

        // El mismo intervalo expresado en otro huso debe seguir chocando.
        $this->postJson('/api/v1/reservations', [
            'equipment_id' => $equipment->id,
            'start_time' => $inicio->copy()->setTimezone('America/Santo_Domingo')->toIso8601String(),
            'end_time' => $inicio->copy()->addHour()->setTimezone('America/Santo_Domingo')->toIso8601String(),
        ])->assertStatus(422);

        $this->assertSame(1, Reservation::count());
    }
}
