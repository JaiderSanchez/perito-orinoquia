<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeritajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identificación del vehículo
            'sucursal_vendedor_id' => ['sometimes', 'nullable', 'uuid', 'exists:sucursales,id'],
            'sucursal_inspeccion_id' => ['sometimes', 'nullable', 'uuid', 'exists:sucursales,id'],
            'vendedor_id' => ['sometimes', 'nullable', 'uuid', 'exists:vendedores,id'],
            'placa' => ['sometimes', 'string', 'max:10'],
            'marca' => ['sometimes', 'string', 'max:80'],
            'linea' => ['sometimes', 'string', 'max:80'],
            'modelo_anio' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'num_motor' => ['sometimes', 'string', 'max:60'],
            'num_chasis' => ['sometimes', 'string', 'max:60'],
            'organismo_transito' => ['sometimes', 'nullable', 'string', 'max:120'],
            'kilometraje' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'tarjeta_operacion' => ['sometimes', 'nullable', 'string', 'max:60'],
            'configuracion_ejes' => ['sometimes', 'nullable', 'string', 'max:60'],

            // Documentación legal
            'numero_soat' => ['sometimes', 'nullable', 'string', 'max:60'],
            'entidad_emisora_soat' => ['sometimes', 'nullable', 'string', 'max:120'],
            'vence_soat' => ['sometimes', 'nullable', 'date'],
            'soat_al_dia' => ['sometimes', 'boolean'],
            'numero_control_rtm' => ['sometimes', 'nullable', 'string', 'max:60'],
            'cda_emisor' => ['sometimes', 'nullable', 'string', 'max:120'],
            'vence_tecnico_mecanica' => ['sometimes', 'nullable', 'date'],
            'tecnico_mecanica_al_dia' => ['sometimes', 'boolean'],
            'coincide_propietario_runt' => ['sometimes', 'boolean'],
            'tiene_embargos_o_alertas' => ['sometimes', 'boolean'],
            'restriccion_blindaje' => ['sometimes', 'string', 'max:40'],
            'comentarios_siniestros' => ['sometimes', 'nullable', 'string'],

            // Motor (parámetros globales)
            'tipo_transmision' => ['sometimes', 'nullable', 'string', 'max:40'],
            'estado_transmision' => ['sometimes', 'nullable', 'string', 'max:40'],
            'comentarios_motor' => ['sometimes', 'nullable', 'string'],
            'porcentaje_bateria' => ['sometimes', 'nullable', 'integer', 'between:0,100'],
            'vida_util_bateria' => ['sometimes', 'nullable', 'string', 'max:120'],

            // Costos
            'costo_alistamiento' => ['sometimes', 'numeric', 'min:0'],
            'costo_reparacion' => ['sometimes', 'numeric', 'min:0'],
            'tiempo_estimado_reparacion' => ['sometimes', 'nullable', 'string', 'max:40'],

            // Concepto final / puntajes
            'estado_general_vehiculo' => ['sometimes', 'string', 'max:40'],
            'concepto_final' => ['sometimes', 'nullable', 'string'],
            'comentarios_generales' => ['sometimes', 'nullable', 'string'],
            'score_estructura' => ['sometimes', 'integer', 'between:0,100'],
            'score_carroceria' => ['sometimes', 'integer', 'between:0,100'],
            'score_mecanica' => ['sometimes', 'integer', 'between:0,100'],
            'score_electrico' => ['sometimes', 'integer', 'between:0,100'],
            'score_legal' => ['sometimes', 'integer', 'between:0,100'],

            'estado' => ['sometimes', Rule::in(['borrador', 'en_proceso', 'completado', 'anulado'])],
        ];
    }
}
