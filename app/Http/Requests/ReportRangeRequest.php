<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Rango de fechas de un reporte: por defecto los ultimos 30 dias, como
 * maximo un año.
 */
class ReportRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('reports', 'view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'lab_id' => ['sometimes', 'nullable', 'integer', 'exists:labs,id'],
        ];
    }

    public function from(): Carbon
    {
        return Carbon::parse($this->validated('from', now()->subDays(30)->toDateString()))->startOfDay();
    }

    public function to(): Carbon
    {
        $to = Carbon::parse($this->validated('to', now()->toDateString()))->endOfDay();

        return $to->min($this->from()->copy()->addYear());
    }

    public function labId(): ?int
    {
        $labId = $this->validated('lab_id');

        return $labId === null ? null : (int) $labId;
    }
}
