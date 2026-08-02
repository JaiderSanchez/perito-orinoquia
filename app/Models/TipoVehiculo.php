<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TipoVehiculo extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['codigo', 'nombre', 'icono', 'descripcion', 'orden', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function accesorios()
    {
        return $this->hasMany(CatalogoAccesorio::class)->where('activo', true)->orderBy('orden');
    }

    public function piezasCarroceria()
    {
        return $this->hasMany(CatalogoPiezaCarroceria::class)->where('activo', true)->orderBy('orden');
    }

    public function zonasCabina()
    {
        return $this->hasMany(CatalogoZonaCabina::class)->where('activo', true)->orderBy('orden');
    }

    public function sistemasMecanicos()
    {
        return $this->hasMany(CatalogoSistemaMecanico::class)->where('activo', true)->orderBy('orden');
    }
}
    