<?php

namespace App\Http\Resources;

use App\Models\LabClosure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LabClosure
 */
class LabClosureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lab_id' => $this->lab_id,
            'is_global' => $this->isGlobal(),
            'lab_name' => $this->lab_id === null ? 'Todos los laboratorios' : $this->whenLoaded('lab', fn () => $this->lab?->name),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'reason' => $this->reason,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
