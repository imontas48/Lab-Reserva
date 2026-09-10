<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncOpeningHoursRequest;
use App\Http\Resources\LabClosureResource;
use App\Http\Resources\LabOpeningHourResource;
use App\Models\Lab;
use App\Models\LabClosure;
use App\Services\LabScheduleService;
use Illuminate\Http\JsonResponse;

class LabScheduleController extends Controller
{
    public function __construct(
        private readonly LabScheduleService $schedule
    ) {}

    /**
     * Horario de apertura y proximos cierres de un laboratorio.
     * GET /api/v1/labs/{lab}/schedule
     */
    public function show(Lab $lab): JsonResponse
    {
        $this->authorize('view', $lab);

        $closures = LabClosure::query()
            ->with('lab')
            ->affectingLab($lab->id)
            ->upcoming()
            ->orderBy('starts_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => [
                'lab_id' => $lab->id,
                'opening_hours' => LabOpeningHourResource::collection($lab->openingHours()->get())->resolve(),
                'closures' => LabClosureResource::collection($closures)->resolve(),
            ],
        ]);
    }

    /**
     * Sustituye el horario de apertura completo.
     * PUT /api/v1/labs/{lab}/opening-hours
     */
    public function syncOpeningHours(SyncOpeningHoursRequest $request, Lab $lab): JsonResponse
    {
        $hours = $this->schedule->syncOpeningHours($lab, $request->validated('hours'));

        return response()->json([
            'data' => LabOpeningHourResource::collection($hours)->resolve(),
        ]);
    }
}
