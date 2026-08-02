<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeAccesorio extends Model
{
    use HasUuids;

    protected $fillable = ['peritaje_id', 'catalogo_accesorio_id', 'presente', 'seleccion', 'danado', 'costo_reparacion', 'comentario_dano'];
    protected $casts = ['presente' => 'boolean', 'danado' => 'boolean'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function catalogoAccesorio()
    {
        return $this->belongsTo(CatalogoAccesorio::class);
    }
}
