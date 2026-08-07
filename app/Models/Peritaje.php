<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Peritaje extends Model
{
    use HasUuids;

    protected $fillable = [
        'tipo_vehiculo_id', 'estado', 'inspector_id', 'sucursal_vendedor_id',
        'sucursal_inspeccion_id', 'vendedor_id', 'fecha_peritaje',
        'placa', 'marca', 'linea', 'modelo_anio', 'num_motor', 'num_chasis',
        'organismo_transito', 'kilometraje', 'tarjeta_operacion', 'configuracion_ejes',
        'numero_soat', 'entidad_emisora_soat', 'vence_soat', 'soat_al_dia',
        'numero_control_rtm', 'cda_emisor', 'vence_tecnico_mecanica', 'tecnico_mecanica_al_dia',
        'coincide_propietario_runt', 'tiene_embargos_o_alertas', 'restriccion_blindaje',
        'comentarios_siniestros', 'tipo_transmision', 'estado_transmision', 'comentarios_motor',
        'porcentaje_bateria', 'vida_util_bateria', 'costo_alistamiento', 'costo_reparacion',
        'tiempo_estimado_reparacion', 'estado_general_vehiculo', 'concepto_final',
        'comentarios_generales', 'score_estructura', 'score_carroceria', 'score_mecanica',
        'score_electrico', 'score_legal', 'firmado_en',
    ];

    protected $casts = [
        'fecha_peritaje' => 'datetime',
        'vence_soat' => 'date',
        'vence_tecnico_mecanica' => 'date',
        'soat_al_dia' => 'boolean',
        'tecnico_mecanica_al_dia' => 'boolean',
        'coincide_propietario_runt' => 'boolean',
        'tiene_embargos_o_alertas' => 'boolean',
        'firmado_en' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Peritaje $peritaje) {
            if (empty($peritaje->codigo)) {
                $siguiente = DB::selectOne("SELECT nextval('peritajes_codigo_seq') AS n")->n;
                $peritaje->codigo = 'PER-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function sucursalVendedor()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_vendedor_id');
    }

    public function sucursalInspeccion()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_inspeccion_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function accesorios()
    {
        return $this->hasMany(PeritajeAccesorio::class);
    }

    public function danosExternos()
    {
        return $this->hasMany(PeritajeDanoExterno::class);
    }

    public function danosInternos()
    {
        return $this->hasMany(PeritajeDanoInterno::class);
    }

    public function detallesTecnicos()
    {
        return $this->hasMany(PeritajeDetalleTecnico::class);
    }

    public function sistemasMecanicos()
    {
        return $this->hasMany(PeritajeSistemaMecanico::class);
    }

    public function compresionCilindros()
    {
        return $this->hasMany(PeritajeCompresionCilindro::class)->orderBy('numero_cilindro');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class);
    }

    public function historialEstados()
    {
        return $this->hasMany(PeritajeHistorialEstado::class)->orderByDesc('created_at');
    }

    public function scopeConDetalleCompleto($query)
    {
        return $query->with([
            'tipoVehiculo',
            'inspector',
            'sucursalVendedor',
            'sucursalInspeccion',
            'vendedor',
            'accesorios.catalogoAccesorio',
            'danosExternos.catalogoPieza',
            'danosInternos.catalogoZona',
            'detallesTecnicos.catalogoElemento',
            'sistemasMecanicos.catalogoSistema',
            'compresionCilindros',
            'archivos',
        ]);
    }

    public function cambiarEstado(string $nuevoEstado, ?int $usuarioId = null, ?string $comentario = null): void
    {
        $this->update(['estado' => $nuevoEstado]);
        $this->historialEstados()->create([
            'estado' => $nuevoEstado,
            'usuario_id' => $usuarioId,
            'comentario' => $comentario,
        ]);
    }

    public function firmaInspector()
    {
        return $this->archivos()->where('categoria', 'FIRMA_INSPECTOR')->latest('created_at')->first();
    }
}
