<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Peritaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PeritajeArchivoController extends Controller
{
    private const CATEGORIAS = [
        'SOAT',
        'RTM',
        'FIRMA_INSPECTOR',
        'FOTO_DANO_EXTERNO',
        'FOTO_DETALLE_TECNICO',
        'FOTO_ACCESORIO',
        'OTRO',
    ];

    /**
     * Verifica si el usuario puede gestionar el peritaje.
     *
     * Admin:
     * - Puede gestionar cualquier peritaje.
     *
     * Inspector:
     * - Solo puede gestionar sus propios peritajes.
     */
    private function puedeGestionarPeritaje(Request $request, Peritaje $peritaje): bool
    {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }

        // El administrador tiene acceso global.
        if ($usuario->rol === 'admin') {
            return true;
        }

        // Los inspectores solo pueden gestionar sus propios peritajes.
        return $usuario->rol === 'inspector'
            && (int) $peritaje->inspector_id === (int) $usuario->id;
    }

    /**
     * POST /api/peritajes/{peritaje}/archivos
     *
     * Sube un archivo real (multipart/form-data).
     */
    public function store(Request $request, Peritaje $peritaje): JsonResponse
    {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para gestionar los archivos de este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:8192',
            ],
            'categoria' => [
                'required',
                'in:' . implode(',', self::CATEGORIAS),
            ],
            'entidad_relacionada_id' => [
                'nullable',
                'uuid',
            ],
        ]);

        $file = $data['file'];

        $carpeta = "peritajes/{$peritaje->id}";

        $nombreArchivo = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $ruta = $file->storeAs(
            $carpeta,
            $nombreArchivo,
            'public'
        );

        $archivo = $peritaje->archivos()->create([
            'categoria' => $data['categoria'],
            'entidad_relacionada_id' => $data['entidad_relacionada_id'] ?? null,
            'nombre_original' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'url' => $ruta,
            'tamanio_bytes' => $file->getSize(),
            'subido_por' => $request->user()->id,
        ]);

        return response()->json($archivo, 201);
    }

    /**
     * POST /api/peritajes/{peritaje}/firma
     *
     * Guarda la firma del inspector como imagen Base64.
     */
    public function guardarFirma(Request $request, Peritaje $peritaje): JsonResponse
    {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para gestionar la firma de este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'firma_base64' => [
                'required',
                'string',
                'starts_with:data:image/png;base64,',
            ],
        ]);

        $contenido = base64_decode(
            substr(
                $data['firma_base64'],
                strpos($data['firma_base64'], ',') + 1
            )
        );

        if ($contenido === false) {
            return response()->json([
                'error' => 'La firma enviada no es válida.'
            ], 422);
        }

        $carpeta = "peritajes/{$peritaje->id}";

        $nombreArchivo = 'firma_' . Str::uuid() . '.png';

        Storage::disk('public')->put(
            "{$carpeta}/{$nombreArchivo}",
            $contenido
        );

        // Solo debe existir una firma vigente por peritaje.
        $firmasAnteriores = $peritaje->archivos()
            ->where('categoria', 'FIRMA_INSPECTOR')
            ->get();

        foreach ($firmasAnteriores as $firmaAnterior) {
            Storage::disk('public')->delete($firmaAnterior->url);
            $firmaAnterior->delete();
        }

        $archivo = $peritaje->archivos()->create([
            'categoria' => 'FIRMA_INSPECTOR',
            'nombre_original' => $nombreArchivo,
            'mime_type' => 'image/png',
            'url' => "{$carpeta}/{$nombreArchivo}",
            'tamanio_bytes' => strlen($contenido),
            'subido_por' => $request->user()->id,
        ]);

        $peritaje->update([
            'firmado_en' => now(),
        ]);

        return response()->json($archivo, 201);
    }

    /**
     * DELETE /api/archivos/{archivo}
     */
    public function destroy(Request $request, Archivo $archivo): JsonResponse
    {
        // Cargamos el peritaje relacionado.
        $peritaje = $archivo->peritaje;

        if (!$peritaje) {
            return response()->json([
                'error' => 'El peritaje asociado al archivo no existe.'
            ], 404);
        }

        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para eliminar este archivo.'
            ], 403);
        }

        Storage::disk('public')->delete($archivo->url);

        $archivo->delete();

        return response()->json([
            'message' => 'Archivo eliminado correctamente.'
        ]);
    }
}
