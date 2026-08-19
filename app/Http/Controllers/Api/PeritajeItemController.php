<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peritaje;
use App\Models\PeritajeAccesorio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeritajeItemController extends Controller
{
    /**
     * Verifica si el usuario puede modificar el peritaje.
     *
     * Admin:
     * - Puede modificar cualquier peritaje.
     *
     * Inspector:
     * - Solo puede modificar sus propios peritajes.
     */
    private function puedeGestionarPeritaje(
        Request $request,
        Peritaje $peritaje
    ): bool {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }

        if (
            in_array(
                $usuario->rol,
                ['admin', 'superadmin'],
                true
            )
        ) {
            return true;
        }

        return $usuario->rol === 'inspector'
            && (int) $peritaje->inspector_id === (int) $usuario->id;
    }

    /**
     * PUT /api/peritajes/{peritaje}/accesorios/{catalogoAccesorio}
     *
     * Equivale a "handleItemChange" en Accesorios.jsx.
     */
    public function upsertAccesorio(
        Request $request,
        Peritaje $peritaje,
        string $catalogoAccesorioId
    ): JsonResponse {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar este peritaje.'
            ], 403);
        }

        $validated = $request->validate([
            'presente' => 'nullable|boolean',
            'seleccion' => 'nullable|boolean',
            'danado' => 'nullable|boolean',
            'costo_reparacion' => 'nullable|numeric',
            'comentario_dano' => 'nullable|string',
        ]);

        $accesorio = PeritajeAccesorio::updateOrCreate(
            [
                'peritaje_id' => $peritaje->id,
                'catalogo_accesorio_id' => $catalogoAccesorioId,
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Accesorio actualizado correctamente',
            'data' => $accesorio
        ]);
    }

    /**
     * PUT /api/peritajes/{peritaje}/danos-externos/{catalogoPieza}
     *
     * Equivale a "handleGuardarDano" en VistaExterna.jsx.
     */
    public function upsertDanoExterno(
        Request $request,
        Peritaje $peritaje,
        string $catalogoPiezaId
    ): JsonResponse {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'tipo_hallazgo' => [
                'required',
                'in:NINGUNO,RAYON,ABOLLADURA,GOLPE,REPINTADO'
            ],
            'micras' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'comentario' => [
                'nullable',
                'string'
            ],
        ]);

        // Si vuelve a "Ninguno" sin comentario,
        // se elimina el registro.
        if (
            $data['tipo_hallazgo'] === 'NINGUNO'
            && empty($data['comentario'])
        ) {
            $peritaje->danosExternos()
                ->where('catalogo_pieza_id', $catalogoPiezaId)
                ->delete();

            return response()->json([
                'deleted' => true
            ]);
        }

        $item = $peritaje->danosExternos()->updateOrCreate(
            [
                'catalogo_pieza_id' => $catalogoPiezaId
            ],
            $data
        );

        return response()->json($item);
    }

    /**
     * PUT /api/peritajes/{peritaje}/danos-internos/{catalogoZona}
     *
     * Equivale a "handleGuardarZona" en VistaInterna.jsx.
     */
    public function upsertDanoInterno(
        Request $request,
        Peritaje $peritaje,
        string $catalogoZonaId
    ): JsonResponse {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'estado' => [
                'required',
                'in:OPTIMO,REGULAR,DANADO'
            ],
            'desgaste' => [
                'required',
                'in:MINIMO,NORMAL,ACELERADO'
            ],
            'comentario' => [
                'nullable',
                'string'
            ],
        ]);

        if (
            $data['estado'] === 'OPTIMO'
            && empty($data['comentario'])
        ) {
            $peritaje->danosInternos()
                ->where('catalogo_zona_id', $catalogoZonaId)
                ->delete();

            return response()->json([
                'deleted' => true
            ]);
        }

        $item = $peritaje->danosInternos()->updateOrCreate(
            [
                'catalogo_zona_id' => $catalogoZonaId
            ],
            $data
        );

        return response()->json($item);
    }

    /**
     * PUT /api/peritajes/{peritaje}/detalles-tecnicos/{catalogoElemento}
     *
     * Equivale a "handleCheckboxChange/handleTextChange"
     * en DetallesTecnicos.jsx.
     */
    public function upsertDetalleTecnico(
        Request $request,
        Peritaje $peritaje,
        string $catalogoElementoId
    ): JsonResponse {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'danado' => [
                'required',
                'boolean'
            ],
            'comentario' => [
                'nullable',
                'string',
                'max:255'
            ],
            'costo' => [
                'nullable',
                'numeric',
                'min:0'
            ],
        ]);

        $item = $peritaje->detallesTecnicos()->updateOrCreate(
            [
                'catalogo_elemento_id' => $catalogoElementoId
            ],
            $data
        );

        return response()->json($item);
    }

    /**
     * PUT /api/peritajes/{peritaje}/sistemas-mecanicos/{catalogoSistema}
     *
     * Equivale a "handleMecanicoItemChange" en Motor.jsx.
     */
    public function upsertSistemaMecanico(
        Request $request,
        Peritaje $peritaje,
        string $catalogoSistemaId
    ): JsonResponse {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'estado' => [
                'required',
                'in:BUENO,REGULAR,MALO'
            ],
            'observaciones' => [
                'nullable',
                'string'
            ],
        ]);

        $item = $peritaje->sistemasMecanicos()->updateOrCreate(
            [
                'catalogo_sistema_id' => $catalogoSistemaId
            ],
            $data
        );

        return response()->json($item);
    }

    /**
     * PUT /api/peritajes/{peritaje}/compresion
     */
    public function upsertCompresion(
        Request $request,
        Peritaje $peritaje
    ): JsonResponse {
        if (!$this->puedeGestionarPeritaje($request, $peritaje)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar este peritaje.'
            ], 403);
        }

        $data = $request->validate([
            'lecturas' => [
                'required',
                'array',
                'min:1'
            ],
            'lecturas.*.numero_cilindro' => [
                'required',
                'integer',
                'between:1,12'
            ],
            'lecturas.*.presion_psi' => [
                'nullable',
                'integer',
                'min:0'
            ],
        ]);

        foreach ($data['lecturas'] as $lectura) {
            $peritaje->compresionCilindros()->updateOrCreate(
                [
                    'numero_cilindro' => $lectura['numero_cilindro']
                ],
                [
                    'presion_psi' => $lectura['presion_psi'] ?? null
                ]
            );
        }

        return response()->json(
            $peritaje->compresionCilindros()->get()
        );
    }
}
