<?php

namespace App\Http\Requests;

use App\Models\EquipmentIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('incident'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(EquipmentIncident::STATUSES)],
            'resolution' => [
                Rule::requiredIf(fn () => $this->input('status') === EquipmentIncident::STATUS_RESOLVED),
                'nullable', 'string', 'max:2000',
            ],
            'equipment_operational' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resolution.required' => 'Indica cómo se resolvió la incidencia.',
        ];
    }
}
