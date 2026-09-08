<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Este seeder crea un administrador con una contrasena conocida y
        // publicada en el propio repositorio. Ejecutarlo en produccion seria
        // entregar una cuenta con control total. En produccion se usa
        // `php artisan lab:make-admin`, que pide la contrasena por consola.
        if (app()->isProduction()) {
            $this->command->warn('AdminUserSeeder omitido en produccion. Usa: php artisan lab:make-admin');

            return;
        }

        // Verificar si ya existe el usuario
        if (User::where('email', 'admin@lab-reserva.test')->exists()) {
            $this->command->info('El usuario admin ya existe.');

            return;
        }

        // Crear usuario administrador
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@lab-reserva.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->command->info(' Usuario administrador creado:');
        $this->command->info('   Email: admin@lab-reserva.test');
        $this->command->info('   Password: password');
    }
}
