<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Una entrada del registro de actividades.
 *
 * Es inmutable: una vez escrita no se edita ni se borra desde la aplicacion.
 * La guardia de booted() lo hace cumplir tambien desde tinker o desde codigo
 * futuro, no solo desde la API, que de por si solo expone lecturas.
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property string $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $subject_label
 * @property string $description
 * @property array<string, mixed>|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $source
 * @property Carbon $created_at
 * @property-read User|null $actor
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const SOURCE_WEB = 'web';

    public const SOURCE_CONSOLE = 'console';

    /**
     * @var array<int, string>
     */
    public const EVENTS = [
        'created',
        'updated',
        'deleted',
        'restored',
        'login',
        'login_failed',
        'logout',
        'permissions_synced',
        'software_synced',
        'opening_hours_synced',
        'layout_updated',
        'reservations_auto_completed',
        'reservations_auto_expired',
        'report_exported',
    ];

    /**
     * Clave corta del sujeto => modelo. Se guarda la clave y no el nombre de
     * la clase para que renombrar o mover un modelo no deje el historial
     * apuntando a una clase que ya no existe.
     *
     * @var array<string, class-string<Model>>
     */
    public const SUBJECTS = [
        'lab' => Lab::class,
        'equipment' => Equipment::class,
        'software' => Software::class,
        'reservation' => Reservation::class,
        'user' => User::class,
        'role' => Role::class,
        'permission' => Permission::class,
        'user_role' => UserRole::class,
        'group_role_assignment' => GroupRoleAssignment::class,
        'permission_override' => PermissionOverride::class,
        'lab_closure' => LabClosure::class,
        'academic_period' => AcademicPeriod::class,
        'incident' => EquipmentIncident::class,
    ];

    /**
     * Sujetos sin modelo propio (la exportacion de un reporte).
     *
     * @var array<int, string>
     */
    public const VIRTUAL_SUBJECTS = ['report'];

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_email',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('El registro de actividades no se puede modificar.');
        });

        static::deleting(static function (): never {
            throw new LogicException('El registro de actividades no se puede borrar.');
        });
    }

    /**
     * @return array<int, string>
     */
    public static function subjectTypes(): array
    {
        return [...array_keys(self::SUBJECTS), ...self::VIRTUAL_SUBJECTS];
    }

    public static function subjectTypeFor(Model $model): ?string
    {
        $type = array_search($model::class, self::SUBJECTS, true);

        return $type === false ? null : $type;
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
