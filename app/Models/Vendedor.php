<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasUuids;

    protected $table = 'vendedores';
    public $timestamps = false; // <--- Desactiva created_at y updated_at aquí

    protected $fillable = ['nombre', 'sucursal_id', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
