<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeritajeController extends Controller
{
    /**
     * Listar todos los peritajes con sus relaciones técnicas.
     */
    public function index()
    {
        try {
            $peritajes = Peritaje::with([
                'sucursalVendedor',
                'inspector',
                'accesorios',
                'danosExternos',
                'danosInternos',
                'detallesTecnicos',
                'sistemasMecanicos',
                'compresionCilindros'
            ])->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $peritajes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Mostrar un peritaje específico por ID con sus relaciones.
     */
    public function show($id)
    {
        try {
            $peritaje = Peritaje::with([
                'sucursalVendedor',
                'inspector',
                'accesorios',
                'danosExternos',
                'danosInternos',
                'detallesTecnicos',
                'sistemasMecanicos',
                'compresionCilindros'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $peritaje
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar un nuevo peritaje completo con sus relaciones.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // 1. Obtenemos los datos excluyendo las relaciones secundarias
            $data = $request->except([
                'accesorios',
                'danos_externos',
                'danos_internos',
                'detalles_tecnicos',
                'sistemas_mecanicos',
                'compresion_cilindros'
            ]);

            // 2. Asignamos automáticamente el inspector con el usuario autenticado actual
            $data['inspector_id'] = auth()->id();

            // 3. Valores por defecto temporales para evitar restricciones Not null violation en PostgreSQL
            $data['marca'] = $data['marca'] ?? 'N/A';
            $data['linea'] = $data['linea'] ?? 'N/A';
            $data['modelo_anio'] = $data['modelo_anio'] ?? 0;
            $data['num_motor'] = $data['num_motor'] ?? 'N/A';
            $data['num_chasis'] = $data['num_chasis'] ?? 'N/A';
            $data['organismo_transito'] = $data['organismo_transito'] ?? 'N/A';

            // 4. Creamos el registro principal del peritaje
            $peritaje = Peritaje::create($data);

            if ($request->has('accesorios')) {
                $peritaje->accesorios()->createMany($request->accesorios);
            }

            if ($request->has('danos_externos')) {
                $peritaje->danosExternos()->createMany($request->danos_externos);
            }

            if ($request->has('danos_internos')) {
                $peritaje->danosInternos()->createMany($request->danos_internos);
            }

            if ($request->has('detalles_tecnicos')) {
                $peritaje->detallesTecnicos()->createMany($request->detalles_tecnicos);
            }

            if ($request->has('sistemas_mecanicos')) {
                $peritaje->sistemasMecanicos()->createMany($request->sistemas_mecanicos);
            }

            if ($request->has('compresion_cilindros')) {
                $peritaje->compresionCilindros()->createMany($request->compresion_cilindros);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peritaje creado exitosamente.',
                'data' => $peritaje->load([
                    'sucursalVendedor',
                    'inspector',
                    'accesorios',
                    'danosExternos',
                    'danosInternos',
                    'detallesTecnicos',
                    'sistemasMecanicos',
                    'compresionCilindros'
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el peritaje.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un peritaje existente y sincronizar sus relaciones.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $peritaje = Peritaje::findOrFail($id);

            $data = $request->except([
                'accesorios',
                'danos_externos',
                'danos_internos',
                'detalles_tecnicos',
                'sistemas_mecanicos',
                'compresion_cilindros'
            ]);

            // Opcional: si deseas actualizar el inspector al modificarlo con el usuario en sesión actual
            // $data['inspector_id'] = auth()->id();

            $peritaje->update($data);

            if ($request->has('accesorios')) {
                $peritaje->accesorios()->delete();
                $peritaje->accesorios()->createMany($request->accesorios);
            }

            if ($request->has('danos_externos')) {
                $peritaje->danosExternos()->delete();
                $peritaje->danosExternos()->createMany($request->danos_externos);
            }

            if ($request->has('danos_internos')) {
                $peritaje->danosInternos()->delete();
                $peritaje->danosInternos()->createMany($request->danos_internos);
            }

            if ($request->has('detalles_tecnicos')) {
                $peritaje->detallesTecnicos()->delete();
                $peritaje->detallesTecnicos()->createMany($request->detalles_tecnicos);
            }

            if ($request->has('sistemas_mecanicos')) {
                $peritaje->sistemasMecanicos()->delete();
                $peritaje->sistemasMecanicos()->createMany($request->sistemas_mecanicos);
            }

            if ($request->has('compresion_cilindros')) {
                $peritaje->compresionCilindros()->delete();
                $peritaje->compresionCilindros()->createMany($request->compresion_cilindros);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peritaje actualizado exitosamente.',
                'data' => $peritaje->load([
                    'sucursalVendedor',
                    'inspector',
                    'accesorios',
                    'danosExternos',
                    'danosInternos',
                    'detallesTecnicos',
                    'sistemasMecanicos',
                    'compresionCilindros'
                ])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el peritaje.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
