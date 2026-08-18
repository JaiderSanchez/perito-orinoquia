<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeCompresionCilindro;
use Illuminate\Http\Request;

class PeritajeCompresionService
{
    public function guardar(Peritaje $peritaje, Request $request): void
    {
        $raw = $request->input('compresion_cilindros') ?? $request->input('compresionCilindros');

        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }

        if (!is_array($raw)) {
            $raw = [];
        }

        $peritaje->compresionCilindros()->delete();

        foreach ($raw as $key => $valor) {
            if (is_array($valor)) {
                $numeroCilindro = $valor['numero_cilindro'] ?? $valor['numeroCilindro'] ?? null;
                $presion = $valor['presion_psi'] ?? $valor['valor'] ?? $valor['psi'] ?? null;
            } else {
                $numeroCilindro = $key;
                $presion = $valor;
            }

            if (!is_numeric($numeroCilindro) || $presion === null || $presion === '') {
                continue;
            }

            PeritajeCompresionCilindro::create([
                'peritaje_id' => $peritaje->id,
                'numero_cilindro' => (int) $numeroCilindro,
                'presion_psi' => $presion,
            ]);
        }
    }
}
