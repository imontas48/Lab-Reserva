<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Support\InvitedUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount([
                'reservations',
                'activeReservations',
            ]);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['blocked'])) {
            $query->where('reservation_blocked_until', '>', now());
        }

        return $query
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_order'] ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Da de alta a un usuario con contrasena temporal.
     *
     * Es la unica via por la que una cuenta nace con must_change_password:
     * el registro publico no la marca porque ahi la contrasena la elige el
     * propio usuario. Si el administrador no indica contrasena se genera una
     * de 12 caracteres sin simbolos, facil de dictar y de escribir.
     *
     * @param  array{name: string, email: string, role: string, password?: ?string}  $data
     */
    public function invite(array $data): InvitedUser
    {
        $temporaryPassword = $data['password'] ?? Str::password(12, symbols: false);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => $data['role'],
        ]);

        $user->forceFill(['must_change_password' => true])->save();

        return new InvitedUser($user->fresh(), $temporaryPassword);
    }

    /**
     * Actualiza nombre, correo o rol base.
     *
     * El rol base gobierna la administracion del RBAC y la recuperacion del
     * sistema, asi que se protege a si mismo: nadie se degrada a si mismo y
     * el ultimo administrador no puede dejar de serlo.
     *
     * @throws BusinessRuleException
     */
    public function update(User $target, array $data, User $actor): User
    {
        if (isset($data['role']) && $data['role'] !== $target->role) {
            if ($target->id === $actor->id) {
                throw new BusinessRuleException('No puedes cambiar tu propio rol.');
            }

            if ($target->isAdmin() && $this->isLastAdmin($target)) {
                throw new BusinessRuleException('No se puede degradar al único administrador del sistema.');
            }
        }

        $target->update($data);

        return $target->fresh();
    }

    /**
     * Baja logica. La FK de reservas esta en RESTRICT y el modelo usa
     * SoftDeletes, asi que el historial se conserva.
     *
     * @throws BusinessRuleException
     */
    public function delete(User $target, User $actor): void
    {
        if ($target->id === $actor->id) {
            throw new BusinessRuleException('No puedes darte de baja a ti mismo.');
        }

        if ($target->isAdmin() && $this->isLastAdmin($target)) {
            throw new BusinessRuleException('No se puede dar de baja al único administrador del sistema.');
        }

        $target->tokens()->delete();
        $target->delete();
    }

    /**
     * Levanta el bloqueo por inasistencias y reinicia el contador.
     */
    public function unblock(User $target): User
    {
        $target->forceFill([
            'reservation_blocked_until' => null,
            'no_show_count' => 0,
        ])->save();

        return $target->fresh();
    }

    private function isLastAdmin(User $target): bool
    {
        return User::query()->admins()->whereKeyNot($target->id)->doesntExist();
    }
}
