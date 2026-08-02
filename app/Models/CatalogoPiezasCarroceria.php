<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CatalogoElementoTecnico extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['codigo', 'nombre', 'orden', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
