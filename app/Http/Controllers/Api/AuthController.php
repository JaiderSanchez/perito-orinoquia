<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión.
     */
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

        // Los usuarios inactivos no pueden iniciar sesión.
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

    /**
     * Listar usuarios.
     *
     * Superadmin:
     * - Puede ver todos los usuarios.
     *
     * Admin:
     * - Puede ver usuarios activos e inactivos.
     * - No puede ver usuarios ocultos.
     * - No puede ver superadmins.
     */
    public function index(Request $request)
    {
        $usuarioActual = $request->user();

        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'superadmin'], true)) {
            return response()->json([
                'error' => 'No tienes permisos para consultar los usuarios.'
            ], 403);
        }

        if ($usuarioActual->rol === 'superadmin') {
            $usuarios = User::with('sucursal')
                ->where('oculto', false)
                ->get();
        } else {
            $usuarios = User::with('sucursal')
                ->where('rol', '!=', 'superadmin')
                ->where('oculto', false)
                ->get();
        }

        return response()->json($usuarios, 200);
    }

    /**
     * Crear usuario.
     */
    public function store(Request $request)
    {
        $usuarioActual = $request->user();

        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'superadmin'], true)) {
            return response()->json([
                'error' => 'No tienes permisos para crear usuarios.'
            ], 403);
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

            'activo' => 'boolean',
        ]);

        /*
         * Un admin no puede crear otro admin.
         * Solo el superadmin puede asignar el rol admin.
         */
        if ($usuarioActual->rol === 'admin' && $request->rol === 'admin') {
            return response()->json([
                'error' => 'No tienes permisos para crear usuarios con rol administrador.'
            ], 403);
        }

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

    /**
     * Mostrar un usuario.
     */
    public function show(Request $request, User $user)
    {
        $usuarioActual = $request->user();

        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'superadmin'], true)) {
            return response()->json([
                'error' => 'No tienes permisos para consultar usuarios.'
            ], 403);
        }

        if ($user->oculto) {
            return response()->json([
                'error' => 'Usuario no encontrado.'
            ], 404);
        }

        /*
         * Un admin no puede consultar información de un superadmin.
         */
        if ($usuarioActual->rol === 'admin' && $user->rol === 'superadmin') {
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

    /**
     * Actualizar usuario.
     *
     * Permite modificar el estado activo/inactivo.
     *
     * Admin:
     * - Puede activar/desactivar técnicos e inspectores.
     *
     * Superadmin:
     * - Puede activar/desactivar técnicos e inspectores.
     *
     * Ninguno puede modificar un superadmin.
     * Los administradores tampoco se modifican desde Gestión de Usuarios.
     */
    public function update(Request $request, User $user)
    {
        $usuarioActual = $request->user();

        /*
         * Verificar permisos generales.
         */
        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'superadmin'], true)) {
            return response()->json([
                'error' => 'No tienes permisos para modificar usuarios.'
            ], 403);
        }

        /*
         * El superadmin nunca puede ser modificado
         * desde Gestión de Usuarios.
         */
        if ($user->rol === 'superadmin') {
            return response()->json([
                'error' => 'El usuario superadmin no puede ser modificado.'
            ], 403);
        }

        /*
         * Los administradores tampoco se modifican
         * desde Gestión de Usuarios.
         */
        if ($user->rol === 'admin') {
            return response()->json([
                'error' => 'Los usuarios administradores no se pueden modificar desde Gestión de Usuarios. Deben modificar sus datos desde su propio perfil.'
            ], 403);
        }

        /*
         * Los usuarios ocultos solo pueden ser modificados
         * por el superadmin.
         */
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

            'activo' => 'required|boolean',
        ]);

        /*
         * Un admin no puede asignar el rol admin.
         */
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

            /*
             * IMPORTANTE:
             * Aquí se guarda directamente el nuevo estado.
             *
             * true  = usuario activo
             * false = usuario inactivo
             */
            'activo' => $request->boolean('activo'),
        ];

        /*
         * La contraseña solamente se actualiza
         * si se envió una nueva.
         */
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $user->refresh();
        $user->load('sucursal');

        return response()->json([
            'success' => true,
            'message' => $user->activo
                ? 'Usuario activado correctamente.'
                : 'Usuario desactivado correctamente.',
            'usuario' => $user,
        ], 200);
    }

    /**
     * Desactivar usuario.
     *
     * No elimina físicamente al usuario.
     */
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
            'message' => 'Usuario desactivado correctamente.'
        ], 200);
    }

    /**
     * Actualizar contraseña del usuario autenticado.
     */
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

    /**
     * Actualizar perfil del usuario autenticado.
     */
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
