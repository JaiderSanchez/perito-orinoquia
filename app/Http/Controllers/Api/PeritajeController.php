<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'sucursalInspeccion',
                'vendedor',
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
                'sucursalInspeccion',
                'vendedor',
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
     * Registrar un nuevo peritaje completo con sus relaciones y archivos adjuntos.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // 1. Obtenemos los datos excluyendo las relaciones secundarias y archivos directos
            $data = $request->except([
                'accesorios',
                'danos_externos',
                'danosExternos',
                'danos_internos',
                'danosInternos',
                'detalles_tecnicos',
                'detallesTecnicos',
                'sistemas_mecanicos',
                'sistemasMecanicos',
                'compresion_cilindros',
                'compresionCilindros',
                'archivo_soat',
                'archivoSoat',
                'archivo_tecnico_mecanica',
                'archivoTecnicoMecanica'
            ]);

            // 2. Mapeo explícito de nombres de campos en camelCase a snake_case si llegan desde el frontend
            $mapeoCampos = [
                'sucursalVendedorId' => 'sucursal_vendedor_id',
                'sucursalInspeccionId' => 'sucursal_inspeccion_id',
                'vendedorId' => 'vendedor_id',
                'tipoVehiculo' => 'tipo_vehiculo',
                'modeloAnio' => 'modelo_anio',
                'numMotor' => 'num_motor',
                'numChasis' => 'num_chasis',
                'clienteNombre' => 'cliente_nombre',
                'clienteDocumento' => 'cliente_documento',
                'clienteTelefono' => 'cliente_telefono',
                'soatAlDia' => 'soat_al_dia',
                'venceSoat' => 'vence_soat',
                'tecnicoMecanicaAlDia' => 'tecnico_mecanica_al_dia',
                'venceTecnicoMecanica' => 'vence_tecnico_mecanica',
            ];

            foreach ($mapeoCampos as $keyCamel => $keySnake) {
                if ($request->has($keyCamel) && !isset($data[$keySnake])) {
                    $data[$keySnake] = $request->input($keyCamel);
                }
            }

            // 3. Manejo de archivos subidos (SOAT y Técnico Mecánica)
            if ($request->hasFile('archivo_soat')) {
                $data['archivo_soat'] = $request->file('archivo_soat')->store('peritajes/soat', 'public');
            } elseif ($request->hasFile('archivoSoat')) {
                $data['archivo_soat'] = $request->file('archivoSoat')->store('peritajes/soat', 'public');
            }

            if ($request->hasFile('archivo_tecnico_mecanica')) {
                $data['archivo_tecnico_mecanica'] = $request->file('archivo_tecnico_mecanica')->store('peritajes/rtm', 'public');
            } elseif ($request->hasFile('archivoTecnicoMecanica')) {
                $data['archivo_tecnico_mecanica'] = $request->file('archivoTecnicoMecanica')->store('peritajes/rtm', 'public');
            }

            // 4. Asignamos automáticamente el inspector con el usuario autenticado actual
            $data['inspector_id'] = auth()->id();

            // 5. Valores por defecto temporales para evitar restricciones Not null violation en PostgreSQL
            $data['marca'] = $data['marca'] ?? 'N/A';
            $data['linea'] = $data['linea'] ?? 'N/A';
            $data['modelo_anio'] = $data['modelo_anio'] ?? 0;
            $data['num_motor'] = $data['num_motor'] ?? 'N/A';
            $data['num_chasis'] = $data['num_chasis'] ?? 'N/A';
            $data['organismo_transito'] = $data['organismo_transito'] ?? 'N/A';

            $peritaje = Peritaje::create($data);

            $accesorios = $request->input('peritaje_accesorios') ?? $request->input('accesoriosList');
            if (is_string($accesorios)) {
                $accesorios = json_decode($accesorios, true);
            }

            if ($accesorios && is_array($accesorios)) {
                $peritaje->accesorios()->delete(); // Por si acaso en un reintento
                $mapeadosAccesorios = [];

                foreach ($accesorios as $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $catId = $dataItem['catalogo_accesorio_id'] ?? $dataItem['id'] ?? null;

                    if ($catId && (preg_match('/^[0-9a-fA-F-]{36}$/', $catId) || is_numeric($catId))) {
                        $mapeadosAccesorios[] = [
                            'catalogo_accesorio_id' => $catId,
                            'presente' => $dataItem['presente'] ?? 0,
                            'seleccion' => $dataItem['seleccion'] ?? 0,
                            'danado' => $dataItem['danado'] ?? 0,
                            'costo_reparacion' => $dataItem['costo_reparacion'] ?? 0,
                            'comentario_dano' => $dataItem['comentario_dano'] ?? null,
                        ];
                    }
                }

                if (!empty($mapeadosAccesorios)) {
                    $peritaje->accesorios()->createMany($mapeadosAccesorios);
                }
            }

            $danosExternos = $request->input('danos_externos') ?? $request->input('danosExternos');
            if ($danosExternos && is_array($danosExternos)) {
                $peritaje->danosExternos()->createMany($danosExternos);
            }

            $danosInternos = $request->input('danos_internos') ?? $request->input('danosInternos');
            if ($danosInternos && is_array($danosInternos)) {
                $peritaje->danosInternos()->createMany($danosInternos);
            }

            $detallesTecnicos = $request->input('detalles_tecnicos') ?? $request->input('detallesTecnicos');
            if ($detallesTecnicos && is_array($detallesTecnicos)) {
                $peritaje->detallesTecnicos()->createMany($detallesTecnicos);
            }

            $sistemasMecanicos = json_decode($request->input('sistemas_mecanicos', '[]'), true) ?: $request->input('sistemasMecanicos');
            if ($sistemasMecanicos && is_array($sistemasMecanicos)) {
                $peritaje->sistemasMecanicos()->delete();
                $mapeados = [];
                foreach ($sistemasMecanicos as $key => $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $catId = $dataItem['catalogo_sistema_id'] ?? $dataItem['id'] ?? null;

                    if ($catId && preg_match('/^[0-9a-fA-F-]{36}$/', $catId)) {
                        $mapeados[] = [
                            'catalogo_sistema_id' => $catId,
                            'estado' => $dataItem['estado'] ?? null,
                            'observaciones' => $dataItem['observaciones'] ?? null,
                        ];
                    }
                }
                if (!empty($mapeados)) {
                    $peritaje->sistemasMecanicos()->createMany($mapeados);
                }
            }

            $peritaje->compresionCilindros()->delete();
            $cilindrosMapeados = [];
            $compresionCilindros = $request->input('compresion_cilindros') ?? $request->input('compresionCilindros');
            if (is_string($compresionCilindros)) {
                $compresionCilindros = json_decode($compresionCilindros, true);
            }

            if ($compresionCilindros && is_array($compresionCilindros)) {
                foreach ($compresionCilindros as $index => $item) {
                    $cilindrosMapeados[] = [
                        'numero_cilindro' => is_array($item) ? ($item['numero_cilindro'] ?? $index + 1) : ($index + 1),
                        'presion_psi' => is_array($item) ? ($item['presion_psi'] ?? $item['valor_psi'] ?? $item['psi'] ?? 0) : $item,
                    ];
                }
            } else {
                for ($i = 1; $i <= 6; $i++) {
                    $valorCil = $request->input("compresionCil{$i}") ?? $request->input("compresion_cil_{$i}");
                    if ($valorCil !== null && $valorCil !== '') {
                        $cilindrosMapeados[] = [
                            'numero_cilindro' => $i,
                            'presion_psi' => $valorCil,
                        ];
                    }
                }
            }
            if (!empty($cilindrosMapeados)) {
                $peritaje->compresionCilindros()->createMany($cilindrosMapeados);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peritaje creado exitosamente.',
                'data' => $peritaje->load([
                    'sucursalVendedor',
                    'sucursalInspeccion',
                    'vendedor',
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
     * Actualizar un peritaje existente y sincronizar sus relaciones y archivos.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $peritaje = Peritaje::findOrFail($id);

            $data = $request->except([
                'accesorios',
                'danos_externos',
                'danosExternos',
                'danos_internos',
                'danosInternos',
                'detalles_tecnicos',
                'detallesTecnicos',
                'sistemas_mecanicos',
                'sistemasMecanicos',
                'compresion_cilindros',
                'compresionCilindros',
                'archivo_soat',
                'archivoSoat',
                'archivo_tecnico_mecanica',
                'archivoTecnicoMecanica'
            ]);

            // Mapeo explícito de campos en camelCase a snake_case
            $mapeoCampos = [
                'sucursalVendedorId' => 'sucursal_vendedor_id',
                'sucursalInspeccionId' => 'sucursal_inspeccion_id',
                'vendedorId' => 'vendedor_id',
                'tipoVehiculo' => 'tipo_vehiculo',
                'modeloAnio' => 'modelo_anio',
                'numMotor' => 'num_motor',
                'numChasis' => 'num_chasis',
                'clienteNombre' => 'cliente_nombre',
                'clienteDocumento' => 'cliente_documento',
                'clienteTelefono' => 'cliente_telefono',
                'soatAlDia' => 'soat_al_dia',
                'venceSoat' => 'vence_soat',
                'tecnicoMecanicaAlDia' => 'tecnico_mecanica_al_dia',
                'venceTecnicoMecanica' => 'vence_tecnico_mecanica',
            ];

            foreach ($mapeoCampos as $keyCamel => $keySnake) {
                if ($request->has($keyCamel) && !isset($data[$keySnake])) {
                    $data[$keySnake] = $request->input($keyCamel);
                }
            }

            // Manejo de actualización de archivos
            if ($request->hasFile('archivo_soat')) {
                if ($peritaje->archivo_soat) {
                    Storage::disk('public')->delete($peritaje->archivo_soat);
                }
                $data['archivo_soat'] = $request->file('archivo_soat')->store('peritajes/soat', 'public');
            } elseif ($request->hasFile('archivoSoat')) {
                if ($peritaje->archivo_soat) {
                    Storage::disk('public')->delete($peritaje->archivo_soat);
                }
                $data['archivo_soat'] = $request->file('archivoSoat')->store('peritajes/soat', 'public');
            }

            if ($request->hasFile('archivo_tecnico_mecanica')) {
                if ($peritaje->archivo_tecnico_mecanica) {
                    Storage::disk('public')->delete($peritaje->archivo_tecnico_mecanica);
                }
                $data['archivo_tecnico_mecanica'] = $request->file('archivo_tecnico_mecanica')->store('peritajes/rtm', 'public');
            } elseif ($request->hasFile('archivoTecnicoMecanica')) {
                if ($peritaje->archivo_tecnico_mecanica) {
                    Storage::disk('public')->delete($peritaje->archivo_tecnico_mecanica);
                }
                $data['archivo_tecnico_mecanica'] = $request->file('archivoTecnicoMecanica')->store('peritajes/rtm', 'public');
            }

            $peritaje->update($data);

            // 1. ACCESORIOS (Con validación de UUID)
            $accesorios = $request->input('accesoriosList');
            if (is_string($accesorios)) {
                $accesorios = json_decode($accesorios, true);
            }
            if ($accesorios && is_array($accesorios)) {
                $peritaje->accesorios()->delete();
                $mapeados = [];
                foreach ($accesorios as $item) {
                    $catId = $item['catalogo_accesorio_id'] ?? $item['id'] ?? null;
                    if ($catId && (preg_match('/^[0-9a-fA-F-]{36}$/', $catId) || is_numeric($catId))) {
                        $mapeados[] = [
                            'catalogo_accesorio_id' => $catId,
                            'presente' => isset($item['presente']) ? (int) $item['presente'] : 0,
                            'seleccion' => isset($item['seleccion']) ? (int) $item['seleccion'] : 0,
                            'danado' => isset($item['danado']) ? (int) $item['danado'] : 0,
                            'costo_reparacion' => isset($item['costo_reparacion']) ? (int) $item['costo_reparacion'] : 0,
                            'comentario_dano' => isset($item['comentario_dano']) ? $item['comentario_dano'] : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (!empty($mapeados)) {
                    $peritaje->accesorios()->createMany($mapeados);
                }
            }

            // 2. DAÑOS EXTERNOS
            $danosExternos = $request->input('danos_externos') ?? $request->input('danosExternos');
            if (is_string($danosExternos)) {
                $danosExternos = json_decode($danosExternos, true);
            }
            if ($danosExternos && is_array($danosExternos)) {
                $peritaje->danosExternos()->delete();
                $mapeados = [];
                foreach ($danosExternos as $item) {
                    $catId = $item['catalogo_pieza_id'] ?? $item['id'] ?? null;
                    if ($catId && (preg_match('/^[0-9a-fA-F-]{36}$/', $catId) || is_numeric($catId))) {
                        $mapeados[] = [
                            'catalogo_pieza_id' => $catId,
                            'tipo_dano' => $item['tipo_dano'] ?? $item['tipoDano'] ?? null,
                            'observaciones' => $item['observaciones'] ?? null,
                        ];
                    }
                }
                if (!empty($mapeados)) {
                    $peritaje->danosExternos()->createMany($mapeados);
                }
            }

            // 3. DAÑOS INTERNOS
            $danosInternos = $request->input('danos_internos') ?? $request->input('danosInternos');
            if (is_string($danosInternos)) {
                $danosInternos = json_decode($danosInternos, true);
            }
            if ($danosInternos && is_array($danosInternos)) {
                $peritaje->danosInternos()->delete();
                $mapeados = [];
                foreach ($danosInternos as $item) {
                    $catId = $item['catalogo_zona_id'] ?? $item['id'] ?? null;
                    if ($catId && (preg_match('/^[0-9a-fA-F-]{36}$/', $catId) || is_numeric($catId))) {
                        $mapeados[] = [
                            'catalogo_zona_id' => $catId,
                            'estado' => $item['estado'] ?? null,
                            'observaciones' => $item['observaciones'] ?? null,
                        ];
                    }
                }
                if (!empty($mapeados)) {
                    $peritaje->danosInternos()->createMany($mapeados);
                }
            }

            // 4. DETALLES TÉCNICOS
            $detallesTecnicos = $request->input('detalles_tecnicos') ?? $request->input('detallesTecnicos');
            if (is_string($detallesTecnicos)) {
                $detallesTecnicos = json_decode($detallesTecnicos, true);
            }
            if ($detallesTecnicos && is_array($detallesTecnicos)) {
                $peritaje->detallesTecnicos()->delete();
                $mapeados = [];
                foreach ($detallesTecnicos as $item) {
                    $catId = $item['catalogo_elemento_id'] ?? $item['id'] ?? null;
                    if ($catId && (preg_match('/^[0-9a-fA-F-]{36}$/', $catId) || is_numeric($catId))) {
                        $mapeados[] = [
                            'catalogo_elemento_id' => $catId,
                            'estado' => $item['estado'] ?? null,
                            'observaciones' => $item['observaciones'] ?? null,
                        ];
                    }
                }
                if (!empty($mapeados)) {
                    $peritaje->detallesTecnicos()->createMany($mapeados);
                }
            }

            // 5. SISTEMAS MECÁNICOS
            $sistemasMecanicos = $request->input('sistemas_mecanicos') ?? $request->input('sistemasMecanicos');
            if (is_string($sistemasMecanicos)) {
                $sistemasMecanicos = json_decode($sistemasMecanicos, true);
            }

            if ($sistemasMecanicos && is_array($sistemasMecanicos)) {
                $peritaje->sistemasMecanicos()->delete();
                $mapeados = [];

                foreach ($sistemasMecanicos as $key => $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $catId = $dataItem['catalogo_sistema_id'] ?? $dataItem['id'] ?? null;

                    if ($catId && preg_match('/^[0-9a-fA-F-]{36}$/', $catId)) {
                        $mapeados[] = [
                            'catalogo_sistema_id' => $catId,
                            'estado' => $dataItem['estado'] ?? null,
                            'observaciones' => $dataItem['observaciones'] ?? null,
                        ];
                    }
                }

                if (!empty($mapeados)) {
                    $peritaje->sistemasMecanicos()->createMany($mapeados);
                }
            }

            // 6. COMPRESIÓN CILINDROS (Soporta formato plano y formato de array)
            $peritaje->compresionCilindros()->delete();
            $cilindrosMapeados = [];

            $compresionCilindros = $request->input('compresion_cilindros') ?? $request->input('compresionCilindros');
            if (is_string($compresionCilindros)) {
                $compresionCilindros = json_decode($compresionCilindros, true);
            }

            if ($compresionCilindros && is_array($compresionCilindros)) {
                foreach ($compresionCilindros as $index => $item) {
                    $cilindrosMapeados[] = [
                        'numero_cilindro' => is_array($item) ? ($item['numero_cilindro'] ?? $index + 1) : ($index + 1),
                        'presion_psi' => is_array($item) ? ($item['presion_psi'] ?? $item['valor_psi'] ?? $item['valor'] ?? $item['psi'] ?? 0) : $item,
                    ];
                }
            } else {
                for ($i = 1; $i <= 6; $i++) {
                    $valorCil = $request->input("compresionCil{$i}") ?? $request->input("compresion_cil_{$i}");
                    if ($valorCil !== null && $valorCil !== '') {
                        $cilindrosMapeados[] = [
                            'numero_cilindro' => $i,
                            'presion_psi' => $valorCil,
                        ];
                    }
                }
            }

            if (!empty($cilindrosMapeados)) {
                foreach ($cilindrosMapeados as $cilindroData) {
                    \App\Models\PeritajeCompresionCilindro::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'peritaje_id' => $peritaje->id,
                        'numero_cilindro' => $cilindroData['numero_cilindro'],
                        'presion_psi' => $cilindroData['presion_psi'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peritaje actualizado exitosamente.',
                'data' => $peritaje->load([
                    'sucursalVendedor',
                    'sucursalInspeccion',
                    'vendedor',
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

    /**
     * Eliminar un peritaje y sus archivos o dependencias asociadas.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $peritaje = Peritaje::findOrFail($id);

            // Eliminar archivos físicos almacenados si existen
            if ($peritaje->archivo_soat) {
                Storage::disk('public')->delete($peritaje->archivo_soat);
            }
            if ($peritaje->archivo_tecnico_mecanica) {
                Storage::disk('public')->delete($peritaje->archivo_tecnico_mecanica);
            }

            // Eliminar relaciones o dependencias antes de borrar el peritaje
            $peritaje->accesorios()->delete();
            $peritaje->danosExternos()->delete();
            $peritaje->danosInternos()->delete();
            $peritaje->detallesTecnicos()->delete();
            $peritaje->sistemasMecanicos()->delete();
            $peritaje->compresionCilindros()->delete();

            // Validar si los métodos existen antes de llamarlos para evitar errores adicionales
            if (method_exists($peritaje, 'archivos')) {
                $peritaje->archivos()->delete();
            }
            if (method_exists($peritaje, 'historialEstados')) {
                $peritaje->historialEstados()->delete();
            }

            // Eliminar el peritaje principal
            $peritaje->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peritaje eliminado exitosamente.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el peritaje.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
