<?php

namespace App\Services;

use App\Models\Peritaje;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PeritajePdfService
{

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
