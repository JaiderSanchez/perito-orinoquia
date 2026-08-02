<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeDanoExterno extends Model
{
    use HasUuids;

    protected $fillable = ['peritaje_id', 'catalogo_pieza_id', 'tipo_hallazgo', 'micras', 'comentario'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function catalogoPieza()
    {
        return $this->belongsTo(CatalogoPiezaCarroceria::class, 'catalogo_pieza_id');
    }

    public function foto()
    {
        return $this->hasOne(Archivo::class, 'entidad_relacionada_id')->where('categoria', 'FOTO_DANO_EXTERNO');
    }
}
