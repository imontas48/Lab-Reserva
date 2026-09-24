<?php

namespace Tests\Feature\Reservations;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regla estricta de conflicto entre una clase (laboratorio completo) y las
 * reservas de equipos sueltos de ese laboratorio, en ambos sentidos.
 */
class CrossConflictTest extends TestCase
{
    private Carbon $inicio;

    private Carbon $fin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inicio = Carbon::tomorrow()->setTimeFromTimeString('10:00:00');
        $this->fin = Carbon::tomorrow()->setTimeFromTimeString('12:00:00');
    }

    /**
     * @return array<string, string>
     */
    private function window(?Carbon $start = null, ?Carbon $end = null): array
    {
        return [
            'start_time' => ($start ?? $this->inicio)->toIso8601String(),
            'end_time' => ($end ?? $this->fin)->toIso8601String(),
        ];
    }

    public function test_una_solicitud_pendiente_de_laboratorio_bloquea_sus_equipos(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()->forLab($lab)->pending()->between($this->inicio, $this->fin)->create();

        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', ['equipment_id' => $equipment->id] + $this->window())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id']);
    }

    public function test_una_clase_confirmada_bloquea_sus_equipos(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()->forLab($lab)->confirmed()->between($this->inicio, $this->fin)->create();

        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', ['equipment_id' => $equipment->id] + $this->window())
            ->assertStatus(422);
    }

    public function test_una_clase_no_bloquea_los_equipos_de_otro_laboratorio(): void
    {
        $labConClase = Lab::factory()->create();
        $otroLab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($otroLab)->create();
        Reservation::factory()->forLab($labConClase)->confirmed()->between($this->inicio, $this->fin)->create();

        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', ['equipment_id' => $equipment->id] + $this->window())
            ->assertCreated();
    }

    public function test_un_equipo_reservado_impide_apartar_su_laboratorio(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()->forEquipment($equipment)->confirmed()->between($this->inicio, $this->fin)->create();

        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', [
            'lab_id' => $lab->id,
            'purpose' => 'Clase de prueba',
        ] + $this->window())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lab_id']);
    }

    public function test_una_clase_rechazada_cancelada_o_expirada_libera_los_equipos(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();

        Reservation::factory()->forLab($lab)->rejected()->between($this->inicio, $this->fin)->create();
        Reservation::factory()->forLab($lab)->cancelled()->between($this->inicio, $this->fin)->create();
        Reservation::factory()->forLab($lab)->expired()->between($this->inicio, $this->fin)->create();

        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', ['equipment_id' => $equipment->id] + $this->window())
            ->assertCreated();
    }

    /**
     * Misma semantica de intervalo semiabierto que entre equipos: la clase
     * de 10-12 y la reserva de equipo de 12-13 son consecutivas, no chocan.
     */
    public function test_el_cruce_respeta_el_intervalo_semiabierto(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()->forLab($lab)->confirmed()->between($this->inicio, $this->fin)->create();

        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', ['equipment_id' => $equipment->id] + $this->window(
            $this->fin,
            (clone $this->fin)->addHour()
        ))->assertCreated();
    }

    /**
     * Un equipo dado de baja con una reserva vigente sigue ocupando el
     * laboratorio: la persona que lo reservo va a presentarse.
     */
    public function test_un_equipo_dado_de_baja_con_reserva_sigue_bloqueando_el_laboratorio(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()->forEquipment($equipment)->confirmed()->between($this->inicio, $this->fin)->create();
        $equipment->delete();

        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', [
            'lab_id' => $lab->id,
            'purpose' => 'Clase de prueba',
        ] + $this->window())->assertStatus(422);
    }

    /**
     * La fila del laboratorio es el mutex de la franja para ambos tipos de
     * reserva. Lo que se puede verificar de forma determinista es el orden:
     * el lock del laboratorio precede a la lectura de reservas.
     */
    public function test_el_laboratorio_se_bloquea_antes_de_comprobar_disponibilidad_al_reservar_un_equipo(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        $user = User::factory()->create();

        DB::enableQueryLog();

        app(ReservationService::class)->createReservation([
            'equipment_id' => $equipment->id,
            'start_time' => $this->inicio,
            'end_time' => $this->fin,
        ], $user);

        $this->assertLabLockPrecedesAvailabilityCheck(array_column(DB::getQueryLog(), 'query'));
        DB::disableQueryLog();
    }

    public function test_el_laboratorio_se_bloquea_antes_de_comprobar_disponibilidad_al_reservar_una_clase(): void
    {
        $lab = Lab::factory()->create();
        $user = User::factory()->teacher()->create();

        DB::enableQueryLog();

        app(ReservationService::class)->createLabReservation([
            'lab_id' => $lab->id,
            'start_time' => $this->inicio,
            'end_time' => $this->fin,
            'purpose' => 'Clase de prueba',
        ], $user);

        $this->assertLabLockPrecedesAvailabilityCheck(array_column(DB::getQueryLog(), 'query'));
        DB::disableQueryLog();
    }

    /**
     * @param  array<int, string>  $consultas
     */
    private function assertLabLockPrecedesAvailabilityCheck(array $consultas): void
    {
        $posicionLock = null;
        $posicionComprobacion = null;

        foreach ($consultas as $i => $sql) {
            if ($posicionLock === null && str_contains($sql, 'from `labs`') && str_contains($sql, 'for update')) {
                $posicionLock = $i;
            }
            if ($posicionComprobacion === null && str_contains($sql, 'from `reservations`')) {
                $posicionComprobacion = $i;
            }
        }

        $this->assertNotNull($posicionLock, 'No se bloqueo la fila del laboratorio.');
        $this->assertNotNull($posicionComprobacion, 'No se consulto la disponibilidad.');
        $this->assertLessThan(
            $posicionComprobacion,
            $posicionLock,
            'El bloqueo del laboratorio debe preceder a la comprobacion de disponibilidad.'
        );
    }
}
