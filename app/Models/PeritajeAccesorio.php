<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeAccesorio extends Model
{
    use HasUuids;

    protected $table = 'peritaje_accesorios';
    protected $guarded = [];
}
