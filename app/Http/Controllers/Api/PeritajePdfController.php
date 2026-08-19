<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use App\Services\PeritajePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PeritajePdfController extends Controller
{
    public function __construct(
        private PeritajePdfService $pdfService
    ) {
    }

    /**
     * GET /api/peritajes/{peritaje}/pdf?modo=descargar|previsualizar
     */
    public function generar(
        Peritaje $peritaje,
        Request $request
    ): Response {
        $usuario = $request->user();

        // El administrador puede generar cualquier PDF.
        if (
            !in_array(
                $usuario->rol,
                ['admin', 'superadmin'],
                true
            )
        ) {

            // Los inspectores solo pueden acceder
            // a PDFs de sus propios peritajes.
            if (
                $usuario->rol !== 'inspector' ||
                (int) $peritaje->inspector_id !== (int) $usuario->id
            ) {
                return response()->json([
                    'error' => 'No tienes permisos para acceder al PDF de este peritaje.'
                ], 403);
            }
        }

        $pdf = $this->pdfService->generar($peritaje);
        $nombre = $this->pdfService->nombreArchivo($peritaje);

        // "previsualizar" → abre inline.
        // "descargar" → fuerza la descarga.
        return $request->query('modo') === 'descargar'
            ? $pdf->download($nombre)
            : $pdf->stream($nombre);
    }
}
