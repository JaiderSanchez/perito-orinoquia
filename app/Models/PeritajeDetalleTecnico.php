<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeDetalleTecnico extends Model
{
    use HasUuids;

    protected $table = 'peritaje_detalles_tecnicos';
    protected $guarded = [];

    public function catalogoElemento()
    {
        return $this->belongsTo(CatalogoElementosTecnico::class, 'catalogo_elemento_id');
    }
}
