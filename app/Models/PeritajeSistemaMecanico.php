<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeSistemaMecanico extends Model
{
    use HasUuids;

    protected $fillable = ['peritaje_id', 'catalogo_sistema_id', 'estado', 'observaciones'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function catalogoSistema()
    {
        return $this->belongsTo(CatalogoSistemaMecanico::class, 'catalogo_sistema_id');
    }
}
