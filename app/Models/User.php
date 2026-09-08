<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
        ];
    }

    /**
     * Get all reservations made by this user.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get active reservations for this user.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->where('status', 'confirmed');
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
