<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeDanoInterno extends Model
{
    use HasUuids;

    protected $fillable = ['peritaje_id', 'catalogo_zona_id', 'estado', 'desgaste', 'comentario'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function catalogoZona()
    {
        return $this->belongsTo(CatalogoZonaCabina::class, 'catalogo_zona_id');
    }
}
