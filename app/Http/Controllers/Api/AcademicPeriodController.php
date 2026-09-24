<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicPeriodRequest;
use App\Http\Requests\UpdateAcademicPeriodRequest;
use App\Http\Resources\AcademicPeriodResource;
use App\Models\AcademicPeriod;
use App\Services\AcademicPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicPeriodController extends Controller
{
    public function __construct(
        private readonly AcademicPeriodService $periods
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AcademicPeriod::class);

        return AcademicPeriodResource::collection($this->periods->getAll());
    }

    public function store(StoreAcademicPeriodRequest $request): JsonResponse
    {
        $period = $this->periods->create($request->validated());

        return (new AcademicPeriodResource($period))->response()->setStatusCode(201);
    }

    public function show(AcademicPeriod $academicPeriod): AcademicPeriodResource
    {
        $this->authorize('view', $academicPeriod);

        return new AcademicPeriodResource($academicPeriod);
    }

    public function update(UpdateAcademicPeriodRequest $request, AcademicPeriod $academicPeriod): AcademicPeriodResource
    {
        return new AcademicPeriodResource($this->periods->update($academicPeriod, $request->validated()));
    }

    public function destroy(AcademicPeriod $academicPeriod): JsonResponse
    {
        $this->authorize('delete', $academicPeriod);

        $this->periods->delete($academicPeriod);

        return response()->json(['message' => 'Periodo académico eliminado exitosamente']);
    }
}
