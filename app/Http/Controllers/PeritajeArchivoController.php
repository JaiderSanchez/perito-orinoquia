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
        'SOAT', 'RTM', 'FIRMA_INSPECTOR', 'FOTO_DANO_EXTERNO',
        'FOTO_DETALLE_TECNICO', 'FOTO_ACCESORIO', 'OTRO',
    ];

    /**
     * POST /api/peritajes/{peritaje}/archivos
     * Sube un archivo real (multipart/form-data) — reemplaza el flujo actual
     * del frontend que convierte todo a base64 y lo mete en el state.
     *
     * Campos esperados: file (binario), categoria, entidad_relacionada_id (opcional,
     * ej. el id de la fila de peritaje_danos_externos a la que pertenece la foto).
     */
    public function store(Request $request, Peritaje $peritaje): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'], // 8 MB
            'categoria' => ['required', 'in:' . implode(',', self::CATEGORIAS)],
            'entidad_relacionada_id' => ['nullable', 'uuid'],
        ]);

        $file = $data['file'];
        $carpeta = "peritajes/{$peritaje->id}";
        $nombreArchivo = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $ruta = $file->storeAs($carpeta, $nombreArchivo, 'public');

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
     * Caso especial: la firma llega como imagen base64 (así la produce
     * react-signature-canvas con canvas.toDataURL()), no como archivo
     * multipart. La decodificamos y la guardamos igual que cualquier otra
     * imagen en el storage.
     */
    public function guardarFirma(Request $request, Peritaje $peritaje): JsonResponse
    {
        $data = $request->validate([
            'firma_base64' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $contenido = base64_decode(substr($data['firma_base64'], strpos($data['firma_base64'], ',') + 1));
        $carpeta = "peritajes/{$peritaje->id}";
        $nombreArchivo = 'firma_' . Str::uuid() . '.png';
        Storage::disk('public')->put("{$carpeta}/{$nombreArchivo}", $contenido);

        // Solo debe existir una firma vigente por peritaje.
        $peritaje->archivos()->where('categoria', 'FIRMA_INSPECTOR')->delete();

        $archivo = $peritaje->archivos()->create([
            'categoria' => 'FIRMA_INSPECTOR',
            'nombre_original' => $nombreArchivo,
            'mime_type' => 'image/png',
            'url' => "{$carpeta}/{$nombreArchivo}",
            'tamanio_bytes' => strlen($contenido),
            'subido_por' => $request->user()->id,
        ]);

        $peritaje->update(['firmado_en' => now()]);

        return response()->json($archivo, 201);
    }

    /** DELETE /api/archivos/{archivo} */
    public function destroy(Archivo $archivo): JsonResponse
    {
        Storage::disk('public')->delete($archivo->url);
        $archivo->delete();

        return response()->json(['message' => 'Archivo eliminado']);
    }
}
