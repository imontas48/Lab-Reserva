<?php

namespace App\Http\Requests;

use App\Models\EquipmentIncident;
use Illuminate\Validation\Rule;

class IndexIncidentRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['created_at', 'severity', 'status'];
    }

    protected function additionalRules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(EquipmentIncident::STATUSES)],
            'severity' => ['sometimes', Rule::in(EquipmentIncident::SEVERITIES)],
            'lab_id' => ['sometimes', 'integer', 'exists:labs,id'],
            'equipment_id' => ['sometimes', 'integer', 'exists:equipment,id'],
            'include_resolved' => ['sometimes', 'boolean'],
        ];
    }
}
