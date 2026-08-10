<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PeritajeSistemaMecanico extends Model
{
    use HasUuids;

    protected $table = 'peritaje_sistemas_mecanicos';
    protected $guarded = [];

    public function store(Request $request)
{
    // 1. Guardar o actualizar el peritaje principal (asegúrate de incluir los campos en el fillable de Peritaje)
    $peritaje = Peritaje::create($request->only([
        'tipoVehiculo',
        'kilometraje',
        'tipoTransmision',
        'traccion',
        'estadoTransmision',
        'comentariosMotor',
        // ... otros campos generales del peritaje ...
    ]));

    // 2. Procesar y guardar los sistemas mecánicos enviados desde Motor.jsx
    $sistemasMecanicos = $request->input('sistemasMecanicos');
    if ($sistemasMecanicos && is_array($sistemasMecanicos)) {
        foreach ($sistemasMecanicos as $key => $values) {
            // Verificamos que al menos tenga estado u observaciones para no guardar registros vacíos
            if (!empty($values['estado']) || !empty($values['observaciones'])) {
                PeritajeSistemaMecanico::create([
                    'peritaje_id' => $peritaje->id,
                    'sistema_key' => $key, // Asegúrate de tener esta columna o 'catalogo_sistema_id' en tu tabla
                    'estado' => $values['estado'] ?? null,
                    'observaciones' => $values['observaciones'] ?? null,
                ]);
            }
        }
    }

    // 3. Procesar las lecturas de compresión de cilindros dinámicas
    for ($i = 1; $i <= 4; $i++) {
        $valorCompresion = $request->input("compresionCil{$i}");
        if (!is_null($valorCompresion) && $valorCompresion !== '') {
            $peritaje->compresionCilindros()->create([
                'numero_cilindro' => $i,
                'presion_psi' => $valorCompresion,
            ]);
        }
    }

    return response()->json([
        'message' => 'Peritaje de motor guardado exitosamente',
        'data' => $peritaje->load(['sistemasMecanicos', 'compresionCilindros'])
    ], 201);
}

}
