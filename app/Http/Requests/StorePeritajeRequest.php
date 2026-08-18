<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeritajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            collect($this->all())->map(function ($value) {
                return $value === "" ? null : $value;
            })->toArray()
        );
    }

    public function rules(): array
    {
        return [
            'tipo_vehiculo_id' => ['required', 'uuid', 'exists:tipos_vehiculo,id'],
            'placa' => ['nullable', 'string', 'max:10'],
            'marca' => ['nullable', 'string', 'max:80'],
            'linea' => ['nullable', 'string', 'max:80'],
            'modelo_anio' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'num_motor' => ['nullable', 'string', 'max:60'],
            'num_chasis' => ['nullable', 'string', 'max:60'],

            'sucursal_vendedor_id' => ['nullable', 'uuid', 'exists:sucursales,id'],
            'sucursal_inspeccion_id' => ['nullable', 'uuid', 'exists:sucursales,id'],
            'vendedor_id' => ['nullable', 'uuid', 'exists:vendedores,id'],
            'organismo_transito' => ['nullable', 'string', 'max:120'],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
            'tarjeta_operacion' => ['nullable', 'string', 'max:60'],
            'configuracion_ejes' => ['nullable', 'string', 'max:60'],

            'cilindraje' => 'nullable|string|max:50',
            'tipo_transmision' => 'nullable|string|max:50',
            'traccion' => 'nullable|string|max:50',
            'estado_transmision' => 'nullable|string|max:50',
            'sistemas_mecanicos' => 'nullable|array',

        ];
    }
}
