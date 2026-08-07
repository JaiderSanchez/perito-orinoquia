<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeDanoInterno extends Model
{
    use HasUuids;

    protected $table = 'peritaje_danos_internos'; // Con la 's' en danos
    protected $guarded = [];
}
