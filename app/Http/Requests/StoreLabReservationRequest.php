<?php

namespace App\Http\Requests;

use App\Models\Lab;
use App\Models\Reservation;

/**
 * Solicitud de un laboratorio completo para una clase.
 *
 * El motivo es obligatorio: es lo que el administrador lee para decidir y lo
 * que aparece en el calendario de los demas usuarios.
 */
class StoreLabReservationRequest extends ReservationWindowRequest
{
    /**
     * Delega la autorización a ReservationPolicy@createLab.
     */
    public function authorize(): bool
    {
        return $this->user()->can('createLab', Reservation::class);
    }

    protected function targetRules(): array
    {
        return [
            'lab_id' => [
                'required',
                'integer',
                'exists:labs,id',
                fn ($attribute, $value, $fail) => $this->validateLab((int) $value, $fail),
            ],
        ];
    }

    protected function purposeRules(): array
    {
        return ['required', 'string', 'min:3', 'max:255'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'lab_id.required' => 'El laboratorio es obligatorio.',
            'lab_id.exists' => 'El laboratorio seleccionado no existe.',
            'purpose.required' => 'Indica el motivo de la clase para la que solicitas el laboratorio.',
            'purpose.min' => 'El motivo debe tener al menos 3 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return parent::attributes() + ['lab_id' => 'laboratorio'];
    }

    /**
     * Comprobacion temprana del conflicto cruzado; la garantia real esta en
     * ReservationService con la fila del laboratorio bloqueada.
     */
    private function validateLab(int $labId, \Closure $fail): void
    {
        $lab = Lab::query()->find($labId);

        if ($lab === null) {
            return;
        }

        if (! $lab->is_active) {
            $fail('El laboratorio seleccionado no está activo y no puede ser reservado.');
        }

        if (! $this->hasParsableWindow()) {
            return;
        }

        if (($violation = $this->scheduleViolation($lab)) !== null) {
            $fail($violation);

            return;
        }

        $start = $this->input('start_time');
        $end = $this->input('end_time');

        if (Reservation::query()->forLab($lab->id)->blocking($start, $end)->exists()) {
            $fail('El laboratorio ya tiene una clase reservada en el horario seleccionado.');

            return;
        }

        if (Reservation::query()->forEquipmentInLab($lab->id)->blocking($start, $end)->exists()) {
            $fail('Hay equipos del laboratorio ya reservados en el horario seleccionado.');
        }
    }
}
