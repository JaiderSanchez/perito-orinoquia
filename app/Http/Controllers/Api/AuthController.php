<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Iniciar sesión.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $user = User::with('sucursal')
            ->where(function ($query) use ($request) {
                $query->where('email', $request->login)
                    ->orWhere('username', $request->login)
                    ->orWhere('name', $request->login);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales proporcionadas son incorrectas.'],
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
                ->when(!$request->boolean('include_archived'), fn ($query) => $query->where('oculto', false))
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

        $limiteUsuarios = (int) (SystemSetting::configuration()['limiteUsuarios'] ?? 50);
        if (User::where('oculto', false)->count() >= $limiteUsuarios) {
            return response()->json(['error' => "Se alcanzó el límite de {$limiteUsuarios} usuarios configurado por el superadmin."], 422);
        }

        if ($request->has('rol')) {
            $request->merge([
                'rol' => strtolower(trim($request->rol))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:3|max:60|alpha_dash|unique:users,username',

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()],

            'sucursal_id' => [
                'required',
                'uuid',
                'exists:sucursales,id',
            ],

            'rol' => [
                'required',
                'string',
                'in:tecnico,inspector,admin,superadmin',
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
        if ($usuarioActual->rol !== 'superadmin' && $request->rol === 'superadmin') {
            return response()->json(['error' => 'Solo el superadmin puede crear otra cuenta superadmin.'], 403);
        }

        $usuario = User::create([
            'name' => $request->name,
            'username' => $request->username,
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
        if ($user->rol === 'superadmin' && $usuarioActual->rol !== 'superadmin') {
            return response()->json([
                'error' => 'El usuario superadmin no puede ser modificado.'
            ], 403);
        }

        /*
         * Los administradores tampoco se modifican
         * desde Gestión de Usuarios.
         */
        if ($user->rol === 'admin' && $usuarioActual->rol !== 'superadmin') {
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
            'username' => 'nullable|string|min:3|max:60|alpha_dash|unique:users,username,' . $user->id,

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],

            'password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers()],

            'rol' => [
                'required',
                'string',
                'in:tecnico,inspector,admin,superadmin',
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
        if ($usuarioActual->rol !== 'superadmin' && $request->rol === 'superadmin') {
            return response()->json(['error' => 'Solo el superadmin puede asignar ese rol.'], 403);
        }
        if ($user->rol === 'superadmin' && ($request->rol !== 'superadmin' || !$request->boolean('activo'))
            && User::where('rol', 'superadmin')->where('activo', true)->count() <= 1) {
            return response()->json(['error' => 'No se puede desactivar ni degradar la última cuenta superadmin activa.'], 422);
        }

        $data = [
            'name' => $request->name,
            'username' => $request->username,
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

        if ($user->rol === 'superadmin' && (
            $usuarioActual->rol !== 'superadmin'
            || User::where('rol', 'superadmin')->where('activo', true)->count() <= 1
        )) {
            return response()->json([
                'error' => 'El usuario superadmin no puede ser eliminado.'
            ], 403);
        }

        if ($user->rol === 'admin' && $usuarioActual->rol !== 'superadmin') {
            return response()->json([
                'error' => 'Los usuarios administradores no se pueden eliminar desde Gestión de Usuarios.'
            ], 403);
        }

        if ($usuarioActual->rol !== 'superadmin') {
            return response()->json(['error' => 'Solo el superadmin puede archivar usuarios.'], 403);
        }

        $user->update(['activo' => false, 'oculto' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario archivado. Sus peritajes históricos se conservaron.'
        ], 200);
    }

    public function restore(Request $request, User $user)
    {
        abort_unless($request->user()?->rol === 'superadmin', 403, 'Solo el superadmin puede restaurar usuarios.');
        $user->update(['oculto' => false, 'activo' => false]);
        return response()->json(['message' => 'Usuario restaurado como inactivo. Puedes activarlo cuando corresponda.']);
    }

    /**
     * Actualizar contraseña del usuario autenticado.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
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
