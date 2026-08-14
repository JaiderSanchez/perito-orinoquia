<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeImagen;
use Illuminate\Http\Request;

class PeritajeImagenService
{
    /**
     * Guarda las imágenes asociadas al peritaje.
     *
     * El frontend puede enviar:
     *
     * - imagenes
     * - peritaje_imagenes
     *
     * Y cada imagen puede utilizar:
     *
     * - imagen_base64
     * - base64
     */
    public function guardar(
        Peritaje $peritaje,
        Request $request
    ): void {
        $imagenes = $request->input('imagenes')
            ?? $request->input('peritaje_imagenes', []);

        /*
         * Cuando llega como JSON dentro de FormData,
         * Laravel lo recibe como string.
         */
        if (is_string($imagenes)) {
            $imagenes = json_decode($imagenes, true);
        }

        if (!is_array($imagenes)) {
            return;
        }

        foreach ($imagenes as $imagen) {
            if (!is_array($imagen)) {
                continue;
            }

            $seccion = $imagen['seccion'] ?? null;

            $base64 = $imagen['imagen_base64']
                ?? $imagen['base64']
                ?? null;

            /*
             * Una imagen necesita al menos sección
             * y contenido.
             */
            if (!$seccion || !$base64) {
                continue;
            }

            PeritajeImagen::updateOrCreate(
                [
                    'peritaje_id' => $peritaje->id,
                    'seccion' => $seccion,
                    'item_id' => $imagen['item_id'] ?? null,
                ],
                [
                    'imagen_base64' => $base64,
                    'nombre_archivo' => $imagen['nombre_archivo']
                        ?? 'archivo_peritaje',
                ]
            );
        }
    }

    /**
     * Elimina todas las imágenes de un peritaje.
     */
    public function eliminar(Peritaje $peritaje): void
    {
        $peritaje->imagenes()->delete();
    }

    /**
     * Elimina una imagen específica.
     */
    public function eliminarImagen($id): void
    {
        PeritajeImagen::findOrFail($id)->delete();
    }
}
