<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\PeritajeCliente;
use App\Models\PeritajeImagen;
use Illuminate\Support\Str;

class PeritajeController extends Controller
{
    private function esAdmin(): bool
    {
        return auth()->user()?->rol === 'admin';
    }
    public function index()
    {
        try {
            $query = Peritaje::with([
                'sucursalVendedor',
                'sucursalInspeccion',
                'vendedor',
                'inspector',
                'accesorios',
                'danosExternos',
                'danosInternos',
                'detallesTecnicos',
                'sistemasMecanicos',
                'compresionCilindros',
                'imagenes'
            ]);

            if (!$this->esAdmin()) {
                $query->where('inspector_id', auth()->id());
            }

            $peritajes = $query->latest()->get();

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

    public function show($id)
    {
        try {
            $peritaje = Peritaje::with([
                'sucursalVendedor',
                'sucursalInspeccion',
                'vendedor',
                'inspector',
                'cliente',
                'accesorios.catalogoAccesorio',
                'danosExternos',
                'danosInternos',
                'detallesTecnicos',
                'sistemasMecanicos',
                'compresionCilindros',
                'imagenes'
            ])->findOrFail($id);

            if (!$this->esAdmin() && $peritaje->inspector_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para consultar este peritaje.'
                ], 403);
            }

            $peritaje->setRelation('accesorios', $peritaje->accesorios->map(function ($item) {
                return [
                    'id' => $item->catalogoAccesorio->codigo ?? $item->catalogo_accesorio_id,
                    'catalogo_accesorio_id' => $item->catalogo_accesorio_id,
                    'name' => $item->catalogoAccesorio->nombre ?? '',
                    'presente' => (bool) $item->presente,
                    'seleccion' => $item->seleccion,
                    'danado' => (bool) $item->danado,
                    'costoReparacion' => $item->costo_reparacion,
                    'comentarioDaño' => $item->comentario_dano,
                ];
            }));

            $peritaje->setRelation('imagenes', $peritaje->imagenes->map(function ($img) {
                return [
                    'id' => $img->id,
                    'seccion' => $img->seccion,
                    'item_id' => $img->item_id,
                    'imagen_base64' => $img->imagen_base64,
                    'nombre_archivo' => $img->nombre_archivo,
                ];
            }));

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

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
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
                'nombre_cliente' => 'required|string|max:120',
                'documento_cliente' => 'required|string|max:120',
                'telefono_cliene' => 'nullable|string|max:120',
                'imagenes',
                'peritaje_imagenes',
                'comentarios_siniestros',
                'siniestros',
            ]);

            $mapeoCampos = [
                'sucursalVendedorId' => 'sucursal_vendedor_id',
                'sucursalInspeccionId' => 'sucursal_inspeccion_id',
                'vendedorId' => 'vendedor_id',
                'tipoVehiculo' => 'tipo_vehiculo',
                'modeloAnio' => 'modelo_anio',
                'numMotor' => 'num_motor',
                'numChasis' => 'num_chasis',
                'nombre_cliente' => 'nombre_cliente',
                'documento_cliente' => 'documento_cliente',
                'telefono_cliene' => 'telefono_cliene',
                'soatAlDia' => 'soat_al_dia',
                'venceSoat' => 'vence_soat',
                'tecnicoMecanicaAlDia' => 'tecnico_mecanica_al_dia',
                'venceTecnicoMecanica' => 'vence_tecnico_mecanica',
                'comentarios_siniestros' => 'comentarios_siniestros',
                'siniestros' => 'comentarios_siniestros',
            ];

            foreach ($mapeoCampos as $keyCamel => $keySnake) {
                if ($request->has($keyCamel) && !isset($data[$keySnake])) {
                    $data[$keySnake] = $request->input($keyCamel);
                }
            }

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

            $data['inspector_id'] = auth()->id();

            $data['marca'] = $data['marca'] ?? 'N/A';
            $data['linea'] = $data['linea'] ?? 'N/A';
            $data['modelo_anio'] = $data['modelo_anio'] ?? 0;
            $data['num_motor'] = $data['num_motor'] ?? 'N/A';
            $data['num_chasis'] = $data['num_chasis'] ?? 'N/A';
            $data['organismo_transito'] = $data['organismo_transito'] ?? 'N/A';

            $peritaje = Peritaje::create($data);

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
                $tipoVehiculoId = $request->input('tipo_vehiculo_id') ?? $request->input('tipoVehiculoId') ?? $peritaje->tipo_vehiculo_id ?? null;

