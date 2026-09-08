<?php

namespace App\Http\Requests;

class IndexEquipmentRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['identifier', 'type', 'is_operational', 'lab_id', 'created_at'];
    }

    protected function additionalRules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'lab_id' => ['sometimes', 'integer', 'exists:labs,id'],
            'type' => ['sometimes', 'string', 'max:100'],
            'is_operational' => ['sometimes', 'boolean'],
        ];
    }
}
