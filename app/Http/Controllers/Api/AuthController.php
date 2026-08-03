<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // <-- Aquí está la clave corregida
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Y aquí asegúrate de usar \App\Models\User o simplemente User si pusiste el 'use' de arriba
        $usuario = User::where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $usuario->createToken('token_perito_orinoquia')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->name ?? 'Usuario',
                'email' => $usuario->email,
                'rol' => $usuario->rol ?? 'inspector'
            ]
        ]);
    }
}