                foreach ($accesorios as $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $frontendId = $dataItem['id'] ?? $dataItem['catalogo_accesorio_id'] ?? null;

                    if ($frontendId) {
                        $catalogoAccesorio = null;

                        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $frontendId)) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::find($frontendId);
                        }

                        if (!$catalogoAccesorio) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::where('codigo', $frontendId)
                                ->orWhere('slug', $frontendId)
                                ->first();
                        }

                        if (!$catalogoAccesorio && isset($dataItem['name'])) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::create([
                                'id' => (string) Str::uuid(),
                                'nombre' => $dataItem['name'],
                                'codigo' => $frontendId,
                                'slug' => $frontendId,
                                'tipo_vehiculo_id' => $tipoVehiculoId,
                                'activo' => true
                            ]);
                        }

                        if ($catalogoAccesorio) {
                            $mapeadosAccesorios[] = [
                                'id' => (string) Str::uuid(),
                                'peritaje_id' => $peritaje->id,
                                'catalogo_accesorio_id' => $catalogoAccesorio->id,
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

            $danosExternos = $request->input('danos_externos') ?? $request->input('danosExternos');
            if (is_string($danosExternos)) {
                $danosExternos = json_decode($danosExternos, true);
            }
            if ($danosExternos && is_array($danosExternos)) {
                $peritaje->danosExternos()->createMany($danosExternos);
            }

            $danosInternos = $request->input('danos_internos') ?? $request->input('danosInternos');
            if (is_string($danosInternos)) {
                $danosInternos = json_decode($danosInternos, true);
            }
            if ($danosInternos && is_array($danosInternos)) {
                $peritaje->danosInternos()->createMany($danosInternos);
            }

            $detallesTecnicos = $request->input('detalles_tecnicos') ?? $request->input('detallesTecnicos');
            if (is_string($detallesTecnicos)) {
                $detallesTecnicos = json_decode($detallesTecnicos, true);
            }
            if ($detallesTecnicos && is_array($detallesTecnicos)) {
                $peritaje->detallesTecnicos()->createMany($detallesTecnicos);
            }

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

            $imagenes = $request->input('imagenes') ?? $request->input('peritaje_imagenes', []);
            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true);
            }

            if ($imagenes && is_array($imagenes)) {
                foreach ($imagenes as $imgItem) {
                    $seccion = $imgItem['seccion'] ?? null;
                    $base64 = $imgItem['imagen_base64'] ?? $imgItem['base64'] ?? null;

                    if ($seccion && $base64) {
                        PeritajeImagen::updateOrCreate(
                            [
                                'peritaje_id' => $peritaje->id,
                                'seccion' => $seccion,
                                'item_id' => $imgItem['item_id'] ?? null,
                            ],
                            [
                                'imagen_base64' => $base64,
                                'nombre_archivo' => $imgItem['nombre_archivo'] ?? 'archivo_peritaje',
                            ]
                        );
                    }
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
                    'compresionCilindros',
                    'imagenes'
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
            if (!$this->esAdmin() && $peritaje->inspector_id !== auth()->id()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para modificar este peritaje.'
                ], 403);
            }

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
                'archivoTecnicoMecanica',
                'imagenes',
                'peritaje_imagenes'
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
                        'nombre_cliente' => $request->nombre_cliente,
                        'documento_cliente' => $request->documento_cliente,
                        'telefono_cliene' => $request->telefono_cliene,
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
                $tipoVehiculoId = $request->input('tipo_vehiculo_id') ?? $request->input('tipoVehiculoId') ?? $peritaje->tipo_vehiculo_id ?? null;

                foreach ($accesorios as $item) {
                    $dataItem = is_array($item) ? $item : [];
                    $frontendId = $dataItem['id'] ?? $dataItem['catalogo_accesorio_id'] ?? null;

                    if ($frontendId) {
                        $catalogoAccesorio = null;

                        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $frontendId)) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::find($frontendId);
                        }

                        if (!$catalogoAccesorio) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::where('codigo', $frontendId)
                                ->orWhere('slug', $frontendId)
                                ->first();
                        }

                        if (!$catalogoAccesorio && isset($dataItem['name'])) {
                            $catalogoAccesorio = \App\Models\CatalogoAccesorio::create([
                                'id' => (string) Str::uuid(),
                                'nombre' => $dataItem['name'],
                                'codigo' => $frontendId,
                                'slug' => $frontendId,
                                'tipo_vehiculo_id' => $tipoVehiculoId,
                                'activo' => true
                            ]);
                        }

                        if ($catalogoAccesorio) {
                            $mapeadosAccesorios[] = [
                                'id' => (string) Str::uuid(),
                                'peritaje_id' => $peritaje->id,
                                'catalogo_accesorio_id' => $catalogoAccesorio->id,
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

            $imagenes = $request->input('imagenes') ?? $request->input('peritaje_imagenes', []);
            if (is_string($imagenes)) {
                $imagenes = json_decode($imagenes, true);
            }

            if ($imagenes && is_array($imagenes)) {
                foreach ($imagenes as $imgItem) {
                    $seccion = $imgItem['seccion'] ?? null;
                    $base64 = $imgItem['imagen_base64'] ?? $imgItem['base64'] ?? null;

                    if ($seccion && $base64) {
                        PeritajeImagen::updateOrCreate(
                            [
                                'peritaje_id' => $peritaje->id,
                                'seccion' => $seccion,
                                'item_id' => $imgItem['item_id'] ?? null,
                            ],
                            [
                                'imagen_base64' => $base64,
                                'nombre_archivo' => $imgItem['nombre_archivo'] ?? 'archivo_peritaje',
                            ]
                        );
                    }
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
                    'compresionCilindros',
                    'imagenes'
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

            if (!$this->esAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden eliminar peritajes.'
                ], 403);
            }

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
            $peritaje->imagenes()->delete();

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
