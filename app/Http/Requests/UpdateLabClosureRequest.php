<?php

namespace App\Http\Requests;

class UpdateLabClosureRequest extends StoreLabClosureRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('closure'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['starts_at', 'ends_at', 'reason'] as $field) {
            $rules[$field] = array_merge(['sometimes'], $rules[$field]);
        }

        return $rules;
    }
}
