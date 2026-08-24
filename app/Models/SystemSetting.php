<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];
    protected $casts = ['value' => 'array'];

    public static function configuration(): array
    {
        return static::where('key', 'configuration')->value('value') ?? [];
    }
}
