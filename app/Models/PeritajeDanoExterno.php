<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PeritajeDanoExterno extends Model
{
    use HasUuids;

    protected $table = 'peritaje_danos_externos'; // Con la 's' en danos
    protected $guarded = [];
}
