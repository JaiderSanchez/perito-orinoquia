<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeAccesorio extends Model
{
    use HasUuids;

    protected $table = 'peritaje_accesorios';

    protected $guarded = [];

    protected $casts = [
        'presente' => 'boolean',
        'danado' => 'boolean',
        'costo_reparacion' => 'integer',
    ];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function catalogoAccesorio()
    {
        return $this->belongsTo(CatalogoAccesorio::class, 'catalogo_accesorio_id');
    }
}
