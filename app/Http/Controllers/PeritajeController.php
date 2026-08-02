<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeritajeRequest;
use App\Http\Requests\UpdatePeritajeRequest;
use App\Models\Peritaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeritajeController extends Controller
{
    /**
     * GET /api/peritajes
     * Alimenta la "Bandeja de Entrada" del dashboard. Soporta filtros
     * simples por estado y búsqueda por placa vía query params.
     */
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
     * tipo de vehículo (equivale al modal "Seleccionar Tipo de Vehículo"
     * del dashboard). El resto de los pasos se van guardando con PATCH.
     */
    public function store(StorePeritajeRequest $request): JsonResponse
    {
        $peritaje = Peritaje::create(array_merge($request->validated(), [
            'inspector_id' => $request->user()->id,
            'estado' => 'borrador',
        ]));

        $peritaje->historialEstados()->create([
            'estado' => 'borrador',
            'usuario_id' => $request->user()->id,
            'comentario' => 'Peritaje iniciado',
        ]);

        return response()->json($peritaje, 201);
    }

    /** GET /api/peritajes/{peritaje} */
    public function show(Peritaje $peritaje): JsonResponse
    {
        return response()->json(
            Peritaje::conDetalleCompleto()->findOrFail($peritaje->id)
        );
    }

    /**
     * PATCH /api/peritajes/{peritaje}
     * El frontend llama esto en cada "onChange" (equivalente a
     * handleDataChange en dashboard.jsx), guardando solo los campos que
     * cambiaron gracias a las reglas "sometimes" del FormRequest.
     */
    public function update(UpdatePeritajeRequest $request, Peritaje $peritaje): JsonResponse
    {
        $peritaje->update($request->validated());

        return response()->json($peritaje->fresh());
    }

    /**
     * PATCH /api/peritajes/{peritaje}/estado
     * Avanza el flujo Borrador -> En Proceso -> Completado (o Anulado),
     * dejando rastro en peritaje_historial_estados.
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

    /** DELETE /api/peritajes/{peritaje} — se anula, nunca se borra físico (trazabilidad). */
    public function destroy(Request $request, Peritaje $peritaje): JsonResponse
    {
        $peritaje->cambiarEstado('anulado', $request->user()->id, 'Peritaje anulado');

        return response()->json(['message' => 'Peritaje anulado'], 200);
    }
}
