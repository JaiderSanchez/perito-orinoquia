<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    // 1. Obtener todos los tipos de vehículo activos
    public function tiposVehiculo()
    {
        $tipos = DB::table('tipos_vehiculo')->where('activo', true)->orderBy('orden')->get();
        return response()->json($tipos);
    }

    // 2. Obtener accesorios según el tipo de vehículo (por código o ID)
    public function accesorios($tipoVehiculoId)
    {
        $accesorios = DB::table('catalogo_accesorios')
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return response()->json($accesorios);
    }

    // 3. Obtener piezas de carrocería (daños externos) según el tipo de vehículo
    public function piezasCarroceria($tipoVehiculoId)
    {
        $piezas = DB::table('catalogo_piezas_carroceria')
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return response()->json($piezas);
    }

    // 4. Obtener zonas de cabina (daños internos) según el tipo de vehículo
    public function zonasCabina($tipoVehiculoId)
    {
        $zonas = DB::table('catalogo_zonas_cabina')
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return response()->json($zonas);
    }

    // 5. Obtener elementos técnicos globales
    public function elementosTecnicos()
    {
        $elementos = DB::table('catalogo_elementos_tecnicos')->where('activo', true)->orderBy('orden')->get();
        return response()->json($elementos);
    }

    // 6. Obtener sistemas mecánicos según el tipo de vehículo
    public function sistemasMecanicos($tipoVehiculoId)
    {
        $sistemas = DB::table('catalogo_sistemas_mecanicos')
            ->where('tipo_vehiculo_id', $tipoVehiculoId)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return response()->json($sistemas);
    }
}
