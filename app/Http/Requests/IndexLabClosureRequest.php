<?php

namespace App\Http\Requests;

class IndexLabClosureRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['starts_at', 'ends_at'];
    }

    protected function additionalRules(): array
    {
        return [
            'lab_id' => ['sometimes', 'integer', 'exists:labs,id'],
            'include_past' => ['sometimes', 'boolean'],
        ];
    }
}
