<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class CatalogoAccesorio extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'catalogo_accesorios';
    public $timestamps = false;

    // Ocultamos columnas innecesarias para que el JSON sea más ligero para React
    protected $hidden = ['created_at', 'updated_at'];

    // Casteamos opciones a array si usamos JSONB
    protected $casts = [
        'opciones' => 'array',
    ];
}
