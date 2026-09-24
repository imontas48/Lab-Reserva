<?php

namespace Tests\Feature\Api;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\Software;
use App\Models\User;
use Tests\TestCase;

/**
 * Forma de las respuestas de la API.
 *
 * Los Resources no tenian ninguna prueba, pese a ser el contrato que consume el
 * frontend. Aqui se fijan los campos que las vistas dan por hechos, para que un
 * renombrado o una eliminacion falle aqui y no en el navegador.
 */
class ResourceContractTest extends TestCase
{
    public function test_el_recurso_de_equipo_expone_el_estado_calculado(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAsAdmin();

        $this->getJson("/api/v1/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('data.identifier', $equipment->identifier)
            ->assertJsonPath('data.status.status', 'available')
            ->assertJsonPath('data.is_available', true)
            ->assertJsonStructure(['data' => ['id', 'lab_id', 'type', 'is_operational', 'status' => ['status', 'details']]]);
    }

    public function test_un_equipo_fuera_de_servicio_lo_refleja_en_su_estado(): void
    {
        $equipment = Equipment::factory()->nonOperational()->create();
        $this->actingAsAdmin();

        $this->getJson("/api/v1/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('data.status.status', 'out_of_service')
            ->assertJsonPath('data.is_available', false);
    }

    public function test_un_equipo_con_reserva_futura_aparece_como_reservado(): void
    {
        $equipment = Equipment::factory()->create();
        Reservation::factory()->forEquipment($equipment)->future()->confirmed()->create();

        $this->actingAsAdmin();

        $this->getJson("/api/v1/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('data.status.status', 'reserved');
    }

    public function test_el_recurso_de_reserva_devuelve_fechas_y_duracion(): void
    {
        $duenio = $this->actingAsStudent();
        $reserva = Reservation::factory()->forUser($duenio)->future()->create();

        $this->getJson("/api/v1/reservations/{$reserva->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.duration_minutes', 60)
            ->assertJsonStructure(['data' => ['id', 'start_time', 'end_time', 'is_active', 'is_past', 'is_future']]);
    }

    public function test_el_recurso_de_laboratorio_expone_los_campos_del_listado(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsAdmin();

        $this->getJson("/api/v1/labs/{$lab->id}")
            ->assertOk()
            ->assertJsonPath('data.name', $lab->name)
            ->assertJsonStructure(['data' => ['id', 'name', 'location', 'capacity', 'is_active']]);
    }

    public function test_el_recurso_de_software_expone_nombre_y_version(): void
    {
        $software = Software::factory()->create();
        $this->actingAsAdmin();

        $this->getJson("/api/v1/software/{$software->id}")
            ->assertOk()
            ->assertJsonPath('data.name', $software->name)
            ->assertJsonPath('data.version', $software->version);
    }

    public function test_el_dashboard_devuelve_el_identificador_del_equipo(): void
    {
        $user = User::factory()->create();
        $equipment = Equipment::factory()->create();
        Reservation::factory()->forUser($user)->forEquipment($equipment)->future()->confirmed()->create();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/dashboard/stats')->assertOk();

        // El controlador leia equipment->name, una columna que no existe, asi
        // que este campo llegaba como null para todas las entradas.
        $this->assertSame($equipment->identifier, $response->json('upcoming_reservations.0.equipment_name'));
        $this->assertNotNull($response->json('upcoming_reservations.0.lab_name'));
    }

    public function test_el_recurso_de_reserva_de_laboratorio_expone_el_laboratorio_y_el_motivo(): void
    {
        $lab = Lab::factory()->create();
        $duenio = $this->actingAsTeacher();
        $reserva = Reservation::factory()->forLab($lab, 'Examen final')->forUser($duenio)->future()->create();

        $this->getJson("/api/v1/reservations/{$reserva->id}")
            ->assertOk()
            ->assertJsonPath('data.type', 'lab')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.is_pending', true)
            ->assertJsonPath('data.purpose', 'Examen final')
            ->assertJsonPath('data.lab.name', $lab->name)
            ->assertJsonPath('data.equipment_id', null)
            ->assertJsonPath('data.reviewer', null)
            ->assertJsonStructure(['data' => ['id', 'type', 'lab_id', 'purpose', 'rejection_reason', 'reviewed_at']]);
    }

    public function test_el_dashboard_describe_una_reserva_de_laboratorio_completo(): void
    {
        $lab = Lab::factory()->create();
        $user = User::factory()->teacher()->create();
        Reservation::factory()->forLab($lab)->forUser($user)->future()->create();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/dashboard/stats')->assertOk();

        $this->assertSame($lab->name, $response->json('upcoming_reservations.0.lab_name'));
        $this->assertSame('Laboratorio completo', $response->json('upcoming_reservations.0.equipment_name'));
        $this->assertSame('lab', $response->json('upcoming_reservations.0.type'));
        $this->assertSame(1, $response->json('stats.active_reservations'));
    }

    /**
     * El frontend decide que mostrar (por ejemplo, la opcion de reservar un
     * laboratorio completo) a partir de los permisos efectivos, no del rol.
     */
    public function test_me_expone_los_permisos_efectivos(): void
    {
        $this->actingAsTeacher();

        $response = $this->getJson('/api/v1/me')->assertOk();

        $permisos = $response->json('data.permissions');
        $this->assertContains('reservations.createLab', $permisos);
        $this->assertNotContains('reservations.approve', $permisos);
        $this->assertSame('teacher', $response->json('data.role'));
    }

    public function test_el_login_devuelve_los_permisos_del_estudiante(): void
    {
        $user = User::factory()->student()->create(['password' => 'password']);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertNotContains('reservations.createLab', $response->json('user.permissions'));
        $this->assertContains('reservations.create', $response->json('user.permissions'));
        $this->assertSame('student', $response->json('user.role'));
    }

    public function test_el_dashboard_cuenta_solo_las_reservas_del_usuario(): void
    {
        $user = User::factory()->create();
        $otro = User::factory()->create();

        Reservation::factory()->forUser($user)->future()->confirmed()->create();
        Reservation::factory()->forUser($otro)->future()->confirmed()->create();

        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('stats.active_reservations', 1);
    }
}
