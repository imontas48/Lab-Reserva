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
            // Equipos que tienen instalado un software concreto.
            'software_id' => ['sometimes', 'integer', 'exists:software,id'],
            // Equipos libres en una franja (ambos campos juntos).
            // Sin 'sometimes': required_with debe evaluarse aunque el campo falte.
            'available_from' => ['nullable', 'required_with:available_to', 'date'],
            'available_to' => ['nullable', 'required_with:available_from', 'date', 'after:available_from'],
        ];
    }
}
