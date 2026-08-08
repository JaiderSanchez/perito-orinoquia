<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PeritajeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'estado' => $this->estado,

            // Fecha formateada para lectura directa en tabla y fecha cruda de respaldo
            'fecha' => $this->fecha_peritaje
                ? Carbon::parse($this->fecha_peritaje)->format('Y-m-d H:i')
                : ($this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i') : null),

            'fecha_peritaje' => $this->fecha_peritaje,
            'placa' => $this->placa ?? 'SIN PLACA',
            'marca' => $this->marca ?? 'N / A',
            'linea' => $this->linea ?? 'N / A',
            'modelo' => $this->modelo_anio,
            'modelo_anio' => $this->modelo_anio,
            'kilometraje' => $this->kilometraje ?? 0,
            'km' => $this->kilometraje ?? 0,

            // Relaciones enviadas como objetos para que React evalúe ?.nombre correctamente
            'sucursal_vendedor' => $this->whenLoaded('sucursalVendedor'),
            'sucursal_inspeccion' => $this->whenLoaded('sucursalInspeccion'),
            'vendedor' => $this->whenLoaded('vendedor'),
            'inspector' => $this->whenLoaded('inspector'),

            // Estructura anidada de respaldo para el vehículo
            'vehiculo' => [
                'placa' => $this->placa,
                'marca' => $this->marca,
                'linea' => $this->linea,
                'modelo' => $this->modelo_anio,
                'kilometraje' => $this->kilometraje,
                'tipo' => $this->whenLoaded('tipoVehiculo', fn() => $this->tipoVehiculo->nombre ?? null),
            ],

            'documentacion' => [
                'soat_al_dia' => $this->soat_al_dia,
                'vence_soat' => $this->vence_soat,
                'tecnico_mecanica_al_dia' => $this->tecnico_mecanica_al_dia,
                'vence_rtm' => $this->vence_tecnico_mecanica,
            ],

            'accesorios' => $this->whenLoaded('accesorios'),
            'danos_externos' => $this->whenLoaded('danosExternos'),
            'danos_internos' => $this->whenLoaded('danosInternos'),
            'detalles_tecnicos' => $this->whenLoaded('detallesTecnicos'),
            'sistemas_mecanicos' => $this->whenLoaded('sistemasMecanicos'),
            'compresion_cilindros' => $this->whenLoaded('compresionCilindros'),
            'archivos' => $this->whenLoaded('archivos'),

            'creado_en' => $this->created_at,
        ];
    }
}
