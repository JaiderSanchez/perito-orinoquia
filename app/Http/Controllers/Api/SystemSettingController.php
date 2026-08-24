<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(SystemSetting::configuration());
    }

    public function update(Request $request)
    {
        abort_unless($request->user()?->rol === 'superadmin', 403, 'Solo el superadmin puede modificar la configuración global.');

        $data = $request->validate([
            'nombreEmpresa' => 'required|string|max:160', 'nit' => 'nullable|string|max:40',
            'telefono' => 'nullable|string|max:40', 'direccion' => 'nullable|string|max:200',
            'ciudad' => 'nullable|string|max:100', 'emailEmpresa' => 'nullable|email|max:160',
            'scoreInicialDefecto' => 'required|integer|min:0|max:100',
            'exigirFotosDocumentos' => 'required|boolean', 'modoOscuroPorDefecto' => 'required|boolean',
            'textoLegalPdf' => 'nullable|string|max:5000', 'limiteUsuarios' => 'required|integer|min:1|max:10000',
        ]);

        SystemSetting::updateOrCreate(['key' => 'configuration'], ['value' => $data, 'updated_by' => $request->user()->id]);
        return response()->json(['message' => 'Configuración guardada.', 'configuration' => $data]);
    }
}
