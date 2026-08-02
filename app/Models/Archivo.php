<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Archivo extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = ['peritaje_id', 'categoria', 'entidad_relacionada_id', 'nombre_original', 'mime_type', 'url', 'tamanio_bytes', 'subido_por'];
    protected $casts = ['created_at' => 'datetime'];

    // "url" guarda la ruta relativa en el disco "public" (ej. peritajes/xxx/firma.png);
    // este accesor arma la URL pública completa para que el frontend la use directo.
    public function getUrlPublicaAttribute(): string
    {
        return Storage::disk('public')->url($this->url);
    }

    public function peritaje()
    {
        return $this->belongsTo(Peritaje::class);
    }
}
