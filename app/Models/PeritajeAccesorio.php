<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PeritajeAccesorio extends Model
{
    use HasUuids;

    protected $table = 'peritaje_accesorios';
    protected $guarded = [];
    public function storeAccesorios(Request $request, $peritajeId)
{
    // Supongamos que recibes un arreglo como:
    // ['catalogo_accesorio_id' => 1, 'presente' => true, 'danado' => false, ...]
    $accesorios = $request->input('peritajeAccesorios');

    foreach ($accesorios as $catalogId => $data) {
        \App\Models\PeritajeAccesorio::updateOrCreate(
            [
                'peritaje_id' => $peritajeId,
                'catalogo_accesorio_id' => $catalogId
            ],
            [
                'presente' => $data['presente'] ?? false,
                'danado' => $data['danado'] ?? false,
                'costo_reparacion' => $data['costo_reparacion'] ?? 0,
                'comentario_dano' => $data['comentario_dano'] ?? null,
            ]
        );
    }

    return response()->json(['message' => 'Accesorios guardados correctamente']);
}
}
