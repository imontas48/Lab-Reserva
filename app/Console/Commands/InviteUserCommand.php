<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Da de alta a un usuario con contrasena temporal desde la consola.
 *
 * Es el mismo camino que usa el panel de administracion (UserService::invite):
 * sirve para el arranque, cuando todavia no existe ningun administrador que
 * pueda entrar al panel, y para altas por script. La contrasena se genera y
 * se imprime UNA vez; el usuario tendra que cambiarla al iniciar sesion.
 */
class InviteUserCommand extends Command
{
    protected $signature = 'lab:invite-user
                            {email : Correo del nuevo usuario}
                            {--name= : Nombre completo}
                            {--role=student : admin, teacher o student}';

    protected $description = 'Crea un usuario con contrasena temporal que debera cambiar en su primer inicio de sesion';

    public function handle(UserService $users): int
    {
        $data = [
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'role' => $this->option('role'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $invitation = $users->invite($data);

        $this->info("Usuario {$invitation->user->email} creado con rol {$invitation->user->role}.");
        $this->line("Contrasena temporal: {$invitation->temporaryPassword}");
        $this->warn('Se pedira cambiarla en el primer inicio de sesion. No se vuelve a mostrar.');

        return self::SUCCESS;
    }
}
