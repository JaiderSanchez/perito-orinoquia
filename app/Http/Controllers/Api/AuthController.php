<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Listar todos los usuarios (Soluciona el error GET /api/users)
     */
    public function index()
    {
        $usuarios = User::all();
        return response()->json($usuarios, 200);
    }

    public function store(Request $request)
    {
        // Normalizamos el texto (lo pasamos a minúsculas y quitamos espacios extra)
        if ($request->has('rol')) {
            $request->merge([
                'rol' => strtolower(trim($request->rol))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'rol' => 'required|string|in:tecnico,inspector,admin',
        ]);

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'usuario' => $usuario
        ], 201);
    }

    public function show(User $user)
    {
        // Cargamos los peritajes asociados al usuario
        $user->load('peritajes');

        return response()->json([
            'success' => true,
            'usuario' => $user
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

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

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $usuario = $request->user();

        if (! Hash::check($request->current_password, $usuario->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $usuario->password = Hash::make($request->new_password);
        $usuario->save();

        return response()->json([
            'message' => '¡Contraseña actualizada exitosamente!'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($request->has('rol')) {
            $request->merge([
                'rol' => strtolower(trim($request->rol))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'rol' => 'required|string|in:tecnico,inspector,admin',
        ]);

        $user->update($request->all());

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'usuario' => $user
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }
}
