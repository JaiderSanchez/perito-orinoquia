<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePeritajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Asegúrate de que esté en true
    }

    public function rules(): array
    {
        return [
            // Estado y relaciones principales
            'estado' => ['sometimes', 'string', 'in:borrador,en_proceso,completado,anulado'],
            'tipo_vehiculo_id' => ['sometimes', 'uuid', 'exists:tipos_vehiculo,id'],
            'sucursal_vendedor_id' => ['nullable'],
            'sucursal_inspeccion_id' => ['nullable'],
            'vendedor_id' => ['nullable'],

            // Información General
            'placa' => ['sometimes', 'string', 'max:10'],
            'marca' => ['sometimes', 'string', 'max:80'],
            'linea' => ['sometimes', 'string', 'max:80'],
            'modelo_anio' => ['sometimes', 'integer'],
            'num_motor' => ['sometimes', 'string', 'max:60'],
            'num_chasis' => ['sometimes', 'string', 'max:60'],
            'kilometraje' => ['sometimes', 'integer'],
            'organismo_transito' => ['nullable', 'string', 'max:100'],

            // Documentación (SOAT / RTM)
            'numero_soat' => ['nullable', 'string', 'max:50'],
            'entidad_emisora_soat' => ['nullable', 'string', 'max:100'],
            'vence_soat' => ['nullable', 'date'],
            'soat_al_dia' => ['sometimes', 'boolean'],
            'numero_control_rtm' => ['nullable', 'string', 'max:50'],
            'cda_emisor' => ['nullable', 'string', 'max:100'],
            'vence_tecnico_mecanica' => ['nullable', 'date'],
            'tecnico_mecanica_al_dia' => ['sometimes', 'boolean'],

            // Alertas Legales y Restricciones
            'coincide_propietario_runt' => ['sometimes', 'boolean'],
            'tiene_embargos_o_alertas' => ['sometimes', 'boolean'],
            'restriccion_blindaje' => ['sometimes', 'string'],

            // Motor y Diagnósticos
            'compresion_motor' => ['nullable', 'string'],
            'fugas_aceite' => ['sometimes', 'boolean'],
            'estado_bateria' => ['nullable', 'string'],
            'ruidos_extranos' => ['sometimes', 'boolean'],
            'comentarios_motor' => ['nullable', 'string'],

            // Resultados y Concepto
            'estado_general_vehiculo' => ['sometimes', 'string'],
            'concepto_final' => ['nullable', 'string'],
            'tiempo_estimado_reparacion' => ['nullable', 'string'],

            // Puntuaciones
            'score_estructura' => ['sometimes', 'numeric'],
            'score_carroceria' => ['sometimes', 'numeric'],
            'score_mecanica' => ['sometimes', 'numeric'],
            'score_electrico' => ['sometimes', 'numeric'],
            'score_legal' => ['sometimes', 'numeric'],

            // Arreglos / Listas (muy importante para que no los borre)
            'accesorios' => ['nullable', 'array'],
            'danos_externos' => ['nullable', 'array'],
            'danos_internos' => ['nullable', 'array'],
            'detalles_tecnicos' => ['nullable', 'array'],
            'sistemas_mecanicos' => ['nullable', 'array'],
            'compresion_cilindros' => ['nullable', 'array'],
        ];
    }
}
