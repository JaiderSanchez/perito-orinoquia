<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class TipoVehiculo extends Model
{
    use HasFactory, HasUuid; // <-- Aquí llamamos al Trait

    protected $table = 'tipos_vehiculo';
    public $timestamps = false; // Esta tabla no tiene created_at ni updated_at

    protected $fillable = [
        'codigo',
        'nombre',
        'icono',
        'descripcion',
        'orden',
        'activo'
    ];
}
