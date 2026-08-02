<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use App\Services\PeritajePdfService;
use Illuminate\Http\Response;

class PeritajePdfController extends Controller
{
    public function __construct(private PeritajePdfService $pdfService)
    {
    }

    /** GET /api/peritajes/{peritaje}/pdf?modo=descargar|previsualizar */
    public function generar(Peritaje $peritaje, \Illuminate\Http\Request $request): Response
    {
        $pdf = $this->pdfService->generar($peritaje);
        $nombre = $this->pdfService->nombreArchivo($peritaje);

        // "previsualizar" -> se abre inline en el <iframe> del frontend (InformePdf.jsx).
        // "descargar" -> fuerza la descarga, igual que hoy hace doc.save() en dashboard.jsx.
        return $request->query('modo') === 'descargar'
            ? $pdf->download($nombre)
            : $pdf->stream($nombre);
    }
}
