<?php

namespace App\Observers;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * Lleva al registro de actividades toda escritura de los modelos auditados.
 *
 * El restore() de SoftDeletes no necesita metodo propio: guarda con save() y
 * llega aqui como un updated de deleted_at, que el servicio reconoce.
 */
class ActivityObserver
{
    public function __construct(
        private readonly ActivityLogService $activity
    ) {}

    public function created(Model $model): void
    {
        $this->activity->recordModelEvent('created', $model);
    }

    public function updated(Model $model): void
    {
        $this->activity->recordModelEvent('updated', $model);
    }

    public function deleted(Model $model): void
    {
        $this->activity->recordModelEvent('deleted', $model);
    }
}
