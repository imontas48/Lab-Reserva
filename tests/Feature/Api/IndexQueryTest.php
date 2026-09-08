<?php

namespace Tests\Feature\Api;

use App\Models\Lab;
use Tests\TestCase;

/**
 * Los controladores pasaban sort_by, sort_order y per_page crudos hasta
 * orderBy() y paginate(): una columna inexistente daba 500 (y era inyeccion de
 * identificador), un orden invalido daba 500, y per_page sin tope permitia
 * pedir la tabla entera.
 *
 * Ademas LabController::index llamaba a getAllLabs() sin argumentos, de modo
 * que la busqueda, el filtro por estado y la paginacion que el servicio ya
 * implementaba no se aplicaban nunca.
 */
class IndexQueryTest extends TestCase
{
    public function test_ordenar_por_una_columna_no_permitida_devuelve_422(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/labs?sort_by=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');
    }

    public function test_una_direccion_de_orden_invalida_devuelve_422(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/labs?sort_order=; DROP TABLE labs')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_order');
    }

    public function test_per_page_por_encima_del_tope_devuelve_422(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/labs?per_page=999999')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_per_page_no_numerico_devuelve_422_y_no_un_typeerror(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/labs?per_page=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_el_filtro_de_busqueda_de_laboratorios_ahora_se_aplica(): void
    {
        Lab::factory()->create(['name' => 'Laboratorio de Redes']);
        Lab::factory()->create(['name' => 'Laboratorio de Quimica']);

        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/labs?search=Redes')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Laboratorio de Redes', $response->json('data.0.name'));
    }

    public function test_el_filtro_de_estado_activo_ahora_se_aplica(): void
    {
        Lab::factory()->count(2)->create(['is_active' => true]);
        Lab::factory()->inactive()->create();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/labs?is_active=0')->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_la_paginacion_de_laboratorios_ahora_funciona(): void
    {
        Lab::factory()->count(7)->create();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/labs?per_page=3')->assertOk();

        $this->assertCount(3, $response->json('data'));
        $this->assertSame(7, $response->json('meta.total'));
    }

    public function test_una_ordenacion_permitida_funciona(): void
    {
        Lab::factory()->create(['name' => 'Zeta']);
        Lab::factory()->create(['name' => 'Alfa']);

        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/labs?sort_by=name&sort_order=asc')->assertOk();

        $this->assertSame('Alfa', $response->json('data.0.name'));
    }

    public function test_un_rol_inexistente_en_by_role_no_llega_al_controlador(): void
    {
        $this->actingAsAdmin();

        // La restriccion de ruta lo rechaza: un rol que no existe es una ruta
        // que no existe.
        $this->getJson('/api/v1/reservations/by-role/hacker')->assertNotFound();
    }
}
