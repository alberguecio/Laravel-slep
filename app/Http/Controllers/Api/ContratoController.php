<?php

namespace App\Http\Controllers\Api;

use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContratoController
{
    public function index(): JsonResponse
    {
        try {
            $contratos = Contrato::with(['proyecto'])
                ->orderBy('numero_contrato')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $contratos,
                'total' => $contratos->count()
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
            $contrato = Contrato::with(['proyecto', 'ordenesTrabajo'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $contrato
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Contrato no encontrado'
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'proyecto_id' => 'required|exists:proyectos,id',
                'numero_contrato' => 'required|string|max:50|unique:contratos',
                'nombre_contrato' => 'nullable|string|max:200',
                'monto_real' => 'required|numeric|min:0',
                'estado' => 'nullable|string|max:20',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
                'observaciones' => 'nullable|string'
            ]);

            $contrato = Contrato::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Contrato creado exitosamente',
                'data' => $contrato
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
            $contrato = Contrato::findOrFail($id);

            $validated = $request->validate([
                'proyecto_id' => 'sometimes|required|exists:proyectos,id',
                'numero_contrato' => 'sometimes|required|string|max:50|unique:contratos,numero_contrato,' . $id,
                'nombre_contrato' => 'nullable|string|max:200',
                'monto_real' => 'sometimes|required|numeric|min:0',
                'estado' => 'nullable|string|max:20',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
                'observaciones' => 'nullable|string'
            ]);

            $contrato->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Contrato actualizado exitosamente',
                'data' => $contrato
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Contrato no encontrado'
            ], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $contrato = Contrato::findOrFail($id);
            
            // Verificar si tiene órdenes de trabajo asociadas
            if ($contrato->ordenesTrabajo()->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede eliminar el contrato porque tiene órdenes de trabajo asociadas'
                ], 400);
            }
            
            $contrato->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Contrato eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Contrato no encontrado'
            ], 404);
        }
    }
}
