<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PermissionService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property int $no_show_count
 * @property bool $must_change_password
 * @property Carbon|null $reservation_blocked_until
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'reservation_blocked_until' => 'datetime',
        ];
    }

    /**
     * Todavia usa la contrasena temporal que le asigno un administrador:
     * la API le exige cambiarla antes de hacer nada mas.
     */
    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }

    /**
     * Bloqueado para reservar por inasistencias reiteradas.
     */
    public function isBlockedFromReserving(): bool
    {
        return $this->reservation_blocked_until !== null
            && $this->reservation_blocked_until->isFuture();
    }

    /**
     * Get all reservations made by this user.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Reservas que ocupan una franja todavia no terminada: pendientes o
     * confirmadas con fin en el futuro. Es la base de la cuota por rol.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)
            ->whereIn('status', Reservation::BLOCKING_STATUSES)
            ->where('end_time', '>=', now());
    }

    /**
     * Scope to get users by role.
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope to get teacher users.
     */
    public function scopeTeachers($query)
    {
        return $query->where('role', 'teacher');
    }

    /**
     * Scope to get student users.
     */
    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    /**
     * Check if the user is an admin.
     */
    /**
     * ¿Tiene el usuario este permiso efectivo?
     *
     * Resuelve roles por grupo, roles individuales vigentes y sobreescrituras,
     * con caché. Es lo que consultan las policies desde que el RBAC gobierna de
     * verdad el control de acceso: antes las 41 decisiones se tomaban con
     * isAdmin() y las cinco tablas del RBAC no influían en nada.
     */
    public function hasPermission(string $subject, string $action): bool
    {
        return app(PermissionService::class)->userHasPermission($this, $subject, $action);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if the user has admin or teacher privileges.
     */
    public function hasElevatedPrivileges(): bool
    {
        return in_array($this->role, ['admin', 'teacher']);
    }

    // =========================================================================
    // RELACIONES RBAC
    // =========================================================================

    /**
     * Asignaciones individuales de roles de este usuario.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Sobreescrituras de permisos de este usuario.
     */
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(PermissionOverride::class);
    }
}
