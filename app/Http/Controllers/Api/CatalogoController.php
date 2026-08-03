<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogoElementoTecnico;
use App\Models\Sucursal;
use App\Models\TipoVehiculo;
use App\Models\Vendedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // <-- Asegúrate de tener esta importación

class CatalogoController extends Controller
{
    /** GET /api/tipos-vehiculo */
    public function tiposVehiculo(): JsonResponse
    {
        return response()->json(
            TipoVehiculo::where('activo', true)->orderBy('orden')->get()
        );
    }

    /** GET /api/tipos-vehiculo/{tipoVehiculo}/checklist */
    public function checklist(TipoVehiculo $tipoVehiculo): JsonResponse
    {
        return response()->json([
            'tipo_vehiculo' => $tipoVehiculo,
            'accesorios' => $tipoVehiculo->accesorios,
            'piezas_carroceria' => $tipoVehiculo->piezasCarroceria,
            'zonas_cabina' => $tipoVehiculo->zonasCabina,
            'sistemas_mecanicos' => $tipoVehiculo->sistemasMecanicos,
            'elementos_tecnicos' => CatalogoElementoTecnico::where('activo', true)->orderBy('orden')->get(),
        ]);
    }

    /** GET /api/sucursales */
    public function sucursales(): JsonResponse
    {
        return response()->json(Sucursal::where('activa', true)->orderBy('nombre')->get());
    }

    /** POST /api/sucursales */
    public function storeSucursal(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $sucursal = Sucursal::create([
            'nombre' => $request->nombre,
            'activa' => true, // Por si tu tabla maneja este campo según el scope de arriba
        ]);

        return response()->json([
            'message' => 'Sucursal creada con éxito',
            'data' => $sucursal
        ], 201);
    }

    /** GET /api/vendedores */
    public function vendedores(): JsonResponse
    {
        return response()->json(Vendedor::where('activo', true)->orderBy('nombre')->get());
    }

    /** POST /api/vendedores */
    public function storeVendedor(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $vendedor = Vendedor::create([
            'nombre' => $request->nombre,
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Vendedor creado con éxito',
            'data' => $vendedor
        ], 201);
    }
}
