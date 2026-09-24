<?php

namespace App\Support;

use App\Models\User;

/**
 * Cuotas y ventanas de reserva que aplican a un usuario.
 *
 * Es la unica lectura de config('lab-reserva.reservations'): los FormRequests
 * la usan para dar un 422 con el campo senalado y ReservationService para
 * hacer cumplir la regla dentro de la transaccion. Se resuelve por el rol
 * base (users.role) porque es politica de rol, no una capacidad concedible
 * desde el RBAC.
 */
class ReservationLimits
{
    public const DEFAULT_ROLE = 'student';

    /**
     * @return array{max_active: int|null, max_advance_days: int|null, max_hours: int|null, min_minutes: int}
     */
    public static function forUser(User $user): array
    {
        return self::forRole($user->role);
    }

    /**
     * @return array{max_active: int|null, max_advance_days: int|null, max_hours: int|null, min_minutes: int}
     */
    public static function forRole(?string $role): array
    {
        $roles = config('lab-reserva.reservations.roles', []);
        $spec = $roles[$role] ?? $roles[self::DEFAULT_ROLE] ?? [];

        return [
            'max_active' => self::nullableInt($spec['max_active'] ?? null),
            'max_advance_days' => self::nullableInt($spec['max_advance_days'] ?? null),
            'max_hours' => self::nullableInt($spec['max_hours'] ?? null),
            'min_minutes' => (int) config('lab-reserva.reservations.min_minutes', 30),
        ];
    }

    /**
     * Una variable de entorno vacia llega como '' y debe significar "sin
     * limite", no "cero".
     */
    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
