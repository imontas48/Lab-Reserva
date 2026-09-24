<?php

namespace Tests\Feature\Equipment;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\Software;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EquipmentFilterTest extends TestCase
{
    public function test_filtra_los_equipos_por_software_instalado(): void
    {
        $matlab = Software::factory()->create(['name' => 'MATLAB']);
        $con = Equipment::factory()->create();
        $con->software()->attach($matlab);
        Equipment::factory()->create();

        $this->actingAsStudent();

        $this->getJson("/api/v1/equipment?software_id={$matlab->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $con->id);
    }

    public function test_filtra_los_equipos_libres_en_una_franja(): void
    {
        $lab = Lab::factory()->create();
        $libre = Equipment::factory()->inLab($lab)->create();
        $ocupado = Equipment::factory()->inLab($lab)->create();
        $averiado = Equipment::factory()->inLab($lab)->nonOperational()->create();
        $enLabConClase = Equipment::factory()->inLab(Lab::factory()->create())->create();

        $inicio = Carbon::tomorrow()->setTimeFromTimeString('10:00:00');
        $fin = Carbon::tomorrow()->setTimeFromTimeString('12:00:00');

        Reservation::factory()->forEquipment($ocupado)->confirmed()->between($inicio, $fin)->create();
        Reservation::factory()->forLab($enLabConClase->lab)->confirmed()->between($inicio, $fin)->create();

        $this->actingAsStudent();

        // http_build_query codifica el '+' del desfase horario, que en una
        // query cruda se leeria como espacio.
        $response = $this->getJson('/api/v1/equipment?'.http_build_query([
            'available_from' => $inicio->toIso8601String(),
            'available_to' => $fin->toIso8601String(),
        ]))->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame($libre->id, $response->json('data.0.id'));

        // Fuera de la franja el ocupado vuelve a estar libre.
        $this->getJson('/api/v1/equipment?'.http_build_query([
            'available_from' => $fin->toIso8601String(),
            'available_to' => $fin->copy()->addHour()->toIso8601String(),
        ]))->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_la_franja_de_disponibilidad_exige_ambos_extremos(): void
    {
        $this->actingAsStudent();

        $this->getJson('/api/v1/equipment?available_from='.Carbon::tomorrow()->toIso8601String())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['available_to']);
    }
}
