<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('sucursal')
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->activo) {
            return response()->json([
                'error' => 'Este usuario se encuentra inactivo.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'usuario' => $user,
        ], 200);
    }

    public function index(Request $request)
    {
        $usuarioActual = $request->user();

        if ($usuarioActual?->rol === 'superadmin') {
            $usuarios = User::with('sucursal')
                ->where('activo', true)
                ->get();
        } else {
            $usuarios = User::with('sucursal')
                ->where('activo', true)
                ->where('rol', '!=', 'superadmin')
                ->where('oculto', false)
                ->get();
        }

        return response()->json($usuarios, 200);
    }

    public function store(Request $request)
    {
        if ($request->has('rol')) {
            $request->merge([
                'rol' => strtolower(trim($request->rol))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => 'required|string|min:8',
            'sucursal_id' => [
                'required',
                'uuid',
                'exists:sucursales,id',
            ],
            'rol' => [
                'required',
                'string',
                'in:tecnico,inspector,admin',
            ],
        ]);

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'sucursal_id' => $request->sucursal_id,
            'oculto' => false,
            'activo' => $request->boolean('activo', true),
        ]);

        $usuario->load('sucursal');

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'usuario' => $usuario
        ], 201);
    }

    public function show(User $user)
    {
        if ($user->oculto) {
            return response()->json([
                'error' => 'Usuario no encontrado.'
            ], 404);
        }

        $user->load([
            'sucursal',
            'peritajes'
        ]);

        return response()->json([
            'success' => true,
            'usuario' => $user
        ], 200);
    }

    public function update(Request $request, User $user)
    {
        $usuarioActual = $request->user();

        if ($user->rol === 'superadmin') {
            return response()->json([
                'error' => 'El usuario superadmin no puede ser modificado.'
            ], 403);
        }

        if ($user->rol === 'admin') {
            return response()->json([
                'error' => 'Los usuarios administradores no se pueden modificar desde Gestión de Usuarios. Deben modificar sus datos desde su propio perfil.'
            ], 403);
        }

        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'superadmin'], true)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar usuarios.'
            ], 403);
        }

        if ($usuarioActual->rol !== 'superadmin' && $user->oculto) {
            return response()->json([
                'error' => 'Usuario no encontrado.'
            ], 404);
        }

        if ($request->has('rol')) {
            $request->merge([
                'rol' => strtolower(trim($request->rol))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'password' => 'nullable|string|min:8',
            'rol' => [
                'required',
                'string',
                'in:tecnico,inspector,admin',
            ],
            'sucursal_id' => [
                'required',
                'uuid',
                'exists:sucursales,id',
            ],
            'activo' => 'boolean',
        ]);

        if ($usuarioActual->rol === 'admin' && $request->rol === 'admin') {
            return response()->json([
                'error' => 'No tienes permisos para asignar el rol de administrador.'
            ], 403);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol,
            'sucursal_id' => $request->sucursal_id,
        ];

        if ($request->has('activo')) {
            $data['activo'] = $request->boolean('activo');
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->refresh();
        $user->load('sucursal');

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $user,
        ], 200);
    }

    public function destroy(Request $request, User $user)
    {
        $usuarioActual = $request->user();

        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'superadmin'], true)) {
            return response()->json([
                'error' => 'No tienes permisos para eliminar usuarios.'
            ], 403);
        }

        if ($usuarioActual->id === $user->id) {
            return response()->json([
                'error' => 'No puedes eliminar tu propio usuario.'
            ], 403);
        }

        if ($user->rol === 'superadmin') {
            return response()->json([
                'error' => 'El usuario superadmin no puede ser eliminado.'
            ], 403);
        }

        if ($user->rol === 'admin') {
            return response()->json([
                'error' => 'Los usuarios administradores no se pueden eliminar desde Gestión de Usuarios.'
            ], 403);
        }

        $user->update([
            'activo' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.'
        ], 200);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $usuario = $request->user();

        if (!Hash::check($request->current_password, $usuario->password)) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'La contraseña actual es incorrecta.'
                ],
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

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $user->id,
            ],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $user->refresh();
        $user->load('sucursal');

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'usuario' => $user,
        ]);
    }
}
