<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeDetalleTecnico extends Model
{
    use HasUuids;

    protected $fillable = ['peritaje_id', 'catalogo_elemento_id', 'danado', 'comentario', 'costo'];
    protected $casts = ['danado' => 'boolean'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function catalogoElemento()
    {
        return $this->belongsTo(CatalogoElementoTecnico::class, 'catalogo_elemento_id');
    }

    public function imagen()
    {
        return $this->hasOne(Archivo::class, 'entidad_relacionada_id')->where('categoria', 'FOTO_DETALLE_TECNICO');
    }
}
