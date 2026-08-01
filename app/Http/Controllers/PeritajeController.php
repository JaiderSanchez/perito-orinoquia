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
            'tipo_vehiculo_id' => 'required|exists:tipos_vehiculo,id',
            'placa' => 'required|string|max:10',
            'cliente_nombre' => 'required|string',
        ]);

        try {
            // 2. Iniciamos la transacción. ¡Si algo falla aquí adentro, nada se guarda!
            DB::beginTransaction();

            // 3. Crear el Peritaje Principal
            // (Asegúrate de ajustar los campos según los que ya tengas en tu tabla 'peritajes')
            $peritaje = Peritaje::create([
                'codigo_reporte' => 'PER-' . strtoupper(Str::random(5)),
                'tipo_vehiculo_id' => $request->tipo_vehiculo_id,
                'usuario_id' => $request->user()?->id ?? 1,
                'estado' => 'completado',
                'placa' => strtoupper($request->placa),
                'marca' => $request->marca ?? null,
                'modelo' => $request->modelo ?? null,
                'cliente_nombre' => $request->cliente_nombre,
            ]);

            // 4. Guardar Accesorios
            if ($request->has('accesorios') && is_array($request->accesorios)) {
                $accesoriosData = [];
                foreach ($request->accesorios as $acc) {
                    $accesoriosData[] = [
                        'peritaje_id' => $peritaje->id,
                        'catalogo_accesorio_id' => $acc['catalogo_id'],
                        'presente' => $acc['presente'] ?? true,
                        'danado' => $acc['danado'] ?? false,
                        'costo_reparacion' => $acc['costo_reparacion'] ?? null,
                        'comentario_dano' => $acc['observaciones'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('peritaje_accesorios')->insert($accesoriosData);
            }

            // 5. Guardar Sistemas Mecánicos
            if ($request->has('sistemas_mecanicos') && is_array($request->sistemas_mecanicos)) {
                $sistemasData = [];
                foreach ($request->sistemas_mecanicos as $sis) {
                    $sistemasData[] = [
                        'peritaje_id' => $peritaje->id,
                        'catalogo_sistema_id' => $sis['catalogo_id'],
                        'estado' => $sis['estado'] ?? 'BUENO', // 'BUENO', 'REGULAR', 'MALO'
                        'observaciones' => $sis['observaciones'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('peritaje_sistemas_mecanicos')->insert($sistemasData);
            }

            // 6. Registrar en el historial de estados
            DB::table('peritaje_historial_estados')->insert([
                'peritaje_id' => $peritaje->id,
                'estado' => 'completado',
                'usuario_id' => $request->user()?->id ?? 1,
                'comentario' => 'Peritaje registrado exitosamente desde la API',
                'created_at' => now(),
            ]);

            // 7. Si todo salió bien, confirmamos los cambios en la base de datos
            DB::commit();

            return response()->json([
                'message' => 'Peritaje guardado exitosamente',
                'codigo_reporte' => $peritaje->codigo_reporte,
                'peritaje_id' => $peritaje->id
            ], 201);

        } catch (\Exception $e) {
            // 8. ¡Error! Revertimos todos los cambios para no dejar registros huérfanos
            DB::rollBack();

            return response()->json([
                'message' => 'Error al guardar el peritaje',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
