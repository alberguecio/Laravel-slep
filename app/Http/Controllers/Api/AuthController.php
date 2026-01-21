<?php

namespace App\Http\Controllers\Api;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController
{
    /**
     * Login del usuario
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            $usuario = Usuario::where('email', $request->email)->first();

            if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Credenciales inválidas'
                    ], 401);
                }
                return redirect('/login')->withErrors(['email' => 'Credenciales inválidas']);
            }

            if ($usuario->estado !== 'activo') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Usuario inactivo. Contacte al administrador.'
                    ], 403);
                }
                return redirect('/login')->withErrors(['email' => 'Usuario inactivo. Contacte al administrador.']);
            }

            // Actualizar último acceso
            $usuario->ultimo_acceso = now();
            $usuario->save();

            // Generar token
            try {
                $token = JWTAuth::fromUser($usuario);
            } catch (JWTException $e) {
                \Log::error('Error generando token JWT: ' . $e->getMessage());
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Error al generar token de autenticación'
                    ], 500);
                }
                return redirect('/login')->withErrors(['email' => 'Error al iniciar sesión. Intente nuevamente.']);
            }

            // Datos del usuario (sin password)
            $userData = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'cargo' => $usuario->cargo,
                'permisos' => $usuario->permisos,
                'estado' => $usuario->estado
            ];

            // Guardar en sesión para web con tiempo de vida extendido
            session(['user' => $userData, 'token' => $token]);
            
            // Configurar cookie de sesión persistente (7 días)
            config(['session.lifetime' => 10080]); // 7 días en minutos
            config(['session.expire_on_close' => false]);
            
            // Si es petición de web, redirigir
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'data' => [
                        'user' => $userData,
                        'token' => $token,
                        'token_type' => 'bearer'
                    ]
                ]);
            }
            
            // Redirigir a dashboard
            return redirect('/')->with('success', 'Bienvenido ' . $usuario->nombre);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect('/login')->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Error en login: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return redirect('/login')->withErrors(['email' => 'Error al iniciar sesión. Contacte al administrador.']);
        }
    }

    /**
     * Logout del usuario
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al hacer logout'
            ], 500);
        }
    }

    /**
     * Obtener usuario actual autenticado
     */
    public function me(): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            
            $userData = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'cargo' => $usuario->cargo,
                'permisos' => $usuario->permisos,
                'estado' => $usuario->estado,
                'ultimo_acceso' => $usuario->ultimo_acceso
            ];

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], 401);
        }
    }

    /**
     * Renovar token
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $newToken,
                    'token_type' => 'bearer'
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al renovar token'
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6'
            ]);

            $usuario = JWTAuth::parseToken()->authenticate();

            if (!Hash::check($request->current_password, $usuario->password_hash)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Contraseña actual incorrecta'
                ], 400);
            }

            $usuario->password_hash = Hash::make($request->new_password);
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], 401);
        }
    }

    /**
     * Verificar permisos del usuario
     */
    public function permissions(): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            
            return response()->json([
                'success' => true,
                'data' => $usuario->permisos ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], 401);
        }
    }
}
