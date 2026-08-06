<?php // @phpstan-ignore-file

namespace App\Http\Controllers\Api;

// Cambia esto en la parte superior de tu PeritajeController.php:
use App\Http\Requests\UpdatePeritajeRequest;
use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeritajeController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $query = Peritaje::with(['tipoVehiculo', 'inspector', 'sucursalVendedor', 'sucursalInspeccion', 'vendedor'])
            ->orderByDesc('fecha_peritaje');

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('placa')) {
            $query->where('placa', 'ilike', '%' . $request->string('placa') . '%');
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    /**
     * POST /api/peritajes
     * Crea el peritaje en estado "borrador" apenas el inspector elige el
     * tipo de vehículo.
     */
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

        // Validamos y filtramos los UUIDs para asegurar que si viene un valor inválido, vacío o numérico, se guarde como null
        $sucursalVendedor = (isset($data['sucursal_vendedor_id']) && Str::isUuid($data['sucursal_vendedor_id'])) ? $data['sucursal_vendedor_id'] : null;
        $sucursalInspeccion = (isset($data['sucursal_inspeccion_id']) && Str::isUuid($data['sucursal_inspeccion_id'])) ? $data['sucursal_inspeccion_id'] : null;
        $vendedor = (isset($data['vendedor_id']) && Str::isUuid($data['vendedor_id'])) ? $data['vendedor_id'] : null;

        $peritaje = Peritaje::create([
            'id' => Str::uuid(),
            'codigo' => $codigoSecuencial,
            'tipo_vehiculo_id' => $data['tipo_vehiculo_id'],
            'inspector_id' => $request->user()->id,
            'estado' => 'borrador',

            // Asignaciones limpias asegurando compatibilidad de UUIDs
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

        return response()->json($peritaje->load(['tipoVehiculo', 'inspector']), 201);
    }

    /** GET /api/peritajes/{peritaje} */
    public function show($id)
{
    $peritaje = Peritaje::findOrFail($id);

    // Si tus tablas relacionadas existen, las cargas así de forma segura:
    // $peritaje->load(['accesorios', 'detallesTecnicos']);

    return response()->json($peritaje);
}

    /**
     * PATCH /api/peritajes/{peritaje}
     */
   public function update(\App\Http\Requests\UpdatePeritajeRequest $request, Peritaje $peritaje): JsonResponse
    {
        // 1. Obtenemos todos los datos ya limpios, validados y decodificados por el FormRequest
        $data = $request->validated();

        // 2. Manejo seguro de archivos multimedia (si no mandan nuevos, los eliminamos del array
        // para que Laravel no sobreescriba la ruta vieja con null o vacíos)
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

        // 3. Actualizamos el peritaje con todo el array $data de un solo golpe.
        // (Esto incluye textos, números, booleanos y todos los arrays de accesorios/daños ya decodificados).
        $peritaje->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Peritaje actualizado correctamente',
            'data' => $peritaje->fresh()
        ]);
    }

    /**
     * PATCH /api/peritajes/{peritaje}/estado
     */
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

        return response()->json($peritaje->fresh());
    }

    /** DELETE /api/peritajes/{peritaje} */
    public function destroy($id)
{
    $peritaje = Peritaje::find($id);

    if (!$peritaje) {
        return response()->json(['message' => 'No encontrado'], 404);
    }

    $peritaje->delete();

    return response()->json(['success' => true, 'message' => 'Eliminado correctamente'], 200);
}



}
