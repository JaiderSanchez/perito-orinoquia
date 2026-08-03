<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeritajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el control de acceso real se hace en middleware auth:sanctum
    }

   public function rules(): array
    {
            return [
            'tipo_vehiculo_id' => ['required', 'uuid', 'exists:tipos_vehiculo,id'],
            'placa'            => ['nullable', 'string', 'max:10'], // Ponlo nullable por ahora si no lo pides en este botón
            'marca'            => ['nullable', 'string', 'max:80'],
            'linea'            => ['nullable', 'string', 'max:80'],
            'modelo_anio'      => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'num_motor'        => ['nullable', 'string', 'max:60'],
            'num_chasis'       => ['nullable', 'string', 'max:60'],

            'sucursal_vendedor_id'   => ['nullable', 'uuid', 'exists:sucursales,id'],
            'sucursal_inspeccion_id' => ['nullable', 'uuid', 'exists:sucursales,id'],
            'vendedor_id'            => ['nullable', 'uuid', 'exists:vendedores,id'],
            'organismo_transito'     => ['nullable', 'string', 'max:120'],
            'kilometraje'          => ['nullable', 'integer', 'min:0'],
            'tarjeta_operacion'      => ['nullable', 'string', 'max:60'],
            'configuracion_ejes'     => ['nullable', 'string', 'max:60'],
        ];
    }
}
