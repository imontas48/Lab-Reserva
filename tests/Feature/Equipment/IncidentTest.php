<?php

namespace Tests\Feature\Equipment;

use App\Models\Equipment;
use App\Models\EquipmentIncident;
use App\Models\User;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    public function test_cualquier_usuario_reporta_una_incidencia(): void
    {
        $equipment = Equipment::factory()->create();
        $student = $this->actingAsStudent();

        $this->postJson("/api/v1/equipment/{$equipment->id}/incidents", [
            'description' => 'El monitor parpadea y se apaga cada pocos minutos.',
            'severity' => 'high',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.severity', 'high')
            ->assertJsonPath('data.reporter.id', $student->id)
            ->assertJsonPath('data.equipment.identifier', $equipment->identifier);

        $this->postJson("/api/v1/equipment/{$equipment->id}/incidents", ['description' => 'corto'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_el_detalle_del_equipo_expone_sus_incidencias_abiertas(): void
    {
        $equipment = Equipment::factory()->create();
        EquipmentIncident::factory()->forEquipment($equipment)->create();
        EquipmentIncident::factory()->forEquipment($equipment)->resolved()->create();
        $this->actingAsStudent();

        $this->getJson("/api/v1/equipment/{$equipment->id}/incidents")->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_solo_quien_atiende_incidencias_ve_el_listado_global(): void
    {
        EquipmentIncident::factory()->count(2)->create();
        EquipmentIncident::factory()->resolved()->create();

        $this->actingAsTeacher();
        $this->getJson('/api/v1/incidents')->assertForbidden();

        $this->actingAsAdmin();
        $this->getJson('/api/v1/incidents')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/incidents?include_resolved=1')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_el_administrador_resuelve_y_devuelve_el_equipo_al_servicio(): void
    {
        $equipment = Equipment::factory()->nonOperational()->create();
        $incident = EquipmentIncident::factory()->forEquipment($equipment)->create();
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/v1/incidents/{$incident->id}", ['status' => 'resolved'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['resolution']);

        $this->patchJson("/api/v1/incidents/{$incident->id}", [
            'status' => 'resolved',
            'resolution' => 'Cable de vídeo sustituido.',
            'equipment_operational' => true,
        ])->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.resolver.id', $admin->id)
            ->assertJsonPath('data.equipment.is_operational', true);

        $this->assertTrue($equipment->fresh()->is_operational);

        $this->patchJson("/api/v1/incidents/{$incident->id}", ['status' => 'open'])->assertStatus(422);
    }

    public function test_un_profesor_no_puede_resolver(): void
    {
        $incident = EquipmentIncident::factory()->create();
        $this->actingAsTeacher();

        $this->patchJson("/api/v1/incidents/{$incident->id}", ['status' => 'in_progress'])->assertForbidden();
    }

    public function test_quien_reporta_ve_su_incidencia_pero_no_las_ajenas(): void
    {
        $student = $this->actingAsStudent();
        $propia = EquipmentIncident::factory()->create(['reported_by' => $student->id]);
        $ajena = EquipmentIncident::factory()->create(['reported_by' => User::factory()->create()->id]);

        $this->getJson("/api/v1/incidents/{$propia->id}")->assertOk();
        $this->getJson("/api/v1/incidents/{$ajena->id}")->assertForbidden();
    }
}
