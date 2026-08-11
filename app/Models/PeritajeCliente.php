<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeritajeCliente extends Model
{
    use HasFactory;

    protected $table = 'peritaje_clientes';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'peritaje_id',
        'nombre_cliente',
        'documento_cliente',
        'telefono_cliene' // Asegúrate que en tu base de datos se llame así, o cámbialo a telefono_cliente en ambos lados
    ];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class, 'peritaje_id');
    }
}
