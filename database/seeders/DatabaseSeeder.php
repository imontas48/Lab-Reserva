<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * RbacSeeder va primero y no es opcional: sin el, las cinco tablas del
     * control de acceso quedan vacias y ningun usuario tiene permiso para nada.
     * DatabaseSeeder no invocaba antes a ningun otro seeder, ni siquiera al de
     * administrador.
     */
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
