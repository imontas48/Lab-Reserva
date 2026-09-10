<?php

namespace Tests\Feature\Security;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use Tests\TestCase;

/**
 * GET /api/v1/equipment/{id}/reservations no tenia ninguna autorizacion y el
 * Resource exponia nombre y correo del dueño de cada reserva. Bastaba con
 * recorrer los identificadores de equipo para extraer el directorio completo de
 * la institucion. ReservationPolicy::viewAny restringe el listado general a
 * administradores, pero esta ruta lateral lo eludia.
 */
class ReservationPrivacyTest extends TestCase
{
    public function test_un_tercero_ve_la_ocupacion_pero_no_la_identidad(): void
    {
        $duenio = User::factory()->create(['name' => 'Ana Duenia', 'email' => 'ana@lab-reserva.test']);
        $equipment = Equipment::factory()->create();
        Reservation::factory()->forEquipment($equipment)->forUser($duenio)->create();

        $this->actingAsStudent();

        $response = $this->getJson("/api/v1/equipment/{$equipment->id}/reservations");

        $response->assertOk();
        // Sigue viendo que la franja esta ocupada: eso es lo que necesita.
        $response->assertJsonPath('data.0.equipment_id', $equipment->id);
        // Pero no quien la ocupa.
        $response->assertJsonMissing(['email' => 'ana@lab-reserva.test']);
        $response->assertJsonMissing(['name' => 'Ana Duenia']);
    }

    public function test_el_dueño_si_ve_sus_propios_datos(): void
    {
        $equipment = Equipment::factory()->create();
        $duenio = $this->actingAsStudent();
        Reservation::factory()->forEquipment($equipment)->forUser($duenio)->create();

        $this->getJson("/api/v1/equipment/{$equipment->id}/reservations")
            ->assertOk()
            ->assertJsonPath('data.0.user.email', $duenio->email);
    }

    public function test_el_administrador_ve_la_identidad_de_cualquiera(): void
    {
        $duenio = User::factory()->create(['email' => 'ana@lab-reserva.test']);
        $equipment = Equipment::factory()->create();
        Reservation::factory()->forEquipment($equipment)->forUser($duenio)->create();

        $this->actingAsAdmin();

        $this->getJson("/api/v1/equipment/{$equipment->id}/reservations")
            ->assertOk()
            ->assertJsonPath('data.0.user.email', 'ana@lab-reserva.test');
    }

    /**
     * La ocupacion de un equipo incluye ahora las clases de su laboratorio, y
     * la ocupacion del laboratorio esta abierta a cualquier autenticado. Ni
     * una ni otra pueden filtrar quien dio la clase.
     */
    public function test_las_clases_del_laboratorio_tampoco_revelan_al_profesor(): void
    {
        $profesor = User::factory()->teacher()->create(['name' => 'Luis Docente', 'email' => 'luis@lab-reserva.test']);
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()->forLab($lab)->forUser($profesor)->confirmed()->create();

        $this->actingAsStudent();

        foreach (["/api/v1/equipment/{$equipment->id}/reservations", "/api/v1/labs/{$lab->id}/reservations"] as $url) {
            $response = $this->getJson($url)->assertOk();

            $response->assertJsonPath('data.0.type', 'lab');
            $response->assertJsonMissing(['email' => 'luis@lab-reserva.test']);
            $response->assertJsonMissing(['name' => 'Luis Docente']);
        }
    }

    public function test_el_listado_general_sigue_siendo_solo_de_administradores(): void
    {
        $this->actingAsStudent();

        $this->getJson('/api/v1/reservations')->assertForbidden();
    }
}
