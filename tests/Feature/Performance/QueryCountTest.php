<?php

namespace Tests\Feature\Performance;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El coste de un listado no debe crecer con el numero de filas.
 *
 * EquipmentResource llamaba a getCurrentStatus() dos veces por recurso, y ese
 * metodo consultaba con $this->reservations(), un query builder nuevo que
 * ignora cualquier eager loading: cuatro consultas por equipo. Como
 * ReservationResource anida un EquipmentResource, una pagina de 15 reservas
 * disparaba unas 60 consultas adicionales.
 *
 * Estas pruebas cuentan consultas en vez de medir tiempos: es determinista y
 * falla en cuanto alguien reintroduce el N+1.
 */
class QueryCountTest extends TestCase
{
    /**
     * @return array{0: int, 1: mixed}
     */
    private function countQueries(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$count, $result];
    }

    public function test_el_listado_de_equipos_no_crece_con_el_numero_de_equipos(): void
    {
        $lab = Lab::factory()->create();
        Equipment::factory()->count(3)->inLab($lab)->create();
        $this->actingAsAdmin();

        // Peticion de calentamiento: la primera resuelve y cachea los permisos
        // efectivos, asi que sin ella las dos medidas no serian comparables.
        $this->getJson('/api/v1/equipment')->assertOk();

        [$pocos] = $this->countQueries(fn () => $this->getJson('/api/v1/equipment')->assertOk());

        Equipment::factory()->count(12)->inLab($lab)->create();

        [$muchos] = $this->countQueries(fn () => $this->getJson('/api/v1/equipment')->assertOk());

        // Con el N+1, pasar de 3 a 15 equipos multiplicaba las consultas por 5.
        $this->assertSame(
            $pocos,
            $muchos,
            "El número de consultas depende del número de equipos ({$pocos} con 3, {$muchos} con 15)."
        );
    }

    public function test_el_listado_de_reservas_no_crece_con_el_numero_de_reservas(): void
    {
        $user = User::factory()->create();
        $equipment = Equipment::factory()->count(3)->create();

        foreach ($equipment as $i => $eq) {
            Reservation::factory()->forEquipment($eq)->forUser($user)->create();
        }

        $this->actingAsAdmin();

        // Calentamiento, por lo mismo.
        $this->getJson('/api/v1/reservations')->assertOk();

        [$pocas] = $this->countQueries(fn () => $this->getJson('/api/v1/reservations')->assertOk());

        foreach (Equipment::factory()->count(9)->create() as $eq) {
            Reservation::factory()->forEquipment($eq)->forUser($user)->create();
        }

        [$muchas] = $this->countQueries(fn () => $this->getJson('/api/v1/reservations')->assertOk());

        $this->assertSame(
            $pocas,
            $muchas,
            "El número de consultas depende del número de reservas ({$pocas} con 3, {$muchas} con 12)."
        );
    }

    public function test_el_listado_de_equipos_se_mantiene_en_un_presupuesto_razonable(): void
    {
        Equipment::factory()->count(25)->create();
        $this->actingAsAdmin();
        $this->getJson('/api/v1/equipment?per_page=25')->assertOk();

        [$consultas] = $this->countQueries(fn () => $this->getJson('/api/v1/equipment?per_page=25')->assertOk());

        // Sesión, permisos, el propio listado y sus relaciones. El valor exacto
        // puede variar con el diseño; lo que no puede es escalar con las filas.
        $this->assertLessThan(
            15,
            $consultas,
            "Un listado de 25 equipos costó {$consultas} consultas."
        );
    }
}
