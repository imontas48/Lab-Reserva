<?php

namespace Tests\Feature\Reports;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    private function seedRange(): Lab
    {
        $lab = Lab::factory()->create(['name' => 'Redes']);
        $equipment = Equipment::factory()->inLab($lab)->create();
        $student = User::factory()->student()->create(['name' => 'Ana']);

        $lunes = Carbon::now()->subDays(3)->setTimeFromTimeString('09:00:00');

        Reservation::factory()->forUser($student)->forEquipment($equipment)
            ->between($lunes, $lunes->copy()->addHours(2))->completed()->withCheckIn()->checkedIn()->create();
        Reservation::factory()->forUser($student)->forEquipment($equipment)
            ->between($lunes->copy()->addDay(), $lunes->copy()->addDay()->addHour())->noShow()->withCheckIn()->create();
        Reservation::factory()->forLab($lab)->confirmed()
            ->between($lunes->copy()->addDays(2), $lunes->copy()->addDays(2)->addHours(3))->create();
        Reservation::factory()->forUser($student)->forEquipment($equipment)
            ->between($lunes->copy()->subMonths(2), $lunes->copy()->subMonths(2)->addHour())->completed()->create();

        return $lab;
    }

    public function test_el_resumen_agrega_por_estado_tipo_y_asistencia(): void
    {
        $this->seedRange();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/reports/summary?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString())
            ->assertOk();

        $this->assertSame(3, $response->json('data.total'));
        $this->assertSame(1, $response->json('data.by_status.completed'));
        $this->assertSame(1, $response->json('data.by_status.no_show'));
        $this->assertSame(1, $response->json('data.by_type.lab'));
        $this->assertSame(2, $response->json('data.by_type.equipment'));
        // JSON no conserva la fraccion .0: se compara por valor.
        $this->assertEquals(50, $response->json('data.attendance.no_show_rate'));
        $this->assertEquals(5, $response->json('data.booked_hours'));
        $this->assertSame('Redes', $response->json('data.top_labs.0.lab_name'));
        $this->assertSame('Ana', $response->json('data.top_users.0.name'));
    }

    public function test_la_ocupacion_se_desglosa_por_dia_hora_y_dia_de_la_semana(): void
    {
        $lab = $this->seedRange();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/reports/occupancy?lab_id='.$lab->id.'&from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString())
            ->assertOk();

        $this->assertCount(3, $response->json('data.by_day'));
        $this->assertCount(24, $response->json('data.by_hour'));
        $this->assertSame(3, $response->json('data.by_hour.9.reservations'));
        $this->assertSame(3, array_sum(array_column($response->json('data.by_weekday'), 'reservations')));
    }

    public function test_la_exportacion_csv_lleva_cabecera_y_una_fila_por_reserva(): void
    {
        $this->seedRange();
        $this->actingAsAdmin();

        $response = $this->get('/api/v1/reports/export?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $lines = array_filter(explode("\n", trim($response->streamedContent())));
        $this->assertCount(4, $lines);
        $this->assertStringContainsString('id;tipo;estado', $lines[0]);
    }

    public function test_los_reportes_son_solo_para_quien_tiene_el_permiso(): void
    {
        $this->actingAsTeacher();

        $this->getJson('/api/v1/reports/summary')->assertForbidden();
        $this->getJson('/api/v1/reports/occupancy')->assertForbidden();
        $this->get('/api/v1/reports/export')->assertForbidden();
    }

    public function test_un_rango_invertido_es_un_error_de_validacion(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/reports/summary?from=2026-09-10&to=2026-09-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }
}
