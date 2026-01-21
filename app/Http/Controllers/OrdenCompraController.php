<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\Contrato;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenCompraController extends Controller
{
    public function index(Request $request)
    {
        // Obtener contratos que tienen OTs sin OC asociada
        // O contratos que tienen OTs (para poder editar OCs existentes)
        // Excluir contratos terminados para el dropdown (pero las OCs existentes de contratos terminados seguirán siendo visibles)
        $contratos = Contrato::whereHas('ordenesTrabajo', function($query) {
            // Incluir contratos que tienen al menos una OT (con o sin OC)
            // Esto permite editar OCs existentes
        })
        ->whereRaw("TRIM(COALESCE(estado, '')) != 'Terminado'")
        ->with(['proyecto', 'proyecto.item'])
        ->get();
        
        // Búsqueda
        $busqueda = $request->get('busqueda', '');
        
        // Query base para OC
        $query = OrdenCompra::with([
            'contrato',
            'contrato.proyecto',
            'contrato.proyecto.item',
            'ordenesTrabajo.contrato'
        ]);
        
        // Filtrar solo órdenes de compra del año actual por fecha de creación
        $query->whereYear('created_at', date('Y'));
        
        // Aplicar filtro de búsqueda si existe
        if (!empty($busqueda)) {
            $query->where(function($q) use ($busqueda) {
                $q->where('numero', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('descripcion', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('factura', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('estado', 'LIKE', '%' . $busqueda . '%')
                  ->orWhereHas('contrato', function($q) use ($busqueda) {
                      $q->where('nombre_contrato', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('numero_contrato', 'LIKE', '%' . $busqueda . '%');
                  })
                  ->orWhereHas('contrato.proyecto', function($q) use ($busqueda) {
                      $q->where('nombre', 'LIKE', '%' . $busqueda . '%');
                  })
                  ->orWhereHas('contrato.proyecto.item', function($q) use ($busqueda) {
                      $q->where('nombre', 'LIKE', '%' . $busqueda . '%');
                  });
            });
        }
        
        // Obtener todas las OC registradas (para búsqueda) o solo las últimas 15
        if (!empty($busqueda)) {
            $ordenesCompra = $query->orderBy('created_at', 'desc')->get();
        } else {
            $ordenesCompra = $query->orderBy('created_at', 'desc')->limit(15)->get();
        }
        
        return view('ordenes-compra.index', compact('contratos', 'ordenesCompra', 'busqueda'));
    }

    public function getContratosDisponibles()
    {
        // Obtener contratos que tienen OTs (con o sin OC asociada) y que no estén terminados
        // Esto permite editar OCs existentes y crear nuevas, pero excluye contratos terminados
        $contratos = Contrato::whereHas('ordenesTrabajo')
            ->whereRaw("TRIM(COALESCE(estado, '')) != 'Terminado'")
            ->with(['proyecto', 'proyecto.item'])
            ->get();
        
        return response()->json([
            'success' => true,
            'contratos' => $contratos->map(function($c) {
                return [
                    'id' => $c->id,
                    'nombre_contrato' => $c->nombre_contrato,
                    'numero_contrato' => $c->numero_contrato,
                    'orden_compra' => $c->orden_compra,
                    'fecha_oc' => $c->fecha_oc ? $c->fecha_oc->format('Y-m-d') : null,
                    'proyecto' => $c->proyecto ? $c->proyecto->nombre : null,
                    'item' => $c->proyecto && $c->proyecto->item ? $c->proyecto->item->nombre : null
                ];
            })
        ]);
    }

    public function getOrdenesTrabajoSinOC($contratoId, Request $request)
    {
        // Si se está editando una OC, incluir también las OTs ya asociadas a esa OC
        $ocId = $request->query('oc_id');
        
        $query = OrdenTrabajo::where('contrato_id', $contratoId);
        
        if ($ocId) {
            // Incluir OTs sin OC o que pertenezcan a la OC que se está editando
            $query->where(function($q) use ($ocId) {
                $q->whereNull('orden_compra_id')
                  ->orWhere('orden_compra_id', $ocId);
            });
        } else {
            // Solo OTs sin OC
            $query->whereNull('orden_compra_id');
        }
        
        $ots = $query->with(['establecimiento', 'comuna'])->get();
        
        return response()->json([
            'success' => true,
            'ordenes_trabajo' => $ots->map(function($ot) {
                return [
                    'id' => $ot->id,
                    'numero_ot' => $ot->numero_ot,
                    'fecha_ot' => $ot->fecha_ot ? $ot->fecha_ot->format('d-m-Y') : null,
                    'establecimiento' => $ot->establecimiento ? $ot->establecimiento->nombre : null,
                    'comuna' => $ot->comuna ? $ot->comuna->nombre : null,
                    'monto' => (float) $ot->monto
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contrato_id' => 'required|exists:contratos,id',
            'numero' => 'required|string|max:50',
            'fecha' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'monto_mercado_publico' => 'nullable|numeric|min:0',
            'estado' => 'required|in:Pendiente,Aprobado,Pagado',
            'descripcion' => 'nullable|string|max:500',
            'factura' => 'nullable|string|max:50',
            'monto_factura' => 'nullable|numeric|min:0',
            'fecha_factura' => 'nullable|date',
            'fecha_recepcion_factura' => 'nullable|date',
            'mes_estimado_pago' => 'nullable|string|max:20',
            'ordenes_trabajo_ids' => 'required|array|min:1',
            'ordenes_trabajo_ids.*' => 'exists:ordenes_trabajo,id'
        ]);

        DB::beginTransaction();
        try {
            $contrato = Contrato::findOrFail($validated['contrato_id']);
            
            // Validar que el contrato no esté terminado
            $estadoContrato = trim($contrato->estado ?? '');
            if ($estadoContrato === 'Terminado') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pueden crear órdenes de compra para contratos terminados.',
                        'errors' => ['contrato_id' => ['El contrato seleccionado está terminado y no puede recibir nuevas órdenes de compra.']]
                    ], 422);
                }
                return back()->withErrors([
                    'contrato_id' => 'No se pueden crear órdenes de compra para contratos terminados.'
                ])->withInput();
            }
            
            // Crear la OC
            // Si no se proporciona monto_mercado_publico o es 0, usar monto_total como valor por defecto
            $montoMercadoPublico = isset($validated['monto_mercado_publico']) && $validated['monto_mercado_publico'] > 0 
                ? $validated['monto_mercado_publico'] 
                : $validated['monto_total'];
            
            $oc = OrdenCompra::create([
                'numero' => $validated['numero'],
                'fecha' => $validated['fecha'],
                'contrato_id' => $validated['contrato_id'],
                'monto_total' => $validated['monto_total'],
                'monto_mercado_publico' => $montoMercadoPublico,
                'estado' => $validated['estado'],
                'descripcion' => $validated['descripcion'] ?? null,
                'factura' => $validated['factura'] ?? null,
                'monto_factura' => $validated['monto_factura'] ?? null,
                'fecha_factura' => $validated['fecha_factura'] ?? null,
                'fecha_recepcion_factura' => $validated['fecha_recepcion_factura'] ?? null,
                'mes_estimado_pago' => $validated['mes_estimado_pago'] ?? null,
            ]);

            // Asociar las OTs seleccionadas
            OrdenTrabajo::whereIn('id', $validated['ordenes_trabajo_ids'])
                ->where('contrato_id', $validated['contrato_id'])
                ->update(['orden_compra_id' => $oc->id]);

            DB::commit();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orden de Compra creada exitosamente'
                ]);
            }
            
            return redirect()->route('ordenes-compra.index')->with('success', 'Orden de Compra creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la Orden de Compra: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withErrors(['error' => 'Error al crear la Orden de Compra: ' . $e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        $oc = OrdenCompra::with([
            'contrato',
            'contrato.proyecto',
            'contrato.proyecto.item',
            'ordenesTrabajo.establecimiento',
            'ordenesTrabajo.establecimiento.comuna',
            'ordenesTrabajo.comuna',
            'ordenesTrabajo.contrato'
        ])->findOrFail($id);
        
        // Si la OC no tiene contrato_id pero tiene OTs asociadas, obtener el contrato desde las OTs
        $contratoDesdeOT = null;
        if (!$oc->contrato_id && $oc->ordenesTrabajo->count() > 0) {
            $primeraOT = $oc->ordenesTrabajo->first();
            if ($primeraOT && $primeraOT->contrato_id) {
                $contratoDesdeOT = $primeraOT->contrato;
                // Asignar temporalmente para que aparezca en el JSON
                $oc->contrato_id = $primeraOT->contrato_id;
                if ($contratoDesdeOT) {
                    $oc->setRelation('contrato', $contratoDesdeOT);
                }
            }
        }
        
        // Formatear fechas para el formulario (Y-m-d para inputs type="date")
        $ocData = $oc->toArray();
        
        // Asegurar que las OTs incluyan su contrato_id y datos de establecimiento/comuna en la respuesta
        if (isset($ocData['ordenes_trabajo'])) {
            foreach ($ocData['ordenes_trabajo'] as &$ot) {
                if (!isset($ot['contrato_id']) && isset($ot['contrato']['id'])) {
                    $ot['contrato_id'] = $ot['contrato']['id'];
                }
                
                // Asegurar que el establecimiento tenga todos sus datos
                if (isset($ot['establecimiento_id']) && (!isset($ot['establecimiento']) || !$ot['establecimiento'])) {
                    $establecimientoId = $ot['establecimiento_id'];
                    $establecimiento = \App\Models\Establecimiento::with('comuna')->find($establecimientoId);
                    if ($establecimiento) {
                        $ot['establecimiento'] = [
                            'id' => $establecimiento->id,
                            'nombre' => $establecimiento->nombre,
                            'comuna_id' => $establecimiento->comuna_id,
                            'comuna' => $establecimiento->comuna ? [
                                'id' => $establecimiento->comuna->id,
                                'nombre' => $establecimiento->comuna->nombre
                            ] : null
                        ];
                    }
                } elseif (isset($ot['establecimiento']) && $ot['establecimiento']) {
                    // Asegurar que el establecimiento tenga la comuna cargada
                    if (!isset($ot['establecimiento']['comuna']) || !$ot['establecimiento']['comuna']) {
                        $establecimientoId = $ot['establecimiento']['id'] ?? $ot['establecimiento_id'] ?? null;
                        if ($establecimientoId) {
                            $establecimiento = \App\Models\Establecimiento::with('comuna')->find($establecimientoId);
                            if ($establecimiento && $establecimiento->comuna) {
                                $ot['establecimiento']['comuna'] = [
                                    'id' => $establecimiento->comuna->id,
                                    'nombre' => $establecimiento->comuna->nombre
                                ];
                            }
                        }
                    }
                }
                
                // Si no hay comuna cargada o es inválida, obtener comuna desde el establecimiento
                $comunaValida = isset($ot['comuna']) && $ot['comuna'] && isset($ot['comuna']['id']);
                if (!$comunaValida && isset($ot['establecimiento'])) {
                    // Intentar obtener comuna desde establecimiento.comuna (relación cargada)
                    if (isset($ot['establecimiento']['comuna']) && $ot['establecimiento']['comuna']) {
                        $ot['comuna'] = $ot['establecimiento']['comuna'];
                    } elseif (isset($ot['establecimiento']['comuna_id'])) {
                        // Si no está cargada la relación, buscar directamente
                        $comunaId = $ot['establecimiento']['comuna_id'];
                        $comuna = \App\Models\Comuna::find($comunaId);
                        if ($comuna) {
                            $ot['comuna'] = ['id' => $comuna->id, 'nombre' => $comuna->nombre];
                        }
                    }
                }
            }
        }
        if ($oc->fecha) {
            $ocData['fecha'] = $oc->fecha->format('Y-m-d');
        }
        if ($oc->fecha_factura) {
            $ocData['fecha_factura'] = $oc->fecha_factura->format('Y-m-d');
        }
        if ($oc->fecha_recepcion_factura) {
            $ocData['fecha_recepcion_factura'] = $oc->fecha_recepcion_factura->format('Y-m-d');
        }
        
        // Obtener IDs de OTs asociadas
        $ocData['ordenes_trabajo_ids'] = $oc->ordenesTrabajo->pluck('id')->toArray();
        
        return response()->json([
            'success' => true,
            'oc' => $ocData
        ]);
    }

    public function update(Request $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);
        
        $validated = $request->validate([
            'contrato_id' => 'required|exists:contratos,id',
            'numero' => 'required|string|max:50',
            'fecha' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'monto_mercado_publico' => 'nullable|numeric|min:0',
            'estado' => 'required|in:Pendiente,Aprobado,Pagado',
            'descripcion' => 'nullable|string|max:500',
            'factura' => 'nullable|string|max:50',
            'monto_factura' => 'nullable|numeric|min:0',
            'fecha_factura' => 'nullable|date',
            'fecha_recepcion_factura' => 'nullable|date',
            'mes_estimado_pago' => 'nullable|string|max:20',
            'ordenes_trabajo_ids' => 'required|array|min:1',
            'ordenes_trabajo_ids.*' => 'exists:ordenes_trabajo,id'
        ]);

        DB::beginTransaction();
        try {
            // Desasociar OTs anteriores de esta OC
            OrdenTrabajo::where('orden_compra_id', $oc->id)
                ->update(['orden_compra_id' => null]);
            
            // Si no se proporciona monto_mercado_publico, usar monto_total como valor por defecto
            $montoMercadoPublico = $validated['monto_mercado_publico'] ?? $validated['monto_total'];
            
            // Actualizar la OC
            $oc->update([
                'numero' => $validated['numero'],
                'fecha' => $validated['fecha'],
                'contrato_id' => $validated['contrato_id'],
                'monto_total' => $validated['monto_total'],
                'monto_mercado_publico' => $montoMercadoPublico,
                'estado' => $validated['estado'],
                'descripcion' => $validated['descripcion'] ?? null,
                'factura' => $validated['factura'] ?? null,
                'monto_factura' => $validated['monto_factura'] ?? null,
                'fecha_factura' => $validated['fecha_factura'] ?? null,
                'fecha_recepcion_factura' => $validated['fecha_recepcion_factura'] ?? null,
                'mes_estimado_pago' => $validated['mes_estimado_pago'] ?? null,
            ]);

            // Asociar las nuevas OTs seleccionadas
            OrdenTrabajo::whereIn('id', $validated['ordenes_trabajo_ids'])
                ->where('contrato_id', $validated['contrato_id'])
                ->update(['orden_compra_id' => $oc->id]);

            DB::commit();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orden de Compra actualizada exitosamente'
                ]);
            }
            
            return redirect()->route('ordenes-compra.index')->with('success', 'Orden de Compra actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la Orden de Compra: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withErrors(['error' => 'Error al actualizar la Orden de Compra: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $oc = OrdenCompra::findOrFail($id);
            
            DB::beginTransaction();
            
            // Desasociar OTs de esta OC
            OrdenTrabajo::where('orden_compra_id', $oc->id)
                ->update(['orden_compra_id' => null]);
            
            // Eliminar la OC
            $oc->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Orden de Compra eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la Orden de Compra: ' . $e->getMessage()
            ], 422);
        }
    }

    public function formularioRecepcionServicios($id)
    {
        $oc = OrdenCompra::with([
            'contrato',
            'contrato.proyecto',
            'contrato.proyecto.item',
            'ordenesTrabajo.establecimiento',
            'ordenesTrabajo.comuna'
        ])->findOrFail($id);
        
        // Obtener email del usuario logueado
        $userEmail = '-';
        $userSession = session('user');
        if (!$userSession && session('token')) {
            try {
                $token = session('token');
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                $userId = $payload->get('sub');
                $usuario = \App\Models\Usuario::find($userId);
                if ($usuario) {
                    $userEmail = $usuario->email;
                }
            } catch (\Exception $e) {
                // Silenciar errores
            }
        } elseif ($userSession && is_array($userSession)) {
            $userEmail = $userSession['email'] ?? '-';
        } elseif (is_object($userSession) && isset($userSession->email)) {
            $userEmail = $userSession->email;
        }
        
        // Obtener nombre del usuario logueado para la firma
        $userNombre = '-';
        if ($userSession && is_array($userSession)) {
            $userNombre = $userSession['nombre'] ?? '-';
        } elseif (is_object($userSession) && isset($userSession->nombre)) {
            $userNombre = $userSession->nombre;
        } elseif (!$userSession && session('token')) {
            try {
                $token = session('token');
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                $userId = $payload->get('sub');
                $usuario = \App\Models\Usuario::find($userId);
                if ($usuario) {
                    $userNombre = $usuario->nombre;
                }
            } catch (\Exception $e) {
                // Silenciar errores
            }
        }
        
        // RUT de la jefatura (valor fijo según la captura)
        $rutJefatura = '15.289.569-0';
        
        return view('ordenes-compra.formulario-recepcion-servicios', compact('oc', 'userEmail', 'userNombre', 'rutJefatura'));
    }

    public function formularioRecepcionFactura($id)
    {
        $oc = OrdenCompra::with([
            'contrato',
            'contrato.proyecto',
            'contrato.proyecto.item'
        ])->findOrFail($id);
        
        // Obtener email del usuario logueado
        $userEmail = '-';
        $userSession = session('user');
        if (!$userSession && session('token')) {
            try {
                $token = session('token');
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                $userId = $payload->get('sub');
                $usuario = \App\Models\Usuario::find($userId);
                if ($usuario) {
                    $userEmail = $usuario->email;
                }
            } catch (\Exception $e) {
                // Silenciar errores
            }
        } elseif ($userSession && is_array($userSession)) {
            $userEmail = $userSession['email'] ?? '-';
        } elseif (is_object($userSession) && isset($userSession->email)) {
            $userEmail = $userSession->email;
        }
        
        // Obtener nombre del usuario logueado para la firma
        $userNombre = '-';
        if ($userSession && is_array($userSession)) {
            $userNombre = $userSession['nombre'] ?? '-';
        } elseif (is_object($userSession) && isset($userSession->nombre)) {
            $userNombre = $userSession->nombre;
        } elseif (!$userSession && session('token')) {
            try {
                $token = session('token');
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                $userId = $payload->get('sub');
                $usuario = \App\Models\Usuario::find($userId);
                if ($usuario) {
                    $userNombre = $usuario->nombre;
                }
            } catch (\Exception $e) {
                // Silenciar errores
            }
        }
        
        // RUT de la jefatura (valor fijo según la captura)
        $rutJefatura = '15.289.569-0';
        
        return view('ordenes-compra.formulario-recepcion-factura', compact('oc', 'userEmail', 'userNombre', 'rutJefatura'));
    }

    public function generarRCS(Request $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);
        
        // Si ya tiene RCS, retornar error
        if ($oc->rcs_numero) {
            return response()->json([
                'success' => false,
                'message' => 'Esta OC ya tiene un número RCS asignado'
            ], 422);
        }
        
        $validated = $request->validate([
            'fecha' => 'required|date',
            'tipo_jefatura' => 'required|in:Titular,Suplencia',
            'jefatura_firma' => 'required|string|max:100'
        ]);
        
        // Generar número correlativo
        $anio = date('Y');
        
        // Buscar el último RCS del año actual
        $ultimoRCS = OrdenCompra::whereNotNull('rcs_numero')
            ->where('rcs_numero', 'LIKE', "RCS-%-{$anio}")
            ->orderBy('rcs_numero', 'desc')
            ->first();
        
        $numeroCorrelativo = 1;
        if ($ultimoRCS && $ultimoRCS->rcs_numero) {
            // Extraer el número del último RCS (formato: RCS-0001-2025)
            // Ejemplo: "RCS-0001-2025" -> matches[1] = "0001", matches[2] = "2025"
            if (preg_match('/RCS-(\d+)-(\d+)/', $ultimoRCS->rcs_numero, $matches)) {
                if (isset($matches[1]) && isset($matches[2]) && $matches[2] == $anio) {
                    $numeroCorrelativo = intval($matches[1]) + 1;
                }
            }
        }
        
        $rcsNumero = 'RCS-' . str_pad($numeroCorrelativo, 4, '0', STR_PAD_LEFT) . '-' . $anio;
        
        // Guardar en BD
        try {
            $oc->update([
                'rcs_numero' => $rcsNumero,
                'rcs_fecha' => $validated['fecha'],
                'rcs_tipo_jefatura' => $validated['tipo_jefatura'],
                'rcs_jefatura_firma' => $validated['jefatura_firma']
            ]);
            
            // Recargar el modelo para asegurar que tiene los valores actualizados
            $oc->refresh();
            
            return response()->json([
                'success' => true,
                'rcs_numero' => $rcsNumero,
                'message' => 'Número RCS generado correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar RCS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 422);
        }
    }

    public function actualizarRCS(Request $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);
        
        $validated = $request->validate([
            'fecha' => 'required|date',
            'tipo_jefatura' => 'required|in:Titular,Suplencia',
            'jefatura_firma' => 'required|string|max:100'
        ]);
        
        // Actualizar solo fecha y jefatura (no el número)
        $oc->update([
            'rcs_fecha' => $validated['fecha'],
            'rcs_tipo_jefatura' => $validated['tipo_jefatura'],
            'rcs_jefatura_firma' => $validated['jefatura_firma']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'RCS actualizado correctamente'
        ]);
    }

    public function generarRCF(Request $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);
        
        // Si ya tiene RCF, retornar error
        if ($oc->rcf_numero) {
            return response()->json([
                'success' => false,
                'message' => 'Esta OC ya tiene un número RCF asignado'
            ], 422);
        }
        
        $validated = $request->validate([
            'fecha' => 'required|date',
            'tipo_jefatura' => 'required|in:Titular,Suplencia',
            'jefatura_firma' => 'required|string|max:100'
        ]);
        
        // Generar número correlativo
        $anio = date('Y');
        
        // Buscar el último RCF del año actual
        $ultimoRCF = OrdenCompra::whereNotNull('rcf_numero')
            ->where('rcf_numero', 'LIKE', "RCF-%-{$anio}")
            ->orderBy('rcf_numero', 'desc')
            ->first();
        
        $numeroCorrelativo = 1;
        if ($ultimoRCF && $ultimoRCF->rcf_numero) {
            // Extraer el número del último RCF (formato: RCF-0001-2025)
            if (preg_match('/RCF-(\d+)-(\d+)/', $ultimoRCF->rcf_numero, $matches)) {
                if (isset($matches[1]) && isset($matches[2]) && $matches[2] == $anio) {
                    $numeroCorrelativo = intval($matches[1]) + 1;
                }
            }
        }
        
        $rcfNumero = 'RCF-' . str_pad($numeroCorrelativo, 4, '0', STR_PAD_LEFT) . '-' . $anio;
        
        // Guardar en BD
        try {
            $oc->update([
                'rcf_numero' => $rcfNumero,
                'rcf_fecha' => $validated['fecha'],
                'rcf_tipo_jefatura' => $validated['tipo_jefatura'],
                'rcf_jefatura_firma' => $validated['jefatura_firma']
            ]);
            
            // Recargar el modelo para asegurar que tiene los valores actualizados
            $oc->refresh();
            
            return response()->json([
                'success' => true,
                'rcf_numero' => $rcfNumero,
                'message' => 'Número RCF generado correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar RCF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 422);
        }
    }

    public function actualizarRCF(Request $request, $id)
    {
        $oc = OrdenCompra::findOrFail($id);
        
        $validated = $request->validate([
            'fecha' => 'required|date',
            'tipo_jefatura' => 'required|in:Titular,Suplencia',
            'jefatura_firma' => 'required|string|max:100'
        ]);
        
        // Actualizar solo fecha y jefatura (no el número)
        $oc->update([
            'rcf_fecha' => $validated['fecha'],
            'rcf_tipo_jefatura' => $validated['tipo_jefatura'],
            'rcf_jefatura_firma' => $validated['jefatura_firma']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'RCF actualizado correctamente'
        ]);
    }
}

