<?php

namespace App\Services;

use App\Models\PeritajeCompresionCilindro;
use Illuminate\Support\Str;

class PeritajeCompresionService
{
    /**
     * Guarda la compresión de los cilindros.
     */
    public function guardar($peritaje, $cilindros, $request = null): void
    {
        $peritaje->compresionCilindros()->delete();

        $cilindrosMapeados = [];

        /*
         * Formato:
         *
         * compresion_cilindros: [
         *     {
         *         numero_cilindro: 1,
         *         presion_psi: 150
         *     }
         * ]
         */
        if (is_string($cilindros)) {
            $cilindros = json_decode($cilindros, true);
        }

        if (is_array($cilindros) && !empty($cilindros)) {
            foreach ($cilindros as $index => $item) {
                if (is_array($item)) {
                    $numero = $item['numero_cilindro']
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

                $cilindrosMapeados[] = [
                    'numero_cilindro' => $numero,
                    'presion_psi' => $presion,
                ];
            }
        }

        /*
         * Compatibilidad con el formato antiguo:
         *
         * compresionCil1
         * compresionCil2
         * ...
         */
        if (
            empty($cilindrosMapeados)
            && $request
        ) {
            for ($i = 1; $i <= 6; $i++) {
                $valor = $request->input(
                    "compresionCil{$i}"
                );

                if ($valor === null || $valor === '') {
                    $valor = $request->input(
                        "compresion_cil_{$i}"
                    );
                }

                if ($valor !== null && $valor !== '') {
                    $cilindrosMapeados[] = [
                        'numero_cilindro' => $i,
                        'presion_psi' => $valor,
                    ];
                }
            }
        }

        foreach ($cilindrosMapeados as $cilindro) {
            PeritajeCompresionCilindro::create([
                'id' => (string) Str::uuid(),
                'peritaje_id' => $peritaje->id,
                'numero_cilindro' => $cilindro['numero_cilindro'],
                'presion_psi' => $cilindro['presion_psi'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
