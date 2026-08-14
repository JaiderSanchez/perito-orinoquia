<?php

namespace App\Services;

use App\Models\Peritaje;
use App\Models\PeritajeImagen;
use Illuminate\Http\Request;

class PeritajeImagenService
{
    public function guardar(
        Peritaje $peritaje,
        Request $request
    ): void {
        $imagenes = $request->input('imagenes')
            ?? $request->input('peritaje_imagenes', []);

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

    public function eliminar(Peritaje $peritaje): void
    {
        $peritaje->imagenes()->delete();
    }

    public function eliminarImagen($id): void
    {
        PeritajeImagen::findOrFail($id)->delete();
    }
}
