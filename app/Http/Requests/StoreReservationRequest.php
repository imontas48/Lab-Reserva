<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;

/**
 * Reserva de un equipo individual.
 */
class StoreReservationRequest extends ReservationWindowRequest
{
    /**
     * Delega la autorización a ReservationPolicy@create.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Reservation::class);
    }

    protected function targetRules(): array
    {
        return [
            'equipment_id' => [
                'required',
                'integer',
                'exists:equipment,id',
                fn ($attribute, $value, $fail) => $this->validateEquipment((int) $value, $fail),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'equipment_id.required' => 'El equipo es obligatorio.',
            'equipment_id.exists' => 'El equipo seleccionado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return parent::attributes() + ['equipment_id' => 'equipo'];
    }

    /**
     * Comprobacion temprana, para devolver un 422 de validacion con el resto
     * de errores del formulario. La garantia real frente a concurrencia esta
     * en ReservationService, dentro de la transaccion y con el laboratorio y
     * el equipo bloqueados.
     */
    private function validateEquipment(int $equipmentId, \Closure $fail): void
    {
        $equipment = Equipment::query()->select(['id', 'lab_id', 'is_operational'])->find($equipmentId);

        if ($equipment === null) {
            return;
        }

        if (! $equipment->is_operational) {
            $fail('El equipo seleccionado no está operacional y no puede ser reservado.');
        }

        if (! $this->hasParsableWindow()) {
            return;
        }

        $lab = Lab::query()->find($equipment->lab_id);

        if ($lab !== null && ($violation = $this->scheduleViolation($lab)) !== null) {
            $fail($violation);

            return;
        }

        $start = $this->input('start_time');
        $end = $this->input('end_time');

        if (Reservation::query()->forLab($equipment->lab_id)->blocking($start, $end)->exists()) {
            $fail('El laboratorio está reservado para una clase en el horario seleccionado.');

            return;
        }

        if (Reservation::query()->forEquipment($equipment->id)->blocking($start, $end)->exists()) {
            $fail('El equipo ya tiene una reserva en el rango de tiempo seleccionado.');
        }
    }
}
