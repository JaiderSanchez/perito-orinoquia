<?php // @phpstan-ignore-file

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePeritajeRequest;
use App\Http\Resources\PeritajeResource;
use App\Models\Peritaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeritajeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Peritaje::with(['tipoVehiculo', 'inspector', 'sucursalVendedor', 'sucursalInspeccion', 'vendedor'])
            ->orderByDesc('fecha_peritaje');

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('placa')) {
            $query->where('placa', 'ilike', '%' . $request->string('placa') . '%');
        }

        return PeritajeResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo_vehiculo_id' => ['required', 'uuid', 'exists:tipos_vehiculo,id'],
            'sucursal_vendedor_id' => ['nullable'],
            'sucursal_inspeccion_id' => ['nullable'],
            'vendedor_id' => ['nullable'],
            'placa' => ['nullable', 'string', 'max:10'],
            'marca' => ['nullable', 'string', 'max:80'],
            'linea' => ['nullable', 'string', 'max:80'],
            'modelo_anio' => ['nullable', 'integer'],
            'num_motor' => ['nullable', 'string', 'max:60'],
            'num_chasis' => ['nullable', 'string', 'max:60'],
            'kilometraje' => ['nullable', 'integer'],
        ]);

        $codigoSecuencial = 'PER-' . str_pad(DB::select("SELECT nextval('peritajes_codigo_seq') as val")[0]->val, 4, '0', STR_PAD_LEFT);

        $sucursalVendedor = (isset($data['sucursal_vendedor_id']) && Str::isUuid($data['sucursal_vendedor_id'])) ? $data['sucursal_vendedor_id'] : null;
        $sucursalInspeccion = (isset($data['sucursal_inspeccion_id']) && Str::isUuid($data['sucursal_inspeccion_id'])) ? $data['sucursal_inspeccion_id'] : null;
        $vendedor = (isset($data['vendedor_id']) && Str::isUuid($data['vendedor_id'])) ? $data['vendedor_id'] : null;

        $peritaje = Peritaje::create([
            'id' => Str::uuid(),
            'codigo' => $codigoSecuencial,
            'tipo_vehiculo_id' => $data['tipo_vehiculo_id'],
            'inspector_id' => $request->user()->id,
            'estado' => 'borrador',
            'sucursal_vendedor_id' => $sucursalVendedor,
            'sucursal_inspeccion_id' => $sucursalInspeccion,
            'vendedor_id' => $vendedor,
            'placa' => !empty($data['placa']) ? strtoupper($data['placa']) : 'SIN-PLACA',
            'marca' => !empty($data['marca']) ? $data['marca'] : 'POR DEFINIR',
            'linea' => !empty($data['linea']) ? $data['linea'] : 'POR DEFINIR',
            'modelo_anio' => $data['modelo_anio'] ?? 2026,
            'num_motor' => !empty($data['num_motor']) ? $data['num_motor'] : 'PENDIENTE',
            'num_chasis' => !empty($data['num_chasis']) ? $data['num_chasis'] : 'PENDIENTE',
            'kilometraje' => $data['kilometraje'] ?? 0,
        ]);

        $peritaje->historialEstados()->create([
            'id' => Str::uuid(),
            'estado' => 'borrador',
            'usuario_id' => $request->user()->id,
            'comentario' => 'Peritaje iniciado',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Peritaje creado correctamente',
            'data' => new PeritajeResource($peritaje->load(['tipoVehiculo', 'inspector']))
        ], 201);
    }

    public function show($id): PeritajeResource
    {
        $peritaje = Peritaje::with([
            'tipoVehiculo',
            'inspector',
            'sucursalVendedor',
            'sucursalInspeccion',
            'vendedor',
            'accesorios',
            'danosExternos',
            'danosInternos',
            'detallesTecnicos',
            'sistemasMecanicos',
            'compresionCilindros',
            'archivos'
        ])->findOrFail($id);

        return new PeritajeResource($peritaje);
    }

    public function update(UpdatePeritajeRequest $request, Peritaje $peritaje): JsonResponse
    {
        $data = $request->validated();

        $accesorios = $data['accesorios'] ?? null;
        $danosExternos = $data['danos_externos'] ?? null;
        $danosInternos = $data['danos_internos'] ?? null;
        $detallesTecnicos = $data['detalles_tecnicos'] ?? null;
        $sistemasMecanicos = $data['sistemas_mecanicos'] ?? null;
        $compresionCilindros = $data['compresion_cilindros'] ?? null;

        unset(
            $data['accesorios'],
            $data['danos_externos'],
            $data['danos_internos'],
            $data['detalles_tecnicos'],
            $data['sistemas_mecanicos'],
            $data['compresion_cilindros']
        );

        if ($request->hasFile('foto_soat')) {
            $data['foto_soat'] = $request->file('foto_soat')->store('peritajes/soat', 'public');
        } else {
            unset($data['foto_soat']);
        }

        if ($request->hasFile('foto_rtm')) {
            $data['foto_rtm'] = $request->file('foto_rtm')->store('peritajes/rtm', 'public');
        } else {
            unset($data['foto_rtm']);
        }

        DB::transaction(function () use ($peritaje, $data, $accesorios, $danosExternos, $danosInternos, $detallesTecnicos, $sistemasMecanicos, $compresionCilindros) {
            $peritaje->update($data);

            // 1. Accesorios
            if (is_array($accesorios)) {
                $peritaje->accesorios()->delete();
                if (!empty($accesorios)) {
                    $formattedAccesorios = array_map(function ($item) {
                        $catalogoId = $item['catalogo_accesorio_id'] ?? $item['id'] ?? null;
                        $resolvedId = null;

                        if ($catalogoId) {
                            if (Str::isUuid($catalogoId)) {
                                $resolvedId = $catalogoId;
                            } else {
                                $catalogo = \App\Models\CatalogoAccesorio::where('nombre', 'ilike', $catalogoId)->first();
                                $resolvedId = $catalogo ? $catalogo->id : null;
                            }
                        }

                        return [
                            'catalogo_accesorio_id' => $resolvedId,
                            'presente' => filter_var($item['presente'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'danado' => filter_var($item['danado'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];
                    }, $accesorios);

                    $formattedAccesorios = array_filter($formattedAccesorios, fn($acc) => !empty($acc['catalogo_accesorio_id']));

                    if (!empty($formattedAccesorios)) {
                        $peritaje->accesorios()->createMany($formattedAccesorios);
                    }
                }
            }

            // 2. Daños Externos (Se excluyen campos visuales tipo 'tipo' u otros extras)
            // 2. Daños Externos
// 2. Daños Externos
            if (is_array($danosExternos)) {
                $peritaje->danosExternos()->delete();
                if (!empty($danosExternos)) {
                    $formattedDanos = array_map(function ($item) {
                        return [
                            'catalogo_pieza_id' => $item['catalogo_pieza_id']
                                ?? $item['pieza_id']
                                ?? $item['catalogo_pieza_carroceria_id']
                                ?? $item['id_pieza']
                                ?? $item['id']
                                ?? null,
                            'micras' => $item['micras'] ?? null,
                            'comentario' => $item['comentario'] ?? null,
                            'foto' => $item['foto'] ?? null,
                            'fotoNombre' => $item['fotoNombre'] ?? null,
                        ];
                    }, $danosExternos);

                    // Filtramos para que guarde el registro si tiene al menos un comentario o una pieza
                    $formattedDanos = array_filter($formattedDanos, fn($d) => !empty($d['catalogo_pieza_id']) || !empty($d['comentario']));

                    if (!empty($formattedDanos)) {
                        $peritaje->danosExternos()->createMany($formattedDanos);
                    }
                }
            }
            // 3. Daños Internos
            if (is_array($danosInternos)) {
                $peritaje->danosInternos()->delete();
                if (!empty($danosInternos)) {
                    $formattedDanosInternos = array_map(function ($item) {
                        return [
                            'catalogo_zona_cabina_id' => $item['catalogo_zona_cabina_id'] ?? $item['id'] ?? null,
                            'comentario' => $item['comentario'] ?? null,
                            'foto' => $item['foto'] ?? null,
                            'fotoNombre' => $item['fotoNombre'] ?? null,
                        ];
                    }, $danosInternos);

                    $formattedDanosInternos = array_filter($formattedDanosInternos, fn($d) => !empty($d['catalogo_zona_cabina_id']) || !empty($d['comentario']));

                    if (!empty($formattedDanosInternos)) {
                        $peritaje->danosInternos()->createMany($formattedDanosInternos);
                    }
                }
            }

            // 4. Detalles Técnicos
            if (is_array($detallesTecnicos)) {
                $peritaje->detallesTecnicos()->delete();
                if (!empty($detallesTecnicos)) {
                    $formattedDetalles = array_map(function ($item) {
                        return [
                            'catalogo_elementos_tecnico_id' => $item['catalogo_elementos_tecnico_id'] ?? $item['catalogo_elemento_id'] ?? $item['id'] ?? null,
                            'estado' => $item['estado'] ?? null,
                            'comentario' => $item['comentario'] ?? null,
                        ];
                    }, $detallesTecnicos);

                    $formattedDetalles = array_filter($formattedDetalles, fn($d) => !empty($d['catalogo_elementos_tecnico_id']) || !empty($d['estado']));

                    if (!empty($formattedDetalles)) {
                        $peritaje->detallesTecnicos()->createMany($formattedDetalles);
                    }
                }
            }

            // 5. Sistemas Mecánicos
            if (is_array($sistemasMecanicos)) {
                $peritaje->sistemasMecanicos()->delete();
                if (!empty($sistemasMecanicos)) {
                    $formattedSistemas = array_map(function ($item) {
                        return [
                            'catalogo_sistema_mecanico_id' => $item['catalogo_sistema_mecanico_id'] ?? $item['catalogo_sistema_id'] ?? $item['id'] ?? null,
                            'estado' => $item['estado'] ?? null,
                            'comentario' => $item['comentario'] ?? null,
                        ];
                    }, $sistemasMecanicos);

                    $formattedSistemas = array_filter($formattedSistemas, fn($s) => !empty($s['catalogo_sistema_mecanico_id']) || !empty($s['estado']));

                    if (!empty($formattedSistemas)) {
                        $peritaje->sistemasMecanicos()->createMany($formattedSistemas);
                    }
                }
            }

            // 6. Compresión de Cilindros
            if (is_array($compresionCilindros)) {
                $peritaje->compresionCilindros()->delete();
                if (!empty($compresionCilindros)) {
                    $formattedCompresion = array_map(function ($item) {
                        return [
                            'numero_cilindro' => $item['numero_cilindro'] ?? null,
                            'valor' => $item['valor'] ?? $item['psi'] ?? null,
                            'comentario' => $item['comentario'] ?? null,
                        ];
                    }, $compresionCilindros);

                    $formattedCompresion = array_filter($formattedCompresion, fn($c) => !empty($c['numero_cilindro']));

                    if (!empty($formattedCompresion)) {
                        $peritaje->compresionCilindros()->createMany($formattedCompresion);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Peritaje actualizado correctamente',
            'data' => new PeritajeResource($peritaje->fresh()->load([
                'tipoVehiculo',
                'inspector',
                'sucursalVendedor',
                'sucursalInspeccion',
                'vendedor',
                'accesorios',
                'danosExternos',
                'danosInternos',
                'detallesTecnicos',
                'sistemasMecanicos',
                'compresionCilindros',
                'archivos'
            ]))
        ]);
    }

    public function cambiarEstado(Request $request, Peritaje $peritaje): JsonResponse
    {
        $data = $request->validate([
            'estado' => ['required', 'in:borrador,en_proceso,completado,anulado'],
            'comentario' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['estado'] === 'completado') {
            $peritaje->update(['firmado_en' => now()]);
        }

        $peritaje->cambiarEstado($data['estado'], $request->user()->id, $data['comentario'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Estado cambiado correctamente',
            'data' => new PeritajeResource($peritaje->fresh()->load([
                'tipoVehiculo',
                'inspector',
                'accesorios',
                'danosExternos',
                'danosInternos',
                'detallesTecnicos',
                'sistemasMecanicos',
                'compresionCilindros',
                'archivos'
            ]))
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $peritaje = Peritaje::find($id);

        if (!$peritaje) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $peritaje->delete();

        return response()->json(['success' => true, 'message' => 'Eliminado correctamente'], 200);
    }
}
