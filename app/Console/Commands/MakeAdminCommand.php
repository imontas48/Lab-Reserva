<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Unica via para crear o elevar un administrador.
 *
 * POST /api/v1/register es publico y por eso ya no acepta el campo role: si lo
 * aceptase, cualquiera podria concederse permisos de administrador. Promover a
 * alguien es una accion deliberada que exige acceso al servidor.
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'lab:make-admin
                            {email : Correo del usuario a crear o promover}
                            {--name= : Nombre, solo si el usuario no existe}';

    protected $description = 'Crea un usuario administrador o promueve a admin uno existente';

    public function handle(): int
    {
        $email = $this->argument('email');

        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            $this->error('El correo no es valido.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->role === 'admin') {
                $this->info("{$email} ya es administrador.");

                return self::SUCCESS;
            }

            $previous = $user->role;
            $user->update(['role' => 'admin']);
            $this->info("{$email} promovido de {$previous} a admin.");

            return self::SUCCESS;
        }

        $name = $this->option('name');
        if (! $name) {
            $this->error('El usuario no existe. Indica --name para crearlo.');

            return self::FAILURE;
        }

        // secret() no muestra la contrasena por pantalla ni la deja en el
        // historial del shell, a diferencia de pasarla como argumento.
        $password = $this->secret('Contrasena para el nuevo administrador');
        $confirmation = $this->secret('Confirma la contrasena');

        if ($password !== $confirmation) {
            $this->error('Las contrasenas no coinciden.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(8)]]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->info("Administrador {$email} creado.");

        return self::SUCCESS;
    }
}
