<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Peritaje;
use App\Services\PeritajeArchivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeritajeArchivoController extends Controller
{
    public function __construct(
        private PeritajeArchivoService $archivoService
    ) {
    }

    public function store(
        Request $request,
        Peritaje $peritaje
    ): JsonResponse {
        if (!$this->archivoService->puedeGestionarPeritaje(
            $request,
            $peritaje
        )) {
            return response()->json([
                'error' =>
                    'No tienes permisos para gestionar los archivos de este peritaje.'
            ], 403);
        }

        $archivo = $this->archivoService->guardar(
            $request,
            $peritaje
        );

        return response()->json(
            $archivo,
            201
        );
    }

    public function guardarFirma(
        Request $request,
        Peritaje $peritaje
    ): JsonResponse {
        if (!$this->archivoService->puedeGestionarPeritaje(
            $request,
            $peritaje
        )) {
            return response()->json([
                'error' =>
                    'No tienes permisos para gestionar la firma de este peritaje.'
            ], 403);
        }

        $archivo = $this->archivoService->guardarFirma(
            $request,
            $peritaje
        );

        return response()->json(
            $archivo,
            201
        );
    }

    public function destroy(
        Request $request,
        Archivo $archivo
    ): JsonResponse {
        $peritaje = $archivo->peritaje;

        if (!$peritaje) {
            return response()->json([
                'error' =>
                    'El peritaje asociado al archivo no existe.'
            ], 404);
        }

        if (!$this->archivoService->puedeGestionarPeritaje(
            $request,
            $peritaje
        )) {
            return response()->json([
                'error' =>
                    'No tienes permisos para eliminar este archivo.'
            ], 403);
        }

        $this->archivoService->eliminar($archivo);

        return response()->json([
            'message' =>
                'Archivo eliminado correctamente.'
        ]);
    }
}
