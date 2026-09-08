<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base de las peticiones de listado.
 *
 * Los controladores pasaban sort_by, sort_order y per_page crudos desde la
 * query string hasta orderBy() y paginate(). Eso daba tres fallos:
 *
 *  - sort_by con una columna inexistente lanzaba QueryException -> HTTP 500,
 *    y al ir directo a orderBy() era una inyeccion de identificador de columna.
 *  - sort_order distinto de asc/desc lanzaba InvalidArgumentException -> 500.
 *  - per_page llegaba como string a un parametro tipado ?int (TypeError) y sin
 *    tope maximo permitia pedir la tabla entera en una sola peticion.
 *
 * Cada listado declara sus columnas ordenables; nada fuera de esa lista llega
 * a la consulta.
 */
abstract class IndexQueryRequest extends FormRequest
{
    /**
     * Columnas por las que se permite ordenar este listado.
     *
     * @return array<int, string>
     */
    abstract protected function sortableColumns(): array;

    public function authorize(): bool
    {
        // La autorizacion del listado la resuelve la policy en el controlador.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'sort_by' => ['sometimes', 'string', Rule::in($this->sortableColumns())],
            'sort_order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ], $this->additionalRules());
    }

    /**
     * Reglas propias de cada listado.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function additionalRules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort_by.in' => 'No se puede ordenar por ese campo. Permitidos: '
                .implode(', ', $this->sortableColumns()).'.',
            'sort_order.in' => 'El orden debe ser asc o desc.',
            'per_page.max' => 'No se pueden solicitar mas de 100 registros por pagina.',
        ];
    }

    /**
     * Filtros ya validados, listos para el servicio.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->except(['per_page']),
            fn ($value) => $value !== null
        );
    }

    public function perPage(): ?int
    {
        $perPage = $this->safe()->integer('per_page');

        return $perPage > 0 ? $perPage : null;
    }
}
