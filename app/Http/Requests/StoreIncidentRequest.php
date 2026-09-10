<?php

namespace App\Http\Requests;

use App\Models\EquipmentIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EquipmentIncident::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'severity' => ['sometimes', Rule::in(EquipmentIncident::SEVERITIES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Describe el problema.',
            'description.min' => 'Describe el problema con al menos 10 caracteres.',
            'severity.in' => 'La gravedad debe ser low, medium o high.',
        ];
    }
}
