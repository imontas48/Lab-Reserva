<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\EquipmentIncident;
use App\Models\GroupRoleAssignment;
use App\Models\Lab;
use App\Models\LabClosure;
use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Software;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Escribe y consulta el registro de actividades.
 *
 * Se escribe de forma sincrona y dentro de la transaccion de la accion: si la
 * accion hace rollback, su registro tambien, y nunca queda constancia de algo
 * que no llego a pasar.
 */
class ActivityLogService
{
    public const REDACTED = '[oculto]';

    /**
     * Atributos que nunca se guardan en claro. Queda constancia de que
     * cambiaron, pero no del valor: el registro es para siempre.
     *
     * @var array<int, string>
     */
    private const SECRET_ATTRIBUTES = ['password', 'remember_token', 'check_in_code'];

    /**
     * Atributos de mantenimiento que no dicen nada de quien hizo que.
     * reminder_sent_at lo escribe el envio automatico de recordatorios.
     *
     * @var array<int, string>
     */
    private const IGNORED_ATTRIBUTES = ['created_at', 'updated_at', 'reminder_sent_at'];

    /**
     * @var array<string, string>
     */
    private const SUBJECT_NOUNS = [
        'lab' => 'el laboratorio',
        'equipment' => 'el equipo',
        'software' => 'el software',
        'reservation' => 'la reserva',
        'user' => 'el usuario',
        'role' => 'el rol',
        'permission' => 'el permiso',
        'user_role' => 'la asignación de rol',
        'group_role_assignment' => 'la regla de grupo',
        'permission_override' => 'la excepción de permiso',
        'lab_closure' => 'el cierre',
        'academic_period' => 'el periodo académico',
        'incident' => 'la incidencia',
    ];

    /**
     * @var array<string, string>
     */
    private const MODEL_EVENT_VERBS = [
        'created' => 'Creó',
        'updated' => 'Modificó',
        'deleted' => 'Eliminó',
        'restored' => 'Restauró',
    ];

    /**
     * Registra una accion.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $event,
        ?Model $subject,
        string $description,
        array $properties = [],
        ?User $actor = null,
        ?string $subjectType = null,
    ): ActivityLog {
        $request = request();
        $isWeb = $request->route() !== null;
        $actor ??= $this->currentUser();

        return ActivityLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'event' => $event,
            'subject_type' => $subjectType ?? ($subject ? ActivityLog::subjectTypeFor($subject) : null),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? mb_substr($this->labelFor($subject), 0, 255) : null,
            'description' => mb_substr($description, 0, 500),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $isWeb ? $request->ip() : null,
            'user_agent' => $isWeb && $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
            'source' => $isWeb ? ActivityLog::SOURCE_WEB : ActivityLog::SOURCE_CONSOLE,
        ]);
    }

    /**
     * Registra un evento de modelo (created, updated, deleted) con su diff.
     *
     * Devuelve null cuando un update solo toco atributos de mantenimiento.
     */
    public function recordModelEvent(string $event, Model $model): ?ActivityLog
    {
        $type = ActivityLog::subjectTypeFor($model);

        if ($type === null) {
            return null;
        }

        if ($event === 'updated') {
            $changes = Arr::except($model->getChanges(), self::IGNORED_ATTRIBUTES);

            if ($changes === []) {
                return null;
            }

            // restore() de SoftDeletes llega como un update de deleted_at.
            if ($this->isRestore($model, $changes)) {
                $event = 'restored';
            }

            $properties = [
                'old' => $this->redact(Arr::only($model->getRawOriginal(), array_keys($changes))),
                'new' => $this->redact($changes),
            ];
        } else {
            $properties = ['attributes' => $this->redact(Arr::except($model->getAttributes(), self::IGNORED_ATTRIBUTES))];
        }

        $description = self::MODEL_EVENT_VERBS[$event].' '.self::SUBJECT_NOUNS[$type].' '.$this->labelFor($model);

        if ($event === 'updated') {
            $description .= ': '.implode(', ', array_keys($properties['new']));
        }

        return $this->record($event, $model, $description, $properties, $this->selfActorFor($model));
    }

    /**
     * Listado paginado y filtrado, del mas reciente al mas antiguo.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('actor:id,name,email,deleted_at');

        foreach (['actor_id', 'event', 'subject_type', 'subject_id', 'source'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (isset($filters['search'])) {
            $search = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', $search)
                    ->orWhere('subject_label', 'like', $search)
                    ->orWhere('actor_name', 'like', $search)
                    ->orWhere('actor_email', 'like', $search);
            });
        }

        // Por id y no por created_at: dos acciones en el mismo segundo
        // conservan el orden en que ocurrieron.
        $direction = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy('id', $direction)->paginate($perPage);
    }

    /**
     * Texto legible del sujeto, copiado al registro en el momento.
     */
    public function labelFor(Model $model): string
    {
        return match (true) {
            $model instanceof Lab, $model instanceof AcademicPeriod => "«{$model->name}»",
            $model instanceof Software => trim("«{$model->name}» {$model->version}"),
            $model instanceof Equipment => "«{$model->identifier}»",
            $model instanceof User => "{$model->name} <{$model->email}>",
            $model instanceof Role => "«{$model->display_name}»",
            $model instanceof Permission => "{$model->subject}.{$model->action}",
            $model instanceof UserRole => "#{$model->id} ({$model->role?->display_name} → {$model->user?->email})",
            $model instanceof PermissionOverride => "#{$model->id} ({$model->permission?->subject}.{$model->permission?->action} → {$model->user?->email})",
            $model instanceof GroupRoleAssignment => "#{$model->id} ({$model->group_type}={$model->group_value} → {$model->role?->display_name})",
            $model instanceof EquipmentIncident, $model instanceof LabClosure, $model instanceof Reservation => "#{$model->id}",
            default => '#'.$model->getKey(),
        };
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Sin sesion, en el registro publico y en el reset de contrasena, quien
     * actua es el propio usuario. Desde consola no: alli el actor es el
     * sistema (lab:invite-user, lab:make-admin).
     */
    private function selfActorFor(Model $model): ?User
    {
        if ($this->currentUser() !== null || request()->route() === null) {
            return null;
        }

        return $model instanceof User ? $model : null;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function isRestore(Model $model, array $changes): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && array_key_exists('deleted_at', $changes)
            && $changes['deleted_at'] === null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function redact(array $attributes): array
    {
        foreach (self::SECRET_ATTRIBUTES as $secret) {
            if (array_key_exists($secret, $attributes) && $attributes[$secret] !== null) {
                $attributes[$secret] = self::REDACTED;
            }
        }

        return $attributes;
    }
}
