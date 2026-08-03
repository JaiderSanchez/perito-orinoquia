<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasUuids;

    protected $table = 'sucursales';
    public $timestamps = false; // <--- Desactiva created_at y updated_at aquí también

    protected $fillable = ['nombre', 'ciudad', 'activa'];
    protected $casts = ['activa' => 'boolean'];
}
