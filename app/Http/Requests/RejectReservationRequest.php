<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class RejectReservationRequest extends FormRequest
{
    /**
     * Se autoriza aqui y no solo en el controlador: un FormRequest valida
     * antes de que el controlador ejecute, y quien no puede rechazar debe
     * recibir 403 aunque no haya mandado motivo, no un 422 que le describe
     * el formulario.
     */
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');

        return $reservation instanceof Reservation
            && $this->user()->can('reject', $reservation);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Indica el motivo del rechazo para que el solicitante pueda corregir su petición.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => 'motivo'];
    }
}
