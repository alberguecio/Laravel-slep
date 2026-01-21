<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si hay usuario en la sesión
        $userSession = session('user');
        $token = session('token');
        
        // Si no hay usuario ni token, redirigir al login
        if (!$userSession && !$token) {
            return redirect('/login')->with('error', 'Por favor inicia sesión para continuar');
        }
        
        // Si hay token pero no usuario, intentar obtener el usuario del token
        if (!$userSession && $token) {
            try {
                $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
                if ($user) {
                    // Restaurar la sesión del usuario
                    $userData = [
                        'id' => $user->id,
                        'nombre' => $user->nombre,
                        'email' => $user->email,
                        'rol' => $user->rol,
                        'cargo' => $user->cargo,
                        'permisos' => $user->permisos,
                        'estado' => $user->estado
                    ];
                    session(['user' => $userData]);
                } else {
                    // Token inválido, limpiar y redirigir
                    session()->forget(['user', 'token']);
                    return redirect('/login')->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente');
                }
            } catch (\Exception $e) {
                // Token expirado o inválido, limpiar y redirigir
                session()->forget(['user', 'token']);
                return redirect('/login')->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente');
            }
        }
        
        return $next($request);
    }
}
