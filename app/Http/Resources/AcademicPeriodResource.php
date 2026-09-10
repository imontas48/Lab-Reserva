<?php

namespace App\Http\Resources;

use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademicPeriod
 */
class AcademicPeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'is_active' => (bool) $this->is_active,
            'is_current' => $this->is_active
                && $this->starts_on?->lte(now()->startOfDay())
                && $this->ends_on?->gte(now()->startOfDay()),
        ];
    }
}
