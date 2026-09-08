<?php

namespace Tests\Feature\Authorization;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Software;
use Tests\TestCase;

/**
 * LabController, EquipmentController y SoftwareController exponian el CRUD
 * completo sin ninguna comprobacion: el authorizeResource estaba comentado como
 * TODO y sus FormRequests devolvian `return true`. Las policies existian y
 * estaban registradas, pero no las invocaba nadie, asi que un estudiante podia
 * borrar el catalogo entero de laboratorios y equipos.
 */
class ResourceAuthorizationTest extends TestCase
{
    public function test_un_estudiante_no_puede_crear_laboratorios(): void
    {
        $this->actingAsStudent();

        $this->postJson('/api/v1/labs', [
            'name' => 'Lab pirata',
            'location' => 'Sotano',
            'capacity' => 10,
        ])->assertForbidden();

        $this->assertDatabaseMissing('labs', ['name' => 'Lab pirata']);
    }

    public function test_un_estudiante_no_puede_borrar_un_laboratorio(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsStudent();

        $this->deleteJson("/api/v1/labs/{$lab->id}")->assertForbidden();

        $this->assertDatabaseHas('labs', ['id' => $lab->id]);
    }

    public function test_un_estudiante_no_puede_modificar_un_laboratorio(): void
    {
        $lab = Lab::factory()->create(['name' => 'Original']);
        $this->actingAsStudent();

        $this->putJson("/api/v1/labs/{$lab->id}", [
            'name' => 'Secuestrado',
            'location' => $lab->location,
            'capacity' => $lab->capacity,
        ])->assertForbidden();

        $this->assertSame('Original', $lab->fresh()->name);
    }

    public function test_un_estudiante_no_puede_marcar_un_equipo_como_averiado(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAsStudent();

        $this->putJson("/api/v1/equipment/{$equipment->id}", [
            'lab_id' => $equipment->lab_id,
            'identifier' => $equipment->identifier,
            'is_operational' => false,
        ])->assertForbidden();

        $this->assertTrue($equipment->fresh()->is_operational);
    }

    public function test_un_estudiante_no_puede_borrar_software(): void
    {
        $software = Software::factory()->create();
        $this->actingAsStudent();

        $this->deleteJson("/api/v1/software/{$software->id}")->assertForbidden();

        $this->assertDatabaseHas('software', ['id' => $software->id]);
    }

    public function test_un_profesor_tampoco_administra_el_catalogo(): void
    {
        $this->actingAsTeacher();

        $this->postJson('/api/v1/labs', [
            'name' => 'Lab del profe',
            'location' => 'Aula 2',
            'capacity' => 20,
        ])->assertForbidden();
    }

    public function test_el_administrador_si_puede_crear_un_laboratorio(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/labs', [
            'name' => 'Lab legitimo',
            'location' => 'Edificio A',
            'capacity' => 25,
        ])->assertCreated();

        $this->assertDatabaseHas('labs', ['name' => 'Lab legitimo']);
    }

    public function test_cualquier_autenticado_puede_consultar_el_catalogo(): void
    {
        Lab::factory()->count(2)->create();
        $this->actingAsStudent();

        // El estudiante necesita ver laboratorios y equipos para reservar:
        // las policies dejan viewAny y view abiertos a proposito.
        $this->getJson('/api/v1/labs')->assertOk();
        $this->getJson('/api/v1/equipment')->assertOk();
        $this->getJson('/api/v1/software')->assertOk();
    }

    public function test_sin_autenticar_no_se_accede_a_nada(): void
    {
        $this->getJson('/api/v1/labs')->assertUnauthorized();
        $this->postJson('/api/v1/labs', [])->assertUnauthorized();
    }
}
