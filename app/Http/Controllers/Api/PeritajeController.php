<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\PeritajeCliente;
use Illuminate\Support\Str;

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
                'cliente',
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
            \Log::info("=== ENTRÓ AL STORE DE PERITAJES ===");
            \Log::info("Datos del request:", $request->all());

            // 1. Obtenemos los datos excluyendo las relaciones secundarias y archivos directos
            $data = $request->except([
                'accesorios',
                'accesoriosList',
                'peritaje_accesorios',
                'danos_externos',
                'danosExternos',
                'danos_internos',
                'danosInternos',
                'detalles_tecnicos',
                'detallesTecnicos',
                'sistemas_mecanicos',
                'sistemasMecanicos',
                'comentarios_siniestros',
                'compresion_cilindros',
                'compresionCilindros',
                'archivo_soat',
                'archivoSoat',
                'archivo_tecnico_mecanica',
                'archivoTecnicoMecanica',
                'nombre_cliente',
                'documento_cliente',
                'telefono_cliene',
                'nombreCliente',
                'documentoCliente',
                'telefonoCliente'
            ]);

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
                'comentarios_siniestros' => 'comentarios_siniestros',
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
            $data['inspector_id'] = auth()->id() ?? 1;

            // 5. Valores por defecto temporales para evitar restricciones Not null violation en PostgreSQL
            $data['marca'] = $data['marca'] ?? 'N/A';
            $data['linea'] = $data['linea'] ?? 'N/A';
            $data['modelo_anio'] = $data['modelo_anio'] ?? 0;
            $data['num_motor'] = $data['num_motor'] ?? 'N/A';
            $data['num_chasis'] = $data['num_chasis'] ?? 'N/A';
            $data['organismo_transito'] = $data['organismo_transito'] ?? 'N/A';

            // Creamos el peritaje principal utilizando el array $data procesado
            $peritaje = Peritaje::create($data);

            // 6. Extracción y guardado/actualización del cliente
            $nombre = $request->input('clienteNombre') ?? $request->input('cliente_nombre');
            $documento = $request->input('clienteDocumento') ?? $request->input('cliente_documento');
            $telefono = $request->input('clienteTelefono') ?? $request->input('cliente_telefono');

            if ($documento) {
                PeritajeCliente::updateOrCreate(
                    ['documento_cliente' => $documento],
                    [
                        'id' => (string) Str::uuid(),
                        'peritaje_id' => $peritaje->id,
                        'nombre_cliente' => $nombre,
                        'telefono_cliene' => $telefono,
                    ]
                );
            }
            $accesorios = $request->input('peritaje_accesorios') ?? $request->input('accesoriosList') ?? $request->input('accesorios', []);
            if (is_string($accesorios)) {
                $accesorios = json_decode($accesorios, true);
            }

            if ($accesorios && is_array($accesorios)) {
                $peritaje->accesorios()->delete();
                $mapeadosAccesorios = [];

                // Obtenemos el tipo de vehículo del request actual para asociarlo si toca crear el accesorio
                $tipoVehiculoId = $request->input('tipo_vehiculo_id') ?? $request->input('tipoVehiculoId') ?? $peritaje->tipo_vehiculo_id ?? null;

                foreach ($accesorios as $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $frontendId = $dataItem['id'] ?? $dataItem['catalogo_accesorio_id'] ?? null;

                    if ($frontendId) {
                        $catalogoAccesorio = null;

                        // 1. Buscar por UUID
                        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $frontendId)) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::find($frontendId);
                        }

                        // 2. Buscar por código o slug
                        if (!$catalogoAccesorio) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::where('codigo', $frontendId)
                                ->orWhere('slug', $frontendId)
                                ->first();
                        }

                        // 3. Si no existe en el catálogo, lo creamos asegurando el tipo_vehiculo_id
                        if (!$catalogoAccesorio && isset($dataItem['name'])) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::create([
                                'id' => (string) Str::uuid(),
                                'nombre' => $dataItem['name'],
                                'codigo' => $frontendId,
                                'slug' => $frontendId,
                                'tipo_vehiculo_id' => $tipoVehiculoId, // <--- Evita el error de columna nula
                                'activo' => true
                            ]);
                        }

                        // Si ya lo encontramos o lo acabamos de crear, lo asociamos al peritaje
                        if ($catalogoAccesorio) {
                            $mapeadosAccesorios[] = [
                                'id' => (string) Str::uuid(),
                                'peritaje_id' => $peritaje->id,
                                'catalogo_accesorio_id' => $catalogoAccesorio->id, // Este va a peritaje_accesorios
                                'presente' => filter_var($dataItem['presente'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                                'seleccion' => $dataItem['seleccion'] ?? null,
                                'danado' => filter_var($dataItem['danado'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                                'costo_reparacion' => (int) ($dataItem['costoReparacion'] ?? $dataItem['costo_reparacion'] ?? 0),
                                'comentario_dano' => $dataItem['comentarioDaño'] ?? $dataItem['comentario_dano'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                if (!empty($mapeadosAccesorios)) {
                    $peritaje->accesorios()->createMany($mapeadosAccesorios);
                }
            }

            // 8. Daños Externos
            $danosExternos = $request->input('danos_externos') ?? $request->input('danosExternos');
            if (is_string($danosExternos)) {
                $danosExternos = json_decode($danosExternos, true);
            }
            if ($danosExternos && is_array($danosExternos)) {
                $peritaje->danosExternos()->createMany($danosExternos);
            }

            // 9. Daños Internos
            $danosInternos = $request->input('danos_internos') ?? $request->input('danosInternos');
            if (is_string($danosInternos)) {
                $danosInternos = json_decode($danosInternos, true);
            }
            if ($danosInternos && is_array($danosInternos)) {
                $peritaje->danosInternos()->createMany($danosInternos);
            }

            // 10. Detalles Técnicos
            $detallesTecnicos = $request->input('detalles_tecnicos') ?? $request->input('detallesTecnicos');
            if (is_string($detallesTecnicos)) {
                $detallesTecnicos = json_decode($detallesTecnicos, true);
            }
            if ($detallesTecnicos && is_array($detallesTecnicos)) {
                $peritaje->detallesTecnicos()->createMany($detallesTecnicos);
            }

            // 11. Sistemas Mecánicos
            $sistemasMecanicos = $request->input('sistemas_mecanicos') ?? $request->input('sistemasMecanicos');
            if (is_string($sistemasMecanicos)) {
                $sistemasMecanicos = json_decode($sistemasMecanicos, true);
            }
            if ($sistemasMecanicos && is_array($sistemasMecanicos)) {
                $peritaje->sistemasMecanicos()->delete();
                $mapeados = [];
                foreach ($sistemasMecanicos as $item) {
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

            // 12. Compresión de Cilindros
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
                foreach ($cilindrosMapeados as $cilindroData) {
                    \App\Models\PeritajeCompresionCilindro::create([
                        'id' => (string) Str::uuid(),
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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $peritaje = Peritaje::findOrFail($id);

            $data = $request->except([
                'accesorios',
                'accesoriosList',
                'peritaje_accesorios',
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
                'nombre_cliente' => 'cliente_nombre',
                'documento_cliente' => 'cliente_documento',
                'telefono_cliene' => 'cliente_telefono',
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

            $nombre = $request->input('clienteNombre') ?? $request->input('cliente_nombre');
            $documento = $request->input('clienteDocumento') ?? $request->input('cliente_documento');
            $telefono = $request->input('clienteTelefono') ?? $request->input('cliente_telefono');

            if ($documento) {
                PeritajeCliente::updateOrCreate(
                    ['documento_cliente' => $documento],
                    [
                        'peritaje_id' => $peritaje->id,
                        'nombre_cliente' => $nombre,
                        'telefono_cliene' => $telefono,
                    ]
                );
            }

            // Accesorios
            $accesorios = $request->input('peritaje_accesorios') ?? $request->input('accesoriosList') ?? $request->input('accesorios', []);
            if (is_string($accesorios)) {
                $accesorios = json_decode($accesorios, true);
            }

            if ($accesorios && is_array($accesorios)) {
                $peritaje->accesorios()->delete();
                $mapeadosAccesorios = [];

                // Obtenemos el tipo de vehículo del request actual para asociarlo si toca crear el accesorio
                $tipoVehiculoId = $request->input('tipo_vehiculo_id') ?? $request->input('tipoVehiculoId') ?? $peritaje->tipo_vehiculo_id ?? null;

                foreach ($accesorios as $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $frontendId = $dataItem['id'] ?? $dataItem['catalogo_accesorio_id'] ?? null;

                    if ($frontendId) {
                        $catalogoAccesorio = null;

                        // 1. Buscar por UUID
                        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $frontendId)) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::find($frontendId);
                        }

                        // 2. Buscar por código o slug
                        if (!$catalogoAccesorio) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::where('codigo', $frontendId)
                                ->orWhere('slug', $frontendId)
                                ->first();
                        }

                        // 3. Si no existe en el catálogo, lo creamos asegurando el tipo_vehiculo_id
                        if (!$catalogoAccesorio && isset($dataItem['name'])) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::create([
                                'id' => (string) Str::uuid(),
                                'nombre' => $dataItem['name'],
                                'codigo' => $frontendId,
                                'slug' => $frontendId,
                                'tipo_vehiculo_id' => $tipoVehiculoId, // <--- Evita el error de columna nula
                                'activo' => true
                            ]);
                        }

                        // Si ya lo encontramos o lo acabamos de crear, lo asociamos al peritaje
                        if ($catalogoAccesorio) {
                            $mapeadosAccesorios[] = [
                                'id' => (string) Str::uuid(),
                                'peritaje_id' => $peritaje->id,
                                'catalogo_accesorio_id' => $catalogoAccesorio->id, // Este va a peritaje_accesorios
                                'presente' => filter_var($dataItem['presente'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                                'seleccion' => $dataItem['seleccion'] ?? null,
                                'danado' => filter_var($dataItem['danado'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                                'costo_reparacion' => (int) ($dataItem['costoReparacion'] ?? $dataItem['costo_reparacion'] ?? 0),
                                'comentario_dano' => $dataItem['comentarioDaño'] ?? $dataItem['comentario_dano'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                if (!empty($mapeadosAccesorios)) {
                    $peritaje->accesorios()->createMany($mapeadosAccesorios);
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

                foreach ($sistemasMecanicos as $item) {
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

            // 6. COMPRESIÓN CILINDROS
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
                        'id' => (string) Str::uuid(),
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

    public function buscarClientes(Request $request)
    {
        $query = $request->input('q');

        $clientes = PeritajeCliente::when($query, function ($qBuilder) use ($query) {
            $qBuilder->where('nombre_cliente', 'like', "%{$query}%")
                ->orWhere('documento_cliente', 'like', "%{$query}%");
        })
            ->limit(10)
            ->get();

        return response()->json($clientes);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $peritaje = Peritaje::findOrFail($id);

            if ($peritaje->archivo_soat) {
                Storage::disk('public')->delete($peritaje->archivo_soat);
            }
            if ($peritaje->archivo_tecnico_mecanica) {
                Storage::disk('public')->delete($peritaje->archivo_tecnico_mecanica);
            }

            $peritaje->accesorios()->delete();
            $peritaje->danosExternos()->delete();
            $peritaje->danosInternos()->delete();
            $peritaje->detallesTecnicos()->delete();
            $peritaje->sistemasMecanicos()->delete();
            $peritaje->compresionCilindros()->delete();

            if (method_exists($peritaje, 'archivos')) {
                $peritaje->archivos()->delete();
            }
            if (method_exists($peritaje, 'historialEstados')) {
                $peritaje->historialEstados()->delete();
            }

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
