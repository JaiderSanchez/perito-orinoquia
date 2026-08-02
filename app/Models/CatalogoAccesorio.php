<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CatalogoAccesorio extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['tipo_vehiculo_id', 'codigo', 'nombre', 'tipo_campo', 'opciones', 'valor_por_defecto', 'orden', 'activo'];
    protected $casts = ['opciones' => 'array', 'activo' => 'boolean'];
}
