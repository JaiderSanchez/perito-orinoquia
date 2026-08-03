<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CatalogoSistemaMecanico extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['tipo_vehiculo_id', 'codigo', 'nombre', 'orden', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
