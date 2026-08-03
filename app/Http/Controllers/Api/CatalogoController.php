<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogoElementoTecnico;
use App\Models\Sucursal;
use App\Models\TipoVehiculo;
use App\Models\Vendedor;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    /** GET /api/tipos-vehiculo */
    public function tiposVehiculo(): JsonResponse
    {
        return response()->json(
            TipoVehiculo::where('activo', true)->orderBy('orden')->get()
        );
    }

    /**
     * GET /api/tipos-vehiculo/{tipoVehiculo}/checklist
     * Arma en un solo request todos los catálogos que necesita el frontend
     * para renderizar el formulario del tipo de vehículo elegido:
     * accesorios, piezas de carrocería, zonas de cabina (si aplica) y
     * sistemas mecánicos. Los "detalles técnicos" son universales.
     */
    public function checklist(TipoVehiculo $tipoVehiculo): JsonResponse
    {
        return response()->json([
            'tipo_vehiculo' => $tipoVehiculo,
            'accesorios' => $tipoVehiculo->accesorios,
            'piezas_carroceria' => $tipoVehiculo->piezasCarroceria,
            // Moto y motocarro no tienen vista interna: el frontend ya
            // maneja el arreglo vacío mostrando "Sección No Disponible".
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

    /** GET /api/vendedores */
    public function vendedores(): JsonResponse
    {
        return response()->json(Vendedor::where('activo', true)->orderBy('nombre')->get());
    }
}
