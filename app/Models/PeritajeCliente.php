<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeritajeCliente extends Model
{
    use HasFactory;

    protected $table = 'peritaje_clientes';

    protected $fillable = [
        'peritaje_id',
        'nombre_cliente',
        'documento_cliente',
        'telefono_cliente',
    ];
}
