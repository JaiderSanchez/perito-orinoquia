<?php

namespace App\Services;

use App\Models\PeritajeCompresionCilindro;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PeritajeCompresionService
{
    public function guardar($peritaje, Request $request): void
    {
        $cilindros = $request->input(
            'compresion_cilindros'
        );

        if ($cilindros === null) {
            $cilindros = $request->input(
                'compresionCilindros'
            );
        }

        if (is_string($cilindros)) {
            $cilindros = json_decode(
                $cilindros,
                true
            );
        }

        $peritaje->compresionCilindros()->delete();

        $registros = [];

        if (is_array($cilindros)) {
            foreach ($cilindros as $index => $item) {
                if (is_array($item)) {
                    $numero = $item['numero_cilindro']
                        ?? $item['numero']
                        ?? ($index + 1);

                    $presion = $item['presion_psi']
                        ?? $item['valor_psi']
                        ?? $item['valor']
                        ?? $item['psi']
                        ?? 0;
                } else {
                    $numero = $index + 1;
                    $presion = $item;
                }

                if ($presion === null || $presion === '') {
                    continue;
                }

                $registros[] = [
                    'id' => (string) Str::uuid(),
                    'peritaje_id' => $peritaje->id,
                    'numero_cilindro' => $numero,
                    'presion_psi' => $presion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!$registros) {
            for ($i = 1; $i <= 6; $i++) {
                $valor = $request->input(
                    "compresionCil{$i}"
                );

                if ($valor === null || $valor === '') {
                    $valor = $request->input(
                        "compresion_cil_{$i}"
                    );
                }

                if ($valor === null || $valor === '') {
                    continue;
                }

                $registros[] = [
                    'id' => (string) Str::uuid(),
                    'peritaje_id' => $peritaje->id,
                    'numero_cilindro' => $i,
                    'presion_psi' => $valor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($registros) {
            PeritajeCompresionCilindro::insert(
                $registros
            );
        }
    }
}
