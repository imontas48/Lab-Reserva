<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class IndexUserRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['name', 'email', 'role', 'created_at', 'no_show_count'];
    }

    protected function additionalRules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(['admin', 'teacher', 'student'])],
            'blocked' => ['sometimes', 'boolean'],
        ];
    }
}
