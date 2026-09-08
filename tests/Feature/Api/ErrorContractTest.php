<?php

namespace Tests\Feature\Api;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use Tests\TestCase;

/**
 * withExceptions() estaba vacio, asi que toda regla de negocio violada salia
 * como HTTP 500: el frontend no podia distinguir "no puedes hacer eso" de
 * "el servidor se cayo", el mensaje en espanol se perdia con APP_DEBUG=false y
 * se disparaban alertas de error falsas.
 */
class ErrorContractTest extends TestCase
{
    public function test_borrar_un_laboratorio_con_equipos_devuelve_422_y_no_500(): void
    {
        $lab = Lab::factory()->create();
        Equipment::factory()->inLab($lab)->create();

        $this->actingAsAdmin();

        $response = $this->deleteJson("/api/v1/labs/{$lab->id}");

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseHas('labs', ['id' => $lab->id]);
    }

    public function test_borrar_un_equipo_con_reservas_activas_devuelve_422(): void
    {
        $equipment = Equipment::factory()->create();
        Reservation::factory()->forEquipment($equipment)->future()->confirmed()->create();

        $this->actingAsAdmin();

        $this->deleteJson("/api/v1/equipment/{$equipment->id}")
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_un_recurso_inexistente_devuelve_404_json(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/labs/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_sin_autenticar_devuelve_401_json_y_no_una_redireccion(): void
    {
        $this->getJson('/api/v1/labs')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_una_ruta_de_api_inexistente_devuelve_404_json(): void
    {
        $this->getJson('/api/v1/no-existe')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_el_comodin_de_la_spa_sigue_sirviendo_las_rutas_del_frontend(): void
    {
        // Regresion del regex que excluye api/ del comodin: solo debe apartar
        // el prefijo exacto, no cualquier ruta que empiece por esas letras.
        $this->get('/dashboard')->assertOk();
        $this->get('/apixample')->assertOk();
    }
}
