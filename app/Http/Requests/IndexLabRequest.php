<?php

namespace App\Http\Requests;

class IndexLabRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['name', 'location', 'capacity', 'is_active', 'created_at'];
    }

    protected function additionalRules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
