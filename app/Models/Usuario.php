<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuid;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuid;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'password_hash',
        'rol',
        'sucursal_id',
        'activo',
        'ultimo_login'
    ];

    // Para que el login de Laravel entienda que la contraseña está en 'password_hash'
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
