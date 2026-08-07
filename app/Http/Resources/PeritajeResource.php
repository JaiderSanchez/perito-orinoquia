<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

            'creado_en' => $this->created_at,
        ];
    }
}
