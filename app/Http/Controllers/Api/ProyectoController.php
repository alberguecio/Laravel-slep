<?php

namespace App\Http\Controllers\Api;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProyectoController
{
    /**
     * Listar todos los proyectos
     */
    public function index(): JsonResponse
    {
        try {
            $proyectos = Proyecto::with(['item', 'oferente', 'contratos'])
                ->orderBy('nombre')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $proyectos,
                'total' => $proyectos->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un proyecto específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $proyecto = Proyecto::with(['item', 'oferente', 'contratos'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $proyecto
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proyecto no encontrado'
            ], 404);
        }
    }

    /**
     * Crear un nuevo proyecto
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:150',
                'item_id' => 'required|exists:items,id',
                'monto_asignado' => 'required|numeric|min:0',
                'estado' => 'nullable|string|max:20',
                'codigo_idi' => 'nullable|string|max:50',
                'oferente_id' => 'nullable|exists:oferentes,id'
            ]);

            $proyecto = Proyecto::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Proyecto creado exitosamente',
                'data' => $proyecto
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

    /**
     * Actualizar un proyecto existente
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $proyecto = Proyecto::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:150',
                'item_id' => 'sometimes|required|exists:items,id',
                'monto_asignado' => 'sometimes|required|numeric|min:0',
                'estado' => 'nullable|string|max:20',
                'codigo_idi' => 'nullable|string|max:50',
                'oferente_id' => 'nullable|exists:oferentes,id'
            ]);

            $proyecto->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Proyecto actualizado exitosamente',
                'data' => $proyecto
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proyecto no encontrado'
            ], 404);
        }
    }

    /**
     * Eliminar un proyecto
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $proyecto = Proyecto::findOrFail($id);
            
            // Verificar si tiene contratos asociados
            if ($proyecto->contratos()->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede eliminar el proyecto porque tiene contratos asociados'
                ], 400);
            }
            
            $proyecto->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Proyecto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proyecto no encontrado'
            ], 404);
        }
    }
}
