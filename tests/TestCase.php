<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Todas las pruebas parten de un esquema limpio.
     *
     * Se aplica aqui y no por caso para que ninguna prueba nueva pueda olvidarlo
     * y acabe dependiendo del estado que dejo la anterior.
     */
    use RefreshDatabase;

    /**
     * Autentica a un usuario recien creado con el rol indicado y lo devuelve.
     *
     * Usa Sanctum::actingAs porque la API se consume con tokens Bearer; el guard
     * de sesion web no interviene en ninguna ruta de routes/api.php.
     */
    protected function actingAsRole(string $role): User
    {
        $user = User::factory()->state(['role' => $role])->create();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsAdmin(): User
    {
        return $this->actingAsRole('admin');
    }

    protected function actingAsTeacher(): User
    {
        return $this->actingAsRole('teacher');
    }

    protected function actingAsStudent(): User
    {
        return $this->actingAsRole('student');
    }
}
