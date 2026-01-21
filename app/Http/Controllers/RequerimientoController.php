<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use App\Models\RequerimientoComentario;
use App\Models\Comuna;
use App\Models\Establecimiento;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequerimientoController extends Controller
{
    public function index(Request $request)
    {
        // Construir query con filtros
        $query = Requerimiento::with(['comuna', 'establecimiento', 'contrato']);
        
        // Filtro por comuna
        if ($request->has('comuna_id') && $request->comuna_id) {
            $query->where('comuna_id', $request->comuna_id);
        }
        
        // Filtro por contrato
        if ($request->has('contrato_id') && $request->contrato_id) {
            $query->where('contrato_id', $request->contrato_id);
        }
        
        // Filtro por urgencias (solo emergencias)
        if ($request->has('solo_urgencias') && $request->solo_urgencias) {
            $query->where('emergencia', true);
        }
        
        $requerimientos = $query->orderBy('fecha_ingreso', 'desc')->get();
        
        // Separar por estado (normalizar estados)
        $pendientes = $requerimientos->filter(function($req) {
            $estado = mb_strtolower($req->estado ?? '');
            return $estado === 'pendiente' || $estado === '';
        });
        $enProceso = $requerimientos->filter(function($req) {
            $estado = mb_strtolower($req->estado ?? '');
            return $estado === 'en proceso' || $estado === 'en_proceso' || $estado === 'proceso';
        });
        $resueltos = $requerimientos->filter(function($req) {
            $estado = mb_strtolower(trim($req->estado ?? ''));
            return $estado === 'resuelto';
        });
        
        // Obtener datos para el formulario (excluir contratos terminados)
        $comunas = Comuna::orderBy('nombre')->get();
        $contratos = Contrato::with('proyecto')
            ->whereRaw("TRIM(COALESCE(estado, '')) != 'Terminado'")
            ->orderBy('nombre_contrato')
            ->get();
        
        return view('requerimientos.index', compact(
            'pendientes',
            'enProceso',
            'resueltos',
            'comunas',
            'contratos'
        ));
    }
    
    public function show($id)
    {
        $requerimiento = Requerimiento::with(['comuna', 'establecimiento', 'contrato', 'usuarioCreador', 'usuarioMod'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'requerimiento' => [
                'id' => $requerimiento->id,
                'fecha_ingreso' => $requerimiento->fecha_ingreso ? $requerimiento->fecha_ingreso->format('d/m/Y H:i') : '-',
                'fecha_mod' => $requerimiento->fecha_mod ? $requerimiento->fecha_mod->format('d/m/Y H:i') : '-',
                'estado' => $requerimiento->estado,
                'emergencia' => $requerimiento->emergencia,
                'comuna_id' => $requerimiento->comuna_id,
                'establecimiento_id' => $requerimiento->establecimiento_id,
                'contrato_id' => $requerimiento->contrato_id,
                'comuna' => $requerimiento->comuna ? $requerimiento->comuna->nombre : '-',
                'establecimiento' => $requerimiento->establecimiento ? $requerimiento->establecimiento->nombre : '-',
                'contrato' => $requerimiento->contrato ? $requerimiento->contrato->nombre_contrato : '-',
                'via_solicitud' => $requerimiento->via_solicitud,
                'fecha_email' => $requerimiento->fecha_email ? $requerimiento->fecha_email->format('d/m/Y') : null,
                'numero_oficio' => $requerimiento->numero_oficio,
                'fecha_oficio' => $requerimiento->fecha_oficio ? $requerimiento->fecha_oficio->format('d/m/Y') : null,
                'descripcion' => $requerimiento->descripcion,
                'usuario_creador' => $requerimiento->usuarioCreador ? $requerimiento->usuarioCreador->nombre : 'Usuario desconocido',
                'usuario_mod' => $requerimiento->usuarioMod ? $requerimiento->usuarioMod->nombre : null
            ]
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'emergencia' => 'nullable|boolean',
            'comuna_id' => 'required|exists:comunas,id',
            'establecimiento_id' => 'nullable|exists:establecimientos,id',
            'contrato_id' => 'nullable|exists:contratos,id',
            'descripcion' => 'required|string',
            'via_solicitud' => 'required|in:Email,Oficio,Telefono',
            'fecha_email' => 'nullable|date|required_if:via_solicitud,Email',
            'numero_oficio' => 'nullable|string|max:50|required_if:via_solicitud,Oficio',
            'fecha_oficio' => 'nullable|date|required_if:via_solicitud,Oficio',
        ]);
        
        // Validar que si se asigna un contrato, no esté terminado
        if (!empty($validated['contrato_id'])) {
            $contrato = Contrato::findOrFail($validated['contrato_id']);
            $estadoContrato = trim($contrato->estado ?? '');
            if ($estadoContrato === 'Terminado') {
                return back()->withErrors([
                    'contrato_id' => 'No se pueden crear requerimientos para contratos terminados.'
                ])->withInput();
            }
        }
        
        $validated['estado'] = 'pendiente';
        $userSession = session('user');
        $validated['usuario_creador_id'] = $userSession['id'] ?? 1;
        
        // Convertir emergencia a boolean
        $validated['emergencia'] = isset($validated['emergencia']) && $validated['emergencia'] == '1';
        
        Requerimiento::create($validated);
        
        return redirect()->route('requerimientos.index')
            ->with('success', 'Requerimiento creado exitosamente');
    }
    
    public function edit($id)
    {
        $requerimiento = Requerimiento::with(['comuna', 'establecimiento', 'contrato'])
            ->findOrFail($id);
        
        // Verificar que el requerimiento esté pendiente
        $estado = mb_strtolower(trim($requerimiento->estado ?? ''));
        if ($estado !== 'pendiente' && $estado !== '') {
            return redirect()->route('requerimientos.index')
                ->with('error', 'Solo se pueden editar requerimientos pendientes.');
        }
        
        return response()->json([
            'success' => true,
            'requerimiento' => [
                'id' => $requerimiento->id,
                'emergencia' => $requerimiento->emergencia,
                'comuna_id' => $requerimiento->comuna_id,
                'establecimiento_id' => $requerimiento->establecimiento_id,
                'establecimiento' => $requerimiento->establecimiento ? $requerimiento->establecimiento->nombre : '',
                'contrato_id' => $requerimiento->contrato_id,
                'descripcion' => $requerimiento->descripcion,
                'via_solicitud' => $requerimiento->via_solicitud,
                'fecha_email' => $requerimiento->fecha_email ? $requerimiento->fecha_email->format('Y-m-d') : null,
                'numero_oficio' => $requerimiento->numero_oficio,
                'fecha_oficio' => $requerimiento->fecha_oficio ? $requerimiento->fecha_oficio->format('Y-m-d') : null
            ]
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $requerimiento = Requerimiento::findOrFail($id);
        
        // Verificar que el requerimiento esté pendiente
        $estado = mb_strtolower(trim($requerimiento->estado ?? ''));
        if ($estado !== 'pendiente' && $estado !== '') {
            return redirect()->route('requerimientos.index')
                ->with('error', 'Solo se pueden editar requerimientos pendientes.');
        }
        
        $validated = $request->validate([
            'emergencia' => 'nullable|boolean',
            'comuna_id' => 'required|exists:comunas,id',
            'establecimiento_id' => 'nullable|exists:establecimientos,id',
            'contrato_id' => 'nullable|exists:contratos,id',
            'descripcion' => 'required|string',
            'via_solicitud' => 'required|in:Email,Oficio,Telefono',
            'fecha_email' => 'nullable|date|required_if:via_solicitud,Email',
            'numero_oficio' => 'nullable|string|max:50|required_if:via_solicitud,Oficio',
            'fecha_oficio' => 'nullable|date|required_if:via_solicitud,Oficio',
        ]);
        
        $userSession = session('user');
        
        // Convertir emergencia a boolean
        $validated['emergencia'] = isset($validated['emergencia']) && $validated['emergencia'] == '1';
        
        // Actualizar campos de modificación
        $validated['usuario_mod_id'] = $userSession['id'] ?? 1;
        $validated['fecha_mod'] = now();
        
        $requerimiento->update($validated);
        
        return redirect()->route('requerimientos.index')
            ->with('success', 'Requerimiento actualizado exitosamente');
    }
    
    public function buscarEstablecimientos(Request $request)
    {
        $termino = $request->get('q', '');
        $comunaId = $request->get('comuna_id');
        
        $query = Establecimiento::with('comuna')
            ->where('nombre', 'like', '%' . $termino . '%');
        
        if ($comunaId) {
            $query->where('comuna_id', $comunaId);
        }
        
        $establecimientos = $query->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre', 'comuna_id']);
        
        return response()->json($establecimientos->map(function($est) {
            return [
                'id' => $est->id,
                'nombre' => $est->nombre,
                'comuna' => $est->comuna ? $est->comuna->nombre : ''
            ];
        }));
    }
    
    public function obtenerComentarios($id)
    {
        $requerimiento = Requerimiento::with(['comentarios.usuario'])->findOrFail($id);
        
        // Ordenar comentarios por fecha (más recientes primero)
        $comentarios = $requerimiento->comentarios->sortByDesc('created_at')->map(function($comentario) {
            return [
                'id' => $comentario->id,
                'comentario' => $comentario->comentario,
                'usuario' => $comentario->usuario ? $comentario->usuario->nombre : 'Usuario desconocido',
                'fecha' => $comentario->created_at ? $comentario->created_at->format('d/m/Y H:i') : '-',
                'fecha_raw' => $comentario->created_at ? $comentario->created_at->toDateTimeString() : null
            ];
        });
        
        return response()->json([
            'success' => true,
            'comentarios' => $comentarios->values()
        ]);
    }
    
    public function agregarComentario(Request $request, $id)
    {
        try {
            \Log::info('Agregar comentario - Request recibido', [
                'requerimiento_id' => $id,
                'request_data' => $request->all()
            ]);
            
            $validated = $request->validate([
                'comentario' => 'required|string|max:1000'
            ]);
            
            \Log::info('Agregar comentario - Validación exitosa', ['validated' => $validated]);
            
            $requerimiento = Requerimiento::findOrFail($id);
            \Log::info('Agregar comentario - Requerimiento encontrado', ['requerimiento_id' => $requerimiento->id]);
            
            $userSession = session('user');
            $usuarioId = $userSession['id'] ?? 1;
            \Log::info('Agregar comentario - Usuario', ['usuario_id' => $usuarioId, 'session' => $userSession]);
            
            $comentario = RequerimientoComentario::create([
                'requerimiento_id' => $requerimiento->id,
                'usuario_id' => $usuarioId,
                'comentario' => $validated['comentario']
            ]);
            
            \Log::info('Agregar comentario - Comentario creado', [
                'comentario_id' => $comentario->id,
                'requerimiento_id' => $comentario->requerimiento_id,
                'usuario_id' => $comentario->usuario_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Comentario agregado exitosamente',
                'comentario_id' => $comentario->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación al agregar comentario', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al agregar comentario', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el comentario: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function iniciarCreacionOT($id)
    {
        $requerimiento = Requerimiento::findOrFail($id);
        
        // Verificar que tenga los datos necesarios
        if (!$requerimiento->comuna_id || !$requerimiento->establecimiento_id || !$requerimiento->contrato_id) {
            return redirect()->route('requerimientos.index')
                ->with('error', 'El requerimiento no tiene todos los datos necesarios para crear una OT');
        }
        
        // Actualizar estado a "en proceso" si está pendiente
        $userSession = session('user');
        if (mb_strtolower($requerimiento->estado ?? '') === 'pendiente' || empty($requerimiento->estado)) {
            $requerimiento->update([
                'estado' => 'en proceso',
                'usuario_mod_id' => $userSession['id'] ?? 1,
                'fecha_mod' => now()
            ]);
            
            // Crear comentario automático
            RequerimientoComentario::create([
                'requerimiento_id' => $requerimiento->id,
                'usuario_id' => $userSession['id'] ?? 1,
                'comentario' => 'Se inició la creación de una Orden de Trabajo (OT) para este requerimiento.'
            ]);
        }
        
        // Redirigir a la página de creación de OT con los parámetros
        return redirect()->route('ordenes-trabajo.index', [
            'requerimiento_comuna_id' => $requerimiento->comuna_id,
            'requerimiento_establecimiento_id' => $requerimiento->establecimiento_id,
            'requerimiento_contrato_id' => $requerimiento->contrato_id
        ]);
    }
    
    public function finalizar(Request $request, $id)
    {
        try {
            \Log::info('Finalizar requerimiento - Request recibido', [
                'requerimiento_id' => $id,
                'request_data' => $request->all()
            ]);
            
            $validated = $request->validate([
                'situacion_final' => 'required|string|max:2000'
            ]);
            
            \Log::info('Finalizar requerimiento - Validación exitosa', ['validated' => $validated]);
            
            $requerimiento = Requerimiento::findOrFail($id);
            \Log::info('Finalizar requerimiento - Estado actual', ['estado_actual' => $requerimiento->estado]);
            
            $userSession = session('user');
            $usuarioId = $userSession['id'] ?? 1;
            \Log::info('Finalizar requerimiento - Usuario', ['usuario_id' => $usuarioId]);
            
            // Actualizar estado a "resuelto" usando update para asegurar que se guarde
            $actualizado = $requerimiento->update([
                'estado' => 'resuelto',
                'usuario_mod_id' => $usuarioId,
                'fecha_mod' => now()
            ]);
            
            \Log::info('Finalizar requerimiento - Update ejecutado', ['actualizado' => $actualizado]);
            
            // Si no se actualizó, intentar de otra forma
            if (!$actualizado) {
                \Log::warning('Finalizar requerimiento - Update falló, intentando save()');
                $requerimiento->estado = 'resuelto';
                $requerimiento->usuario_mod_id = $usuarioId;
                $requerimiento->fecha_mod = now();
                $actualizado = $requerimiento->save();
                \Log::info('Finalizar requerimiento - Save ejecutado', ['actualizado' => $actualizado]);
            }
            
            // Recargar el modelo desde la base de datos
            $requerimiento->refresh();
            \Log::info('Finalizar requerimiento - Estado después de refresh', ['estado' => $requerimiento->estado]);
            
            // Crear comentario con la situación final
            $comentario = RequerimientoComentario::create([
                'requerimiento_id' => $requerimiento->id,
                'usuario_id' => $usuarioId,
                'comentario' => 'Situación Final Requerimiento: ' . $validated['situacion_final']
            ]);
            
            \Log::info('Finalizar requerimiento - Comentario creado', [
                'comentario_id' => $comentario->id,
                'requerimiento_id' => $comentario->requerimiento_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Requerimiento finalizado exitosamente',
                'estado' => $requerimiento->estado,
                'estado_guardado' => $actualizado,
                'comentario_id' => $comentario->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación al finalizar requerimiento', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al finalizar requerimiento', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el requerimiento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $requerimiento = Requerimiento::findOrFail($id);
            
            // Verificar que el requerimiento esté resuelto
            if (mb_strtolower(trim($requerimiento->estado ?? '')) !== 'resuelto') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar requerimientos resueltos.'
                ], 400);
            }
            
            // Eliminar comentarios asociados primero
            RequerimientoComentario::where('requerimiento_id', $id)->delete();
            
            // Eliminar el requerimiento
            $requerimiento->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Requerimiento eliminado correctamente.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requerimiento no encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al eliminar requerimiento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el requerimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}

