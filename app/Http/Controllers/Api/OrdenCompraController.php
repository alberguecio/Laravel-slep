<?php

namespace App\Http\Controllers\Api;

use App\Models\OrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrdenCompraController
{
    public function index(): JsonResponse
    {
        try {
            $ordenes = OrdenCompra::with(['proyecto', 'oferente'])
                ->orderBy('fecha', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $ordenes,
                'total' => $ordenes->count()
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
            $orden = OrdenCompra::with(['proyecto', 'oferente', 'ordenesTrabajo'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $orden
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Orden de compra no encontrada'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero' => 'required|string|max:50',
                'proyecto_id' => 'required|exists:proyectos,id',
                'oferente_id' => 'nullable|exists:oferentes,id',
                'monto_total' => 'required|numeric|min:0',
                'estado' => 'nullable|string|max:20',
                'fecha' => 'nullable|date'
            ]);

            $orden = OrdenCompra::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de compra creada exitosamente',
                'data' => $orden
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
            $orden = OrdenCompra::findOrFail($id);

            $validated = $request->validate([
                'numero' => 'sometimes|required|string|max:50',
                'proyecto_id' => 'sometimes|required|exists:proyectos,id',
                'oferente_id' => 'nullable|exists:oferentes,id',
                'monto_total' => 'sometimes|required|numeric|min:0',
                'estado' => 'nullable|string|max:20',
                'fecha' => 'nullable|date'
            ]);

            $orden->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de compra actualizada exitosamente',
                'data' => $orden
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Orden de compra no encontrada'
            ], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            
            // Verificar si tiene órdenes de trabajo asociadas
            if ($orden->ordenesTrabajo()->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede eliminar la orden de compra porque tiene órdenes de trabajo asociadas'
                ], 400);
            }
            
            $orden->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de compra eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Orden de compra no encontrada'
            ], 404);
        }
    }
}
