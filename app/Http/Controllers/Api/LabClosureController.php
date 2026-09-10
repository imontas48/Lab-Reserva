<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexLabClosureRequest;
use App\Http\Requests\StoreLabClosureRequest;
use App\Http\Requests\UpdateLabClosureRequest;
use App\Http\Resources\LabClosureResource;
use App\Models\LabClosure;
use App\Services\LabScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabClosureController extends Controller
{
    public function __construct(
        private readonly LabScheduleService $schedule
    ) {}

    public function index(IndexLabClosureRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LabClosure::class);

        return LabClosureResource::collection(
            $this->schedule->getClosures($request->filters(), $request->perPage() ?? 15)
        );
    }

    public function store(StoreLabClosureRequest $request): JsonResponse
    {
        $closure = $this->schedule->createClosure($request->validated(), $request->user());

        return (new LabClosureResource($closure))->response()->setStatusCode(201);
    }

    public function show(LabClosure $closure): LabClosureResource
    {
        $this->authorize('view', $closure);

        return new LabClosureResource($closure->load(['lab', 'creator']));
    }

    public function update(UpdateLabClosureRequest $request, LabClosure $closure): LabClosureResource
    {
        return new LabClosureResource($this->schedule->updateClosure($closure, $request->validated()));
    }

    public function destroy(LabClosure $closure): JsonResponse
    {
        $this->authorize('delete', $closure);

        $this->schedule->deleteClosure($closure);

        return response()->json(['message' => 'Cierre eliminado exitosamente']);
    }
}
