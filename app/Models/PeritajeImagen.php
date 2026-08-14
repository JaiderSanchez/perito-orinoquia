<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeritajeImagen extends Model
{
    use HasFactory;

    protected $table = 'peritaje_imagenes';

    protected $fillable = [
        'peritaje_id',
        'seccion',
        'item_id',
        'imagen_base64',
        'nombre_archivo',
    ];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }
}
