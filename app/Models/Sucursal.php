<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasUuids;

    protected $fillable = ['nombre', 'ciudad', 'activa'];
    protected $casts = ['activa' => 'boolean'];
}
