<?php

namespace App\Http\Controllers\Api;

use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrdenTrabajoController
{
    public function index(): JsonResponse
    {
        try {
            $ordenes = OrdenTrabajo::with(['comuna', 'establecimiento', 'convenio', 'oferente', 'ordenCompra', 'contrato'])
                ->orderBy('fecha_ot', 'desc')
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
            $orden = OrdenTrabajo::with(['comuna', 'establecimiento', 'convenio', 'oferente', 'ordenCompra', 'contrato', 'presupuestoOt'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $orden
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Orden de trabajo no encontrada'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comuna_id' => 'nullable|exists:comunas,id',
                'establecimiento_id' => 'nullable|exists:establecimientos,id',
                'convenio_id' => 'nullable|exists:convenios,id',
                'oferente_id' => 'nullable|exists:oferentes,id',
                'numero_ot' => 'nullable|string|max:20',
                'fecha_ot' => 'nullable|date',
                'monto' => 'nullable|numeric|min:0',
                'orden_compra_id' => 'nullable|exists:ordenes_compra,id',
                'contrato_id' => 'nullable|exists:contratos,id',
                'observacion' => 'nullable|string'
            ]);

            $orden = OrdenTrabajo::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de trabajo creada exitosamente',
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
            $orden = OrdenTrabajo::findOrFail($id);

            $validated = $request->validate([
                'comuna_id' => 'nullable|exists:comunas,id',
                'establecimiento_id' => 'nullable|exists:establecimientos,id',
                'convenio_id' => 'nullable|exists:convenios,id',
                'oferente_id' => 'nullable|exists:oferentes,id',
                'numero_ot' => 'nullable|string|max:20',
                'fecha_ot' => 'nullable|date',
                'monto' => 'nullable|numeric|min:0',
                'orden_compra_id' => 'nullable|exists:ordenes_compra,id',
                'contrato_id' => 'nullable|exists:contratos,id',
                'observacion' => 'nullable|string'
            ]);

            $orden->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de trabajo actualizada exitosamente',
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
                'error' => 'Orden de trabajo no encontrada'
            ], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $orden = OrdenTrabajo::findOrFail($id);
            $orden->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de trabajo eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Orden de trabajo no encontrada'
            ], 404);
        }
    }
}
