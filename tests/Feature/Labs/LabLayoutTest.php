<?php

namespace Tests\Feature\Labs;

use App\Models\Equipment;
use App\Models\EquipmentIncident;
use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LabLayoutTest extends TestCase
{
    public function test_el_administrador_define_el_plano(): void
    {
        $lab = Lab::factory()->create();
        [$a, $b] = Equipment::factory()->count(2)->inLab($lab)->create();
        $this->actingAsAdmin();

        $this->putJson("/api/v1/labs/{$lab->id}/layout", [
            'grid_rows' => 3,
            'grid_cols' => 4,
            'positions' => [
                ['equipment_id' => $a->id, 'row' => 1, 'col' => 1],
                ['equipment_id' => $b->id, 'row' => 3, 'col' => 4],
            ],
        ])->assertOk()
            ->assertJsonPath('data.lab.grid_rows', 3)
            ->assertJsonPath('data.equipment.0.grid_row', 1)
            ->assertJsonPath('data.equipment.1.grid_col', 4);

        // Reposicionar deja sin celda a los que no se envian.
        $this->putJson("/api/v1/labs/{$lab->id}/layout", [
            'grid_rows' => 2, 'grid_cols' => 2,
            'positions' => [['equipment_id' => $a->id, 'row' => 2, 'col' => 2]],
        ])->assertOk();

        $this->assertNull($b->fresh()->grid_row);
    }

    public function test_el_plano_rechaza_celdas_repetidas_fuera_de_rango_o_equipos_ajenos(): void
    {
        $lab = Lab::factory()->create();
        [$a, $b] = Equipment::factory()->count(2)->inLab($lab)->create();
        $ajeno = Equipment::factory()->create();
        $this->actingAsAdmin();

        $this->putJson("/api/v1/labs/{$lab->id}/layout", [
            'grid_rows' => 2, 'grid_cols' => 2,
            'positions' => [
                ['equipment_id' => $a->id, 'row' => 1, 'col' => 1],
                ['equipment_id' => $b->id, 'row' => 1, 'col' => 1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['positions']);

        $this->putJson("/api/v1/labs/{$lab->id}/layout", [
            'grid_rows' => 2, 'grid_cols' => 2,
            'positions' => [['equipment_id' => $a->id, 'row' => 5, 'col' => 1]],
        ])->assertStatus(422);

        $this->putJson("/api/v1/labs/{$lab->id}/layout", [
            'grid_rows' => 2, 'grid_cols' => 2,
            'positions' => [['equipment_id' => $ajeno->id, 'row' => 1, 'col' => 1]],
        ])->assertStatus(422);
    }

    public function test_el_mapa_devuelve_el_estado_en_tiempo_real_de_cada_puesto(): void
    {
        $lab = Lab::factory()->create(['grid_rows' => 1, 'grid_cols' => 3]);
        $libre = Equipment::factory()->inLab($lab)->create(['grid_row' => 1, 'grid_col' => 1]);
        $enUso = Equipment::factory()->inLab($lab)->create(['grid_row' => 1, 'grid_col' => 2]);
        $averiado = Equipment::factory()->inLab($lab)->nonOperational()->create(['grid_row' => 1, 'grid_col' => 3]);
        Reservation::factory()->forEquipment($enUso)->confirmed()
            ->between(Carbon::now()->subMinutes(10), Carbon::now()->addMinutes(50))->create();
        EquipmentIncident::factory()->forEquipment($averiado)->create();

        $this->actingAsStudent();

        $response = $this->getJson("/api/v1/labs/{$lab->id}/map")->assertOk();

        $byId = collect($response->json('data.equipment'))->keyBy('id');
        $this->assertSame('available', $byId[$libre->id]['status']['status']);
        $this->assertSame('in_use', $byId[$enUso->id]['status']['status']);
        $this->assertSame('out_of_service', $byId[$averiado->id]['status']['status']);
        $this->assertSame(1, $byId[$averiado->id]['open_incidents_count']);
        $this->assertSame(3, $response->json('data.lab.grid_cols'));
    }

    public function test_un_profesor_no_puede_editar_el_plano(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsTeacher();

        $this->putJson("/api/v1/labs/{$lab->id}/layout", ['grid_rows' => 1, 'grid_cols' => 1, 'positions' => []])
            ->assertForbidden();
    }
}
