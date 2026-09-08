<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
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
                    $equipment = Equipment::find($value);
                    if ($equipment && ! $equipment->is_operational) {
                        $fail('El equipo seleccionado no está operacional y no puede ser reservado.');
                    }
                },
                // Validación de solapamiento: el equipo no debe tener reservas conflictivas
                function ($attribute, $value, $fail) {
                    $startTime = $this->input('start_time');
                    $endTime = $this->input('end_time');

                    // Solo validar si tenemos ambas fechas y son interpretables.
                    // Esta regla se evalúa aunque start_time y end_time hayan
                    // fallado su propia validación, así que sin esta guarda una
                    // fecha ilegible llegaba hasta Carbon::parse y salía como
                    // 500 en vez de como error de validación.
                    if (! $startTime || ! $endTime || ! strtotime((string) $startTime) || ! strtotime((string) $endTime)) {
                        return;
                    }

                    // Comprobacion temprana, para devolver un 422 de validacion
                    // con el resto de errores del formulario. La garantia real
                    // frente a concurrencia esta en ReservationService, dentro
                    // de la transaccion y con la fila del equipo bloqueada.
                    $hasConflict = Reservation::query()
                        ->forEquipment($value)
                        ->blocking($startTime, $endTime)
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

                    // Las reglas de closure se evalúan aunque 'date' ya haya
                    // fallado, así que hay que comprobar que ambos valores sean
                    // interpretables: de lo contrario new \DateTime() lanza y
                    // la respuesta sale como 500 en vez de como 422.
                    if ($startTime && strtotime((string) $startTime) && strtotime((string) $value)) {
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
    /**
     * Normaliza los instantes al huso de la aplicación antes de validar.
     *
     * El método estaba vacío, con un comentario que decía que "asumimos que
     * vienen en formato ISO 8601". El frontend manda de hecho dos formatos
     * distintos al mismo endpoint —el calendario, hora local sin offset; el
     * formulario, UTC con sufijo Z— y las columnas son DATETIME sin huso, así
     * que sin normalizar aquí el mismo horario se guardaba desplazado según por
     * dónde se hubiese creado la reserva.
     *
     * A partir de aquí todo el backend trabaja en el huso de la aplicación:
     * validación, almacenamiento y comparación de solapamiento.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['start_time', 'end_time'] as $field) {
            $value = $this->input($field);

            if (! is_string($value) || $value === '') {
                continue;
            }

            try {
                $normalized[$field] = Reservation::normalizeInstant($value);
            } catch (\Exception) {
                // Formato irreconocible: se deja tal cual para que la regla
                // 'date' produzca un error de validación legible en vez de una
                // excepción aquí.
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
