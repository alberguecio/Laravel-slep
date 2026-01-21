<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProyectoController;
use App\Http\Controllers\Api\ContratoController;
use App\Http\Controllers\Api\OrdenCompraController;
use App\Http\Controllers\Api\OrdenTrabajoController;
use App\Http\Controllers\Api\RequerimientoController;
use App\Http\Controllers\Api\AuthController;

// Rutas de autenticación (sin middleware)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('jwt.refresh');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('jwt.auth');
Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->middleware('jwt.auth');
Route::get('/auth/permissions', [AuthController::class, 'permissions'])->middleware('jwt.auth');

// Test básico
Route::get('/test', function () {
    return response()->json([
        'message' => '🚀 API Laravel funcionando correctamente',
        'status' => 'ok',
        'modelos' => [
            'usuario', 'proyecto', 'contrato', 'orden_compra', 
            'orden_trabajo', 'requerimiento', 'comuna', 'establecimiento',
            'item', 'oferente', 'convenio', 'precio_unitario',
            'presupuesto_ot', 'presupuesto_ot_item'
        ]
    ]);
});

// Rutas de Proyectos (Protegidas con JWT)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/proyectos', [ProyectoController::class, 'index']);
    Route::get('/proyectos/{id}', [ProyectoController::class, 'show']);
    Route::post('/proyectos', [ProyectoController::class, 'store']);
    Route::put('/proyectos/{id}', [ProyectoController::class, 'update']);
    Route::delete('/proyectos/{id}', [ProyectoController::class, 'destroy']);
});

// Rutas de Contratos (Protegidas con JWT)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/contratos', [ContratoController::class, 'index']);
    Route::get('/contratos/{id}', [ContratoController::class, 'show']);
    Route::post('/contratos', [ContratoController::class, 'store']);
    Route::put('/contratos/{id}', [ContratoController::class, 'update']);
    Route::delete('/contratos/{id}', [ContratoController::class, 'destroy']);
});

// Rutas de Órdenes de Compra (Protegidas con JWT)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/ordenes_compra', [OrdenCompraController::class, 'index']);
    Route::get('/ordenes_compra/{id}', [OrdenCompraController::class, 'show']);
    Route::post('/ordenes_compra', [OrdenCompraController::class, 'store']);
    Route::put('/ordenes_compra/{id}', [OrdenCompraController::class, 'update']);
    Route::delete('/ordenes_compra/{id}', [OrdenCompraController::class, 'destroy']);
});

// Rutas de Órdenes de Trabajo (Protegidas con JWT)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/ordenes_trabajo', [OrdenTrabajoController::class, 'index']);
    Route::get('/ordenes_trabajo/{id}', [OrdenTrabajoController::class, 'show']);
    Route::post('/ordenes_trabajo', [OrdenTrabajoController::class, 'store']);
    Route::put('/ordenes_trabajo/{id}', [OrdenTrabajoController::class, 'update']);
    Route::delete('/ordenes_trabajo/{id}', [OrdenTrabajoController::class, 'destroy']);
});

// Rutas de Requerimientos (Protegidas con JWT)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/requerimientos', [RequerimientoController::class, 'index']);
    Route::get('/requerimientos/{id}', [RequerimientoController::class, 'show']);
    Route::post('/requerimientos', [RequerimientoController::class, 'store']);
    Route::put('/requerimientos/{id}', [RequerimientoController::class, 'update']);
    Route::delete('/requerimientos/{id}', [RequerimientoController::class, 'destroy']);
});

// Rutas de Usuarios (protegidas)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/usuarios', function () {
        $usuarios = \App\Models\Usuario::select('id', 'nombre', 'email', 'rol', 'cargo', 'estado', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    });
});

