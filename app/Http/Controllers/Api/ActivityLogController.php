<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Solo lectura: el registro de actividades no tiene store, update ni destroy.
 */
class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activity
    ) {}

    /**
     * GET /api/v1/activity-logs
     */
    public function index(IndexActivityLogRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ActivityLog::class);

        return ActivityLogResource::collection(
            $this->activity->getAll($request->filters(), $request->perPage() ?? 25)
        );
    }
}
