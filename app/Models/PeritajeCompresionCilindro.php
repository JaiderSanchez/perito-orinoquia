<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeCompresionCilindro extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['peritaje_id', 'numero_cilindro', 'presion_psi'];

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }
}
