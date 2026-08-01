<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Peritaje;
use Illuminate\Support\Str;

class PeritajeController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar los datos mínimos requeridos
        $request->validate([
            'tipo_vehiculo_id' => 'required|uuid',
            'placa' => 'required|string|max:10',
            'cliente_nombre' => 'required|string',
            // Puedes agregar más validaciones aquí
        ]);

        try {
            // 2. Iniciamos la transacción. ¡Si algo falla aquí adentro, nada se guarda!
            DB::beginTransaction();

            // 3. Crear el Peritaje Principal
            $peritaje = Peritaje::create([
                // Generamos un código de reporte único, ej: PER-X89JD
                'codigo_reporte' => 'PER-' . strtoupper(Str::random(5)),
                'tipo_vehiculo_id' => $request->tipo_vehiculo_id,
                'usuario_id' => $request->user()->id, // El usuario logueado en Sanctum
                'estado' => 'completado',
                'placa' => strtoupper($request->placa),
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'cliente_nombre' => $request->cliente_nombre,
                // ... Mapear el resto de campos principales aquí
            ]);

            // 4. Guardar Accesorios (Si React envía un array llamado 'accesorios')
            if ($request->has('accesorios') && is_array($request->accesorios)) {
                $accesoriosData = [];
                foreach ($request->accesorios as $acc) {
                    $accesoriosData[] = [
                        'id' => (string) Str::uuid(),
                        'peritaje_id' => $peritaje->id,
                        'catalogo_accesorio_id' => $acc['catalogo_id'],
                        'estado' => $acc['estado'], // 'bueno', 'malo', 'regular', 'no_aplica'
                        'observaciones' => $acc['observaciones'] ?? null,
                    ];
                }
                // Insertamos todos los accesorios de golpe por rendimiento
                DB::table('peritaje_accesorios')->insert($accesoriosData);
            }

            // 5. Guardar Sistemas Mecánicos (Misma lógica)
            if ($request->has('sistemas_mecanicos') && is_array($request->sistemas_mecanicos)) {
                $sistemasData = [];
                foreach ($request->sistemas_mecanicos as $sis) {
                    $sistemasData[] = [
                        'id' => (string) Str::uuid(),
                        'peritaje_id' => $peritaje->id,
                        'catalogo_sistema_id' => $sis['catalogo_id'],
                        'estado' => $sis['estado'],
                        'nivel_desgaste' => $sis['nivel_desgaste'] ?? null,
                    ];
                }
                DB::table('peritaje_sistemas_mecanicos')->insert($sistemasData);
            }

            // Aquí repetirías el bloque 'if' para carrocería, daños, etc.

            // 6. Si todo salió bien, confirmamos los cambios en la base de datos
            DB::commit();

            return response()->json([
                'message' => 'Peritaje guardado exitosamente',
                'codigo_reporte' => $peritaje->codigo_reporte,
                'peritaje_id' => $peritaje->id
            ], 201);

        } catch (\Exception $e) {
            // 7. ¡Error! Revertimos todos los cambios para no dejar basura en la base de datos
            DB::rollBack();

            return response()->json([
                'message' => 'Error al guardar el peritaje',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
