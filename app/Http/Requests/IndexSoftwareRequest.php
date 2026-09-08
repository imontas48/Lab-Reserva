<?php

namespace App\Http\Requests;

class IndexSoftwareRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['name', 'version', 'created_at'];
    }

    protected function additionalRules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
