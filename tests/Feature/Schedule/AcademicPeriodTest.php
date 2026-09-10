<?php

namespace Tests\Feature\Schedule;

use App\Models\AcademicPeriod;
use Tests\TestCase;

class AcademicPeriodTest extends TestCase
{
    public function test_el_administrador_gestiona_los_periodos(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/academic-periods', [
            'name' => '2026-2',
            'starts_on' => now()->subWeek()->toDateString(),
            'ends_on' => now()->addMonths(3)->toDateString(),
        ])->assertCreated()->assertJsonPath('data.is_current', true);

        $id = $response->json('data.id');

        $this->patchJson("/api/v1/academic-periods/{$id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_current', false);

        $this->assertNull(AcademicPeriod::current());

        $this->deleteJson("/api/v1/academic-periods/{$id}")->assertOk();
        $this->assertDatabaseCount('academic_periods', 0);
    }

    public function test_las_fechas_deben_ser_coherentes_y_el_nombre_unico(): void
    {
        AcademicPeriod::factory()->create(['name' => '2026-1']);
        $this->actingAsAdmin();

        $this->postJson('/api/v1/academic-periods', [
            'name' => '2026-1',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-08-01',
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'ends_on']);
    }

    public function test_los_demas_roles_solo_consultan(): void
    {
        AcademicPeriod::factory()->create();

        $this->actingAsTeacher();
        $this->getJson('/api/v1/academic-periods')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/academic-periods', [
            'name' => 'X',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-01',
        ])->assertForbidden();
    }
}
