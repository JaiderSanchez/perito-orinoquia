<?php

namespace App\Services;

use App\Models\Peritaje;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PeritajePdfService
{
    /**
     * Genera el PDF completo del peritaje (Dompdf) con TODAS las secciones
     * capturadas en el formulario: identificación, documentación legal,
     * motor + checklist mecánico + compresión, accesorios, daños externos,
     * daños internos, detalles técnicos, concepto final y firma.
     *
     * Esto reemplaza a "handleDescargarPDF" de dashboard.jsx, que hoy solo
     * arma 5 de las 8 secciones (le faltan daños internos, sistemas
     * mecánicos detallados y detalles técnicos).
     */
    public function generar(Peritaje $peritaje)
    {
        $peritaje = Peritaje::conDetalleCompleto()->findOrFail($peritaje->id);

        $firma = $peritaje->archivos->firstWhere('categoria', 'FIRMA_INSPECTOR');
        $firmaPath = $firma ? Storage::disk('public')->path($firma->url) : null;

        $html = view('pdf.peritaje', [
            'p' => $peritaje,
            'firmaPath' => $firmaPath,
        ])->render();

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    public function nombreArchivo(Peritaje $peritaje): string
    {
        return "Peritaje_Orinoquia_{$peritaje->placa}.pdf";
    }
}
