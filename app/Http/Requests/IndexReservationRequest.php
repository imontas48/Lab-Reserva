<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class IndexReservationRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['start_time', 'end_time', 'status', 'created_at'];
    }

    protected function additionalRules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['confirmed', 'cancelled', 'completed'])],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'equipment_id' => ['sometimes', 'integer', 'exists:equipment,id'],
            'lab_id' => ['sometimes', 'integer', 'exists:labs,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
        ];
    }
}
