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
     * Listar todos los usuarios.
     *
     * Esta ruta está protegida por el middleware "admin"
     * desde routes/api.php.
     */
    public function index()
    {
        $usuarios = User::with('sucursal')->get();

        return response()->json($usuarios, 200);
    }

    /**
     * Crear un nuevo usuario.
     *
     * Solo administradores deben tener acceso a esta función.
     */
    public function store(Request $request)
    {
        // Normalizamos el rol.
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
        ]);

        // Cargamos la sucursal para devolverla en la respuesta.
        $usuario->load('sucursal');

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'usuario' => $usuario
        ], 201);
    }

    /**
     * Mostrar un usuario específico.
     *
     * Solo administradores deben tener acceso.
     */
    public function show(User $user)
    {
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
     * Actualizar un usuario específico.
     *
     * Solo administradores deben tener acceso.
     */
    public function update(Request $request, User $user)
    {
        // Normalizamos el rol.
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
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol,
            'sucursal_id' => $request->sucursal_id,
        ];

        // Solo cambia la contraseña si se envió una nueva.
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Volvemos a cargar los datos desde la base de datos.
        $user->refresh();
        $user->load('sucursal');

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'usuario' => $user
        ], 200);
    }

    /**
     * Eliminar un usuario.
     *
     * Solo administradores deben tener acceso.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }

    /**
     * Login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $usuario = User::with('sucursal')
            ->where('email', $request->email)
            ->first();

        if (
            !$usuario ||
            !Hash::check($request->password, $usuario->password)
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Las credenciales proporcionadas son incorrectas.'
                ],
            ]);
        }

        $token = $usuario
            ->createToken('token_perito_orinoquia')
            ->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',

            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->name ?? 'Usuario',
                'email' => $usuario->email,
                'rol' => $usuario->rol ?? 'inspector',
                'sucursal_id' => $usuario->sucursal_id,

                'sucursal' => $usuario->sucursal ? [
                    'id' => $usuario->sucursal->id,
                    'nombre' => $usuario->sucursal->nombre,
                ] : null,
            ],
        ]);
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

        if (
            !Hash::check(
                $request->current_password,
                $usuario->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'La contraseña actual es incorrecta.'
                ],
            ]);
        }

        $usuario->password = Hash::make(
            $request->new_password
        );

        $usuario->save();

        return response()->json([
            'message' => '¡Contraseña actualizada exitosamente!'
        ]);
    }

    /**
     * Actualizar perfil del usuario autenticado.
     *
     * Aquí NO permitimos modificar:
     * - rol
     * - sucursal_id
     *
     * Esos datos son responsabilidad del administrador.
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
            'message' => 'Perfil actualizado correctamente',
            'usuario' => $user,
        ]);
    }
}
