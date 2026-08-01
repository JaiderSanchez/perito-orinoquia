<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Peritaje extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'peritajes';

    protected $fillable = [
        'codigo_reporte', // Este lo podemos autogenerar
        'tipo_vehiculo_id',
        'usuario_id', // El inspector
        'sucursal_id', // Si tienes sucursales, si no, puede quedar null
        'vendedor_id', // Si aplica
        'estado', // 'borrador', 'completado', 'cancelado'

        // Datos básicos del vehículo
        'placa',
        'marca',
        'linea',
        'modelo',
        'color',
        'kilometraje',
        'cilindraje',

        // Datos del cliente
        'cliente_nombre',
        'cliente_documento',
        'cliente_telefono',
        'cliente_email',

        // Resultados globales
        'calificacion_general',
        'aprobado',
        'observaciones_generales'
    ];
}
