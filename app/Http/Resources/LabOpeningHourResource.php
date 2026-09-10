<?php

namespace App\Http\Resources;

use App\Models\LabOpeningHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LabOpeningHour
 */
class LabOpeningHourResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'weekday' => $this->weekday,
            'weekday_name' => $this->weekdayName(),
            'opens_at' => substr((string) $this->opens_at, 0, 5),
            'closes_at' => substr((string) $this->closes_at, 0, 5),
        ];
    }
}
