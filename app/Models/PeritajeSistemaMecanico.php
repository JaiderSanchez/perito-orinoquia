<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeSistemaMecanico extends Model
{
    use HasUuids;

    protected $table = 'peritaje_sistemas_mecanicos';
    protected $guarded = [];
}
