<?php

namespace Tests\Feature\Reservations;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Services\EquipmentService;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * La deteccion de solapamiento usaba whereBetween, que es inclusivo en ambos
 * extremos, de modo que una reserva de 11:00-12:00 chocaba con otra de
 * 10:00-11:00. Las franjas consecutivas -el caso de uso normal de un
 * laboratorio por horas- se rechazaban siempre.
 *
 * La regla vive ahora en un unico sitio, Reservation::scopeOverlapping, con
 * semantica de intervalo medio abierto.
 */
class OverlapTest extends TestCase
{
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->equipment = Equipment::factory()->create();
    }

    private function at(string $hour): Carbon
    {
        return Carbon::tomorrow()->setTimeFromTimeString($hour);
    }

    private function reserve(string $from, string $to): TestResponse
    {
        return $this->postJson('/api/v1/reservations', [
            'equipment_id' => $this->equipment->id,
            'start_time' => $this->at($from)->toIso8601String(),
            'end_time' => $this->at($to)->toIso8601String(),
        ]);
    }

    public function test_dos_reservas_consecutivas_son_validas(): void
    {
        $this->actingAsStudent();

        // Este es el caso que el sistema rechazaba: el fin de una coincide
        // exactamente con el inicio de la siguiente.
        $this->reserve('10:00', '11:00')->assertCreated();
        $this->reserve('11:00', '12:00')->assertCreated();

        $this->assertSame(2, Reservation::count());
    }

    public function test_una_reserva_que_termina_donde_empieza_otra_tampoco_choca(): void
    {
        $this->actingAsStudent();

        $this->reserve('10:00', '11:00')->assertCreated();
        $this->reserve('09:00', '10:00')->assertCreated();

        $this->assertSame(2, Reservation::count());
    }

    public function test_un_solapamiento_parcial_se_rechaza(): void
    {
        $this->actingAsStudent();

        $this->reserve('10:00', '11:00')->assertCreated();
        $this->reserve('10:30', '11:30')->assertStatus(422);

        $this->assertSame(1, Reservation::count());
    }

    public function test_una_reserva_contenida_en_otra_se_rechaza(): void
    {
        $this->actingAsStudent();

        $this->reserve('10:00', '12:00')->assertCreated();
        $this->reserve('10:30', '11:00')->assertStatus(422);

        $this->assertSame(1, Reservation::count());
    }

    public function test_una_reserva_que_engloba_a_otra_se_rechaza(): void
    {
        $this->actingAsStudent();

        $this->reserve('10:30', '11:00')->assertCreated();
        $this->reserve('10:00', '12:00')->assertStatus(422);

        $this->assertSame(1, Reservation::count());
    }

    public function test_una_reserva_identica_se_rechaza(): void
    {
        $this->actingAsStudent();

        $this->reserve('10:00', '11:00')->assertCreated();
        $this->reserve('10:00', '11:00')->assertStatus(422);

        $this->assertSame(1, Reservation::count());
    }

    public function test_una_reserva_cancelada_libera_la_franja(): void
    {
        $this->actingAsStudent();

        $this->reserve('10:00', '11:00')->assertCreated();
        Reservation::first()->update(['status' => Reservation::STATUS_CANCELLED]);

        $this->reserve('10:00', '11:00')->assertCreated();
    }

    public function test_el_solapamiento_es_por_equipo_y_no_global(): void
    {
        $otro = Equipment::factory()->create();
        $this->actingAsStudent();

        $this->reserve('10:00', '11:00')->assertCreated();

        $this->postJson('/api/v1/reservations', [
            'equipment_id' => $otro->id,
            'start_time' => $this->at('10:00')->toIso8601String(),
            'end_time' => $this->at('11:00')->toIso8601String(),
        ])->assertCreated();
    }

    public function test_las_tres_implementaciones_coinciden(): void
    {
        // Equipment::isAvailableInRange solo tenia tres casos frente a los
        // cuatro de las otras copias, asi que declaraba disponible un equipo
        // que el servicio rechazaba a continuacion.
        $this->actingAsStudent();
        $this->reserve('10:00', '12:00')->assertCreated();

        $inicio = $this->at('10:30');
        $fin = $this->at('11:00');

        $this->assertFalse($this->equipment->fresh()->isAvailableInRange($inicio, $fin));
        $this->assertFalse(
            app(EquipmentService::class)
                ->isAvailable($this->equipment->fresh(), $inicio, $fin)
        );
    }
}
