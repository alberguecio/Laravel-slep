<?php

namespace App\Http\Controllers\Api;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RequerimientoController
{
    public function index(): JsonResponse
    {
        try {
            $requerimientos = Requerimiento::with(['comuna', 'establecimiento', 'usuarioCreador'])
                ->orderBy('fecha_ingreso', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $requerimientos,
                'total' => $requerimientos->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $requerimiento = Requerimiento::with(['comuna', 'establecimiento', 'usuarioCreador', 'comentarios'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $requerimiento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Requerimiento no encontrado'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comuna_id' => 'required|exists:comunas,id',
                'establecimiento_id' => 'nullable|exists:establecimientos,id',
                'descripcion' => 'required|string',
                'estado' => 'nullable|string|max:20',
                'via_solicitud' => 'required|string|max:20',
                'fecha_email' => 'nullable|date',
                'numero_oficio' => 'nullable|string|max:50',
                'fecha_oficio' => 'nullable|date'
            ]);

            $validated['usuario_creador_id'] = 1; // TODO: usar usuario autenticado
            $validated['estado'] = $validated['estado'] ?? 'pendiente';

            $requerimiento = Requerimiento::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Requerimiento creado exitosamente',
                'data' => $requerimiento
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $requerimiento = Requerimiento::findOrFail($id);

            $validated = $request->validate([
                'comuna_id' => 'sometimes|required|exists:comunas,id',
                'establecimiento_id' => 'nullable|exists:establecimientos,id',
                'descripcion' => 'sometimes|required|string',
                'estado' => 'nullable|string|max:20',
                'via_solicitud' => 'sometimes|required|string|max:20',
                'fecha_email' => 'nullable|date',
                'numero_oficio' => 'nullable|string|max:50',
                'fecha_oficio' => 'nullable|date'
            ]);

            $validated['usuario_mod_id'] = 1; // TODO: usar usuario autenticado

            $requerimiento->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Requerimiento actualizado exitosamente',
                'data' => $requerimiento
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Requerimiento no encontrado'
            ], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $requerimiento = Requerimiento::findOrFail($id);
            $requerimiento->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Requerimiento eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Requerimiento no encontrado'
            ], 404);
        }
    }
}
