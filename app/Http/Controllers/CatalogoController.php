<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoVehiculo;
use App\Models\CatalogoAccesorio;
use App\Models\CatalogoPiezasCarroceria;
use App\Models\CatalogoZonasCabina;
use App\Models\CatalogoElementosTecnicos;
use App\Models\CatalogoSistemasMecanicos;

class CatalogoController extends Controller
{
    // Obtener todos los catálogos según el tipo de vehículo (ej: 'carro', 'moto')
    public function getCatalogosPorTipo($codigo_tipo)
    {
        $tipo = TipoVehiculo::where('codigo', $codigo_tipo)->where('activo', true)->first();

        if (!$tipo) {
            return response()->json(['message' => 'Tipo de vehículo no encontrado'], 404);
        }

        return response()->json([
            'tipo_vehiculo' => $tipo,
            'accesorios' => CatalogoAccesorio::where('tipo_vehiculo_id', $tipo->id)->orderBy('orden')->get(),
            'piezas_carroceria' => CatalogoPiezasCarroceria::where('tipo_vehiculo_id', $tipo->id)->orderBy('orden')->get(),
            'zonas_cabina' => CatalogoZonasCabina::where('tipo_vehiculo_id', $tipo->id)->orderBy('orden')->get(),
            'sistemas_mecanicos' => CatalogoSistemasMecanicos::where('tipo_vehiculo_id', $tipo->id)->orderBy('orden')->get(),
            // Los elementos técnicos son globales, no dependen del tipo de vehículo
            'elementos_tecnicos' => CatalogoElementosTecnicos::where('activo', true)->orderBy('orden')->get(),
        ]);
    }

    // Obtener la lista de tipos de vehículo disponibles (Para el primer select en React)
    public function getTiposVehiculo()
    {
        $tipos = TipoVehiculo::where('activo', true)->orderBy('orden')->get();
        return response()->json($tipos);
    }
}
