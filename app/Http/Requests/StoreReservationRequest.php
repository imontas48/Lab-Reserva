<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Delega la autorización a ReservationPolicy@create.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Reservation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'equipment_id' => [
                'required',
                'integer',
                'exists:equipment,id',
                // Validación personalizada: verificar que el equipo esté operacional
                function ($attribute, $value, $fail) {
                    $equipment = \App\Models\Equipment::find($value);
                    if ($equipment && !$equipment->is_operational) {
                        $fail('El equipo seleccionado no está operacional y no puede ser reservado.');
                    }
                },
                // Validación de solapamiento: el equipo no debe tener reservas conflictivas
                function ($attribute, $value, $fail) {
                    $startTime = $this->input('start_time');
                    $endTime = $this->input('end_time');

                    // Solo validar si tenemos ambas fechas
                    if (!$startTime || !$endTime) {
                        return;
                    }

                    // Buscar reservas confirmadas que se solapen
                    $hasConflict = Reservation::where('equipment_id', $value)
                        ->where('status', 'confirmed')
                        ->where(function ($query) use ($startTime, $endTime) {
                            // Caso 1: La nueva reserva comienza durante una existente
                            $query->whereBetween('start_time', [$startTime, $endTime])
                                  // Caso 2: La nueva reserva termina durante una existente
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  // Caso 3: La nueva reserva engloba completamente una existente
                                  ->orWhere(function ($q) use ($startTime, $endTime) {
                                      $q->where('start_time', '>=', $startTime)
                                        ->where('end_time', '<=', $endTime);
                                  })
                                  // Caso 4: Una reserva existente engloba completamente la nueva
                                  ->orWhere(function ($q) use ($startTime, $endTime) {
                                      $q->where('start_time', '<=', $startTime)
                                        ->where('end_time', '>=', $endTime);
                                  });
                        })
                        ->exists();

                    if ($hasConflict) {
                        $fail('El equipo ya tiene una reserva confirmada en el rango de tiempo seleccionado.');
                    }
                },
            ],
            'start_time' => [
                'required',
                'date',
                'after:now', // La reserva debe ser en el futuro
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time', // La hora de fin debe ser después de la de inicio
                // Validación adicional: duración máxima de reserva (ej: 8 horas)
                function ($attribute, $value, $fail) {
                    $startTime = $this->input('start_time');
                    if ($startTime) {
                        $start = new \DateTime($startTime);
                        $end = new \DateTime($value);
                        $diff = $start->diff($end);
                        $hours = ($diff->days * 24) + $diff->h;

                        if ($hours > 8) {
                            $fail('La duración máxima de una reserva es de 8 horas.');
                        }

                        if ($diff->i < 30 && $hours === 0) {
                            $fail('La duración mínima de una reserva es de 30 minutos.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'equipment_id.required' => 'El equipo es obligatorio.',
            'equipment_id.exists' => 'El equipo seleccionado no existe.',
            'start_time.required' => 'La fecha y hora de inicio es obligatoria.',
            'start_time.after' => 'La reserva debe ser para una fecha y hora futura.',
            'end_time.required' => 'La fecha y hora de fin es obligatoria.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'equipment_id' => 'equipo',
            'start_time' => 'fecha y hora de inicio',
            'end_time' => 'fecha y hora de fin',
        ];
    }

    /**
     * Prepare the data for validation.
     * Convierte las fechas al formato correcto si es necesario.
     */
    protected function prepareForValidation(): void
    {
        // Aquí podríamos normalizar las fechas si el frontend las envía en otro formato
        // Por ahora, asumimos que vienen en formato ISO 8601
    }
}
