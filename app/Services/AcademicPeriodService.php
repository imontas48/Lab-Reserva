<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Collection;

class AcademicPeriodService
{
    public function getAll(): Collection
    {
        return AcademicPeriod::query()->orderByDesc('starts_on')->get();
    }

    public function create(array $data): AcademicPeriod
    {
        return AcademicPeriod::create($data + ['is_active' => $data['is_active'] ?? true]);
    }

    public function update(AcademicPeriod $period, array $data): AcademicPeriod
    {
        $period->update($data);

        return $period->fresh();
    }

    public function delete(AcademicPeriod $period): void
    {
        $period->delete();
    }
}
