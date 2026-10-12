<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->subject_label,
            'properties' => $this->properties,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'source' => $this->source,
            'created_at' => $this->created_at?->toIso8601String(),
            // La copia del momento manda; la relacion solo dice si la cuenta
            // sigue existiendo.
            'actor' => $this->actor_id || $this->actor_name ? [
                'id' => $this->actor_id,
                'name' => $this->actor_name,
                'email' => $this->actor_email,
                'deleted' => $this->relationLoaded('actor') && ($this->actor === null || $this->actor->trashed()),
            ] : null,
        ];
    }
}
