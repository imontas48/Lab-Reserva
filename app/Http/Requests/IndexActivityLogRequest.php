<?php

namespace App\Http\Requests;

use App\Models\ActivityLog;
use Illuminate\Validation\Rule;

class IndexActivityLogRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['created_at'];
    }

    protected function additionalRules(): array
    {
        return [
            'actor_id' => ['sometimes', 'integer', 'exists:users,id'],
            'event' => ['sometimes', 'string', Rule::in(ActivityLog::EVENTS)],
            'subject_type' => ['sometimes', 'string', Rule::in(ActivityLog::subjectTypes())],
            'subject_id' => ['sometimes', 'integer', 'min:1'],
            'source' => ['sometimes', 'string', Rule::in([ActivityLog::SOURCE_WEB, ActivityLog::SOURCE_CONSOLE])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'search' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
