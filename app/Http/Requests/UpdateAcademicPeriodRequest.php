<?php

namespace App\Http\Requests;

class UpdateAcademicPeriodRequest extends StoreAcademicPeriodRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('academic_period'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['name', 'starts_on', 'ends_on'] as $field) {
            $rules[$field] = array_merge(['sometimes'], $rules[$field]);
        }

        return $rules;
    }
}
