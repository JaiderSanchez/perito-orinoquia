<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeHistorialEstado extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['peritaje_id', 'estado', 'usuario_id', 'comentario'];
    protected $casts = ['created_at' => 'datetime'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
