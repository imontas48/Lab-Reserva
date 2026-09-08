<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\Software;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Comprobaciones minimas de que la aplicacion arranca y de que el arnes de
 * pruebas (migraciones, factories, base MySQL separada) esta operativo.
 *
 * No cubren reglas de negocio: eso llega en las fases siguientes.
 */
class SmokeTest extends TestCase
{
    public function test_health_check_responde(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_el_esquema_se_migra_y_las_factories_funcionan(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_las_factories_del_dominio_construyen_el_grafo_completo(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        $software = Software::factory()->create();
        $equipment->software()->attach($software);

        $reservation = Reservation::factory()
            ->forEquipment($equipment)
            ->create();

        $this->assertSame($lab->id, $equipment->lab->id);
        $this->assertTrue($equipment->software->contains($software));
        $this->assertSame($equipment->id, $reservation->equipment->id);
        $this->assertSame('confirmed', $reservation->status);

        // Los estados que usaran las fases siguientes.
        $this->assertFalse(Equipment::factory()->nonOperational()->create()->is_operational);
        $this->assertFalse(Lab::factory()->inactive()->create()->is_active);
        $this->assertTrue(Reservation::factory()->past()->create()->end_time->isPast());
        $this->assertSame('cancelled', Reservation::factory()->cancelled()->create()->status);
    }

    public function test_login_con_credenciales_validas_devuelve_token(): void
    {
        $user = User::factory()->create([
            'email' => 'docente@lab-reserva.test',
            'password' => Hash::make('secreto-de-prueba'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'docente@lab-reserva.test',
            'password' => 'secreto-de-prueba',
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertNotEmpty($response->json('token'));
        $this->assertSame($user->id, $response->json('user.id'));
    }

    public function test_login_con_credenciales_invalidas_no_devuelve_token(): void
    {
        User::factory()->create([
            'email' => 'docente@lab-reserva.test',
            'password' => Hash::make('secreto-de-prueba'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'docente@lab-reserva.test',
            'password' => 'contrasena-incorrecta',
        ]);

        $response->assertStatus(422);
        $this->assertNull($response->json('token'));
    }
}
