<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeritajeCliente extends Model
{
    use HasFactory, HasUuids; // <--- Añade HasUuids aquí

    protected $table = 'peritaje_clientes';

    // Opcional si usas HasUuids, pero asegura que Laravel sepa que no es autoincremental numérico
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'peritaje_id',
        'nombre_cliente',
        'documento_cliente',
        'telefono_cliene',
    ];
}
