<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];
    protected $casts = ['value' => 'array'];

    public static function defaults(): array
    {
        return [
            'nombreEmpresa' => 'Servi-Centro CDA / Perito Orinoquia',
            'nit' => '', 'telefono' => '', 'direccion' => '', 'ciudad' => 'Yopal, Casanare',
            'emailEmpresa' => '', 'scoreInicialDefecto' => 100,
            'exigirFotosDocumentos' => true, 'modoOscuroPorDefecto' => false,
            'textoLegalPdf' => 'El presente peritaje es un diagnóstico visual, estético y mecánico del estado del vehículo al momento de la inspección. No compromete responsabilidad legal sobre fallas fortuitas posteriores ni vicios ocultos no detectables en banco.',
            'limiteUsuarios' => 50,
        ];
    }

    public static function configuration(): array
    {
        return array_replace(
            static::defaults(),
            static::where('key', 'configuration')->value('value') ?? []
        );
    }
}
