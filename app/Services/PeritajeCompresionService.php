<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeCompresionCilindro;
use Illuminate\Http\Request;

class PeritajeCompresionService
{
    public function guardar(Peritaje $peritaje, Request $request): void
    {
        /*
         * Estructura principal:
         *
         * compresion_cilindros: {
         *     1: {
         *         presion_psi: 150,
         *         fuga_porcentaje: 8
         *     },
         *     2: {
         *         presion_psi: 145,
         *         fuga_porcentaje: 10
         *     }
         * }
         */

        $raw = $request->input('compresion_cilindros')
            ?? $request->input('compresionCilindros');

        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }

        if (!is_array($raw)) {
            $raw = [];
        }

        /*
         * También aceptamos directamente:
         *
         * compresionCil1
         * compresionCil2
         * ...
         *
         * fugaCil1
         * fugaCil2
         * ...
         */

        for ($i = 1; $i <= 4; $i++) {

            $cilindro = $raw[$i] ?? $raw[(string) $i] ?? [];

            if (!is_array($cilindro)) {
                $cilindro = [];
            }

            /*
             * COMPRESIÓN
             */
            $presion = $cilindro['presion_psi']
                ?? $cilindro['valor']
                ?? $cilindro['psi']
                ?? null;

            if ($presion === null) {
                $presion = $request->input("compresionCil{$i}")
                    ?? $request->input("compresion_cil_{$i}")
                    ?? $request->input("compresion_cil{$i}");
            }

            /*
             * FUGA
             */
            $fuga = $cilindro['fuga_porcentaje']
                ?? $cilindro['fuga']
                ?? $cilindro['porcentaje']
                ?? null;

            if ($fuga === null) {
                $fuga = $request->input("fugaCil{$i}")
                    ?? $request->input("fuga_cil_{$i}")
                    ?? $request->input("fuga_cil{$i}");
            }

            /*
             * Buscar registro existente.
             */
            $cilindroExistente = $peritaje
                ->compresionCilindros()
                ->where('numero_cilindro', $i)
                ->first();

            /*
             * No perder información existente.
             */
            $presionFinal =
                ($presion !== null && $presion !== '')
                    ? $presion
                    : $cilindroExistente?->presion_psi;

            $fugaFinal =
                ($fuga !== null && $fuga !== '')
                    ? $fuga
                    : $cilindroExistente?->fuga_porcentaje;

            /*
             * Guardar.
             */
            if (
                ($presionFinal !== null && $presionFinal !== '')
                ||
                ($fugaFinal !== null && $fugaFinal !== '')
            ) {
                PeritajeCompresionCilindro::updateOrCreate(
                    [
                        'peritaje_id' => $peritaje->id,
                        'numero_cilindro' => $i,
                    ],
                    [
                        'presion_psi' => (
                            $presionFinal !== ''
                            && $presionFinal !== null
                        )
                            ? $presionFinal
                            : null,

                        'fuga_porcentaje' => (
                            $fugaFinal !== ''
                            && $fugaFinal !== null
                        )
                            ? $fugaFinal
                            : null,
                    ]
                );
            }
        }
    }
}
