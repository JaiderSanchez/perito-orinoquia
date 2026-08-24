<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use App\Services\PeritajeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PeritajeController extends Controller
{
    public function __construct(
        private PeritajeService $peritajeService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->peritajeService->index()
        );
    }

    public function show($id): JsonResponse
    {
        return response()->json(
            $this->peritajeService->show($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(
            $this->peritajeService->store($request),
            201
        );
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(
            $this->peritajeService->update($request, $id)
        );
    }

    public function destroy($id): JsonResponse
    {
        $this->peritajeService->destroy($id);
        return response()->json([
            'message' => 'Peritaje eliminado correctamente.'
        ], 200);
    }

    public function cambiarEstado(Request $request, Peritaje $peritaje): JsonResponse
    {
        $validated = $request->validate([
            'estado' => 'required|string|in:PENDIENTE,EN_PROGRESO,COMPLETADO,CANCELADO',
        ]);

        $peritaje = $this->peritajeService->cambiarEstado($peritaje, $validated['estado']);

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'peritaje' => $peritaje
        ], 200);
    }

    public function buscarClientes(Request $request): JsonResponse
    {
        return response()->json(
            $this->peritajeService->buscarClientes(
                $request->input('query')
            )
        );
    }
}
