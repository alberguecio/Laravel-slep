<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Models\Comuna;
use App\Models\Establecimiento;
use App\Models\Oferente;
use App\Models\Contrato;
use App\Models\PresupuestoOt;
use App\Models\PresupuestoOtItem;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenTrabajoController extends Controller
{
    /**
     * Mostrar lista de órdenes de trabajo
     */
    public function index(Request $request)
    {
        // Búsqueda
        $busqueda = $request->get('busqueda', '');
        
        // Parámetros de requerimiento para pre-llenar campos
        $requerimientoComunaId = $request->get('requerimiento_comuna_id');
        $requerimientoEstablecimientoId = $request->get('requerimiento_establecimiento_id');
        $requerimientoContratoId = $request->get('requerimiento_contrato_id');
        
        // Query base para órdenes
        $query = OrdenTrabajo::with([
            'comuna',
            'establecimiento',
            'establecimiento.comuna', // Cargar comuna del establecimiento como fallback
            'oferente',
            'contrato',
            'ordenCompra'
        ]);
        
        // Filtrar solo órdenes del año actual por fecha de creación
        $query->whereYear('created_at', date('Y'));
        
        // Aplicar filtro de búsqueda si existe
        if (!empty($busqueda)) {
            $query->where(function($q) use ($busqueda) {
                $q->where('numero_ot', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('estado', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('tipo', 'LIKE', '%' . $busqueda . '%')
                  ->orWhereHas('establecimiento', function($q) use ($busqueda) {
                      $q->where('nombre', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('rbd', 'LIKE', '%' . $busqueda . '%');
                  })
                  ->orWhereHas('comuna', function($q) use ($busqueda) {
                      $q->where('nombre', 'LIKE', '%' . $busqueda . '%');
                  })
                  ->orWhereHas('contrato', function($q) use ($busqueda) {
                      $q->where('nombre_contrato', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('numero_contrato', 'LIKE', '%' . $busqueda . '%');
                  });
            });
        }
        
        // Obtener todas las órdenes (para búsqueda) o solo las últimas 15
        if (!empty($busqueda)) {
            $ordenes = $query->orderBy('created_at', 'desc')->get();
        } else {
            $ordenes = $query->orderBy('created_at', 'desc')->limit(15)->get();
        }

        // Datos para filtros y formularios
        $comunas = Comuna::orderBy('nombre')->get();
        // Obtener establecimientos - asegurarse de que comuna_id esté disponible
        // No necesitamos with('comuna') aquí, solo necesitamos comuna_id que ya está en la tabla
        // Asegurar que la conexión use UTF-8
        DB::statement("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        
        $establecimientos = Establecimiento::select('id', 'nombre', 'comuna_id', 'rbd')
            ->orderBy('nombre')
            ->get()
            ->map(function ($est) {
                // Asegurar que el nombre esté en UTF-8 correctamente
                // Intentar detectar y convertir la codificación si es necesario
                if (!mb_check_encoding($est->nombre, 'UTF-8')) {
                    $est->nombre = mb_convert_encoding($est->nombre, 'UTF-8', 'ISO-8859-1');
                }
                return $est;
            });
        $oferentes = Oferente::orderBy('nombre')->get();
        
        // Obtener contratos con monto restante calculado (excluir contratos terminados)
        $contratos = Contrato::with('proyecto')
            ->whereRaw("TRIM(COALESCE(estado, '')) != 'Terminado'")
            ->withSum('ordenesTrabajo as monto_usado', 'monto')
            ->orderBy('nombre_contrato')
            ->get()
            ->map(function ($contrato) {
                $contrato->monto_usado = (float) ($contrato->monto_usado ?? 0);
                $contrato->monto_restante = (float) $contrato->monto_real - $contrato->monto_usado;
                if ($contrato->monto_restante < 0) {
                    $contrato->monto_restante = 0;
                }
                // Verificar si tiene precios unitarios y cantidad (sin cargar la relación completa)
                try {
                    $contrato->tiene_precios = \App\Models\PrecioUnitario::where('contrato_id', $contrato->id)->exists();
                    $contrato->cantidad_precios = \App\Models\PrecioUnitario::where('contrato_id', $contrato->id)->count();
                } catch (\Exception $e) {
                    $contrato->tiene_precios = false;
                    $contrato->cantidad_precios = 0;
                }
                return $contrato;
            });

        // Generar próximo número de OT (formato: 0000-2025)
        $añoActual = date('Y');
        $ultimoNumero = OrdenTrabajo::where('numero_ot', 'LIKE', '%-' . $añoActual)
            ->whereNotNull('numero_ot')
            ->orderByRaw("CAST(SUBSTRING_INDEX(numero_ot, '-', 1) AS UNSIGNED) DESC")
            ->value('numero_ot');
        
        $proximoNumero = '0001';
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            if (count($partes) === 2 && $partes[1] == $añoActual) {
                $numeroActual = (int) $partes[0];
                $proximoNumero = str_pad($numeroActual + 1, 4, '0', STR_PAD_LEFT);
            }
        }
        $numeroOt = $proximoNumero . '-' . $añoActual;

        return view('ordenes-trabajo.index', compact(
            'ordenes',
            'comunas',
            'establecimientos',
            'oferentes',
            'contratos',
            'numeroOt',
            'busqueda',
            'requerimientoComunaId',
            'requerimientoEstablecimientoId',
            'requerimientoContratoId'
        ));
    }

    /**
     * Crear nueva orden de trabajo
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'comuna_id' => 'nullable|exists:comunas,id',
                'establecimiento_id' => 'required|exists:establecimientos,id',
                'contrato_id' => 'required|exists:contratos,id',
                'numero_ot' => 'nullable|string|max:20',
                'fecha_ot' => 'required|date',
                'estado' => 'required|in:Pendiente,Enviado',
                'tipo' => 'required|in:Normal,Emergencia',
                'medida' => 'nullable|string|max:100',
                'monto' => 'nullable|numeric|min:0',
                'presupuesto_items' => 'nullable|array',
                'presupuesto_items.*.item' => 'required|integer|min:1',
                'presupuesto_items.*.partida' => 'required|string|max:500',
                'presupuesto_items.*.numero_partida' => 'nullable|string|max:20',
                'presupuesto_items.*.unidad' => 'required|string|max:50',
                'presupuesto_items.*.cantidad' => 'required|numeric|min:0',
                'presupuesto_items.*.precio' => 'required|numeric|min:0',
                'presupuesto_items.*.total' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si es petición AJAX, devolver errores de validación en JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            // Si no es AJAX, lanzar la excepción normalmente para que Laravel maneje la redirección
            throw $e;
        }

        // Validar saldo disponible del contrato
        $contrato = Contrato::findOrFail($validated['contrato_id']);
        
        // Validar que el contrato no esté terminado
        $estadoContrato = trim($contrato->estado ?? '');
        if ($estadoContrato === 'Terminado') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden crear órdenes de trabajo para contratos terminados.',
                    'errors' => ['contrato_id' => ['El contrato seleccionado está terminado y no puede recibir nuevas órdenes de trabajo.']]
                ], 422);
            }
            return back()->withErrors([
                'contrato_id' => 'No se pueden crear órdenes de trabajo para contratos terminados.'
            ])->withInput();
        }
        
        $montoUsado = OrdenTrabajo::where('contrato_id', $contrato->id)->sum('monto') ?? 0;
        $saldoDisponible = (float) $contrato->monto_real - $montoUsado;
        
        // Limpiar monto si viene con formato de moneda
        $montoLimpio = 0;
        if (isset($validated['monto'])) {
            if (is_string($validated['monto'])) {
                // Remover símbolos de moneda y espacios
                $montoLimpio = str_replace(['$', ' ', '€'], '', $validated['monto']);
                // Remover puntos (separadores de miles) y reemplazar coma por punto (decimal)
                $montoLimpio = str_replace('.', '', $montoLimpio);
                $montoLimpio = str_replace(',', '.', $montoLimpio);
                $montoLimpio = (float) $montoLimpio;
            } else {
                $montoLimpio = (float) $validated['monto'];
            }
        }

        // Calcular monto total desde los items del presupuesto si existen
        // Si viene monto del frontend (ya incluye IVA), usarlo. Solo calcular desde items si no viene monto
        $montoOT = $montoLimpio;
        if (!empty($validated['presupuesto_items']) && $montoOT == 0) {
            // Solo calcular desde items si no hay monto enviado (el monto del frontend ya incluye IVA)
            $montoOT = array_sum(array_column($validated['presupuesto_items'], 'total'));
        }

        // Si hay monto del frontend (que ya incluye IVA), usarlo. Si no, usar el calculado
        if ($montoLimpio > 0) {
            $montoOT = $montoLimpio;
        }
        
        $validated['monto'] = $montoOT;

        if ($montoOT > $saldoDisponible + 0.0001) {
            $mensajeError = 'El monto supera el saldo disponible del contrato ($' . number_format($saldoDisponible, 0, ',', '.') . ').';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError
                ], 422);
            }
            return back()->withErrors([
                'monto' => $mensajeError
            ])->withInput();
        }

        // Generar número de OT si no viene
        if (empty($validated['numero_ot'])) {
            $añoActual = date('Y', strtotime($validated['fecha_ot']));
            $ultimoNumero = OrdenTrabajo::where('numero_ot', 'LIKE', '%-' . $añoActual)
                ->whereNotNull('numero_ot')
                ->orderByRaw("CAST(SUBSTRING_INDEX(numero_ot, '-', 1) AS UNSIGNED) DESC")
                ->value('numero_ot');
            
            $proximoNumero = '0001';
            if ($ultimoNumero) {
                $partes = explode('-', $ultimoNumero);
                if (count($partes) === 2 && $partes[1] == $añoActual) {
                    $numeroActual = (int) $partes[0];
                    $proximoNumero = str_pad($numeroActual + 1, 4, '0', STR_PAD_LEFT);
                }
            }
            $validated['numero_ot'] = $proximoNumero . '-' . $añoActual;
        }

        // Guardar en transacción para asegurar consistencia
        DB::beginTransaction();
        try {
            // Crear la OT
            $ordenTrabajo = OrdenTrabajo::create($validated);

            // Si hay items del presupuesto, crear el presupuesto
            if (!empty($validated['presupuesto_items']) && count($validated['presupuesto_items']) > 0) {
                // Crear PresupuestoOt
                $presupuestoOt = PresupuestoOt::create([
                    'ot_id' => $ordenTrabajo->id,
                    'usuario_id' => auth()->id() ?? null,
                    'fecha' => $validated['fecha_ot'],
                ]);

                // Crear los items del presupuesto
                foreach ($validated['presupuesto_items'] as $item) {
                    // Si viene numero_partida, guardarlo. Si no, intentar extraerlo de partida si tiene formato "numero - partida"
                    $numeroPartida = $item['numero_partida'] ?? null;
                    $partidaTexto = $item['partida'] ?? '';
                    
                    // Si no viene numero_partida pero la partida tiene formato "numero - partida", extraerlo
                    if (!$numeroPartida && $partidaTexto && strpos($partidaTexto, ' - ') !== false) {
                        $partes = explode(' - ', $partidaTexto, 2);
                        $numeroPartida = trim($partes[0]);
                        $partidaTexto = trim($partes[1] ?? $partidaTexto);
                    }
                    
                    PresupuestoOtItem::create([
                        'presupuesto_ot_id' => $presupuestoOt->id,
                        'item' => $item['item'],
                        'partida' => $partidaTexto,
                        'numero_partida' => $numeroPartida,
                        'unidad' => $item['unidad'],
                        'cantidad' => round((float) $item['cantidad'], 2),
                        'precio' => (float) $item['precio'],
                        'total' => round((float) $item['total'], 0), // Redondear total a 0 decimales
                    ]);
                }
            }

            DB::commit();
            
            // Preparar mensaje de éxito
            $mensaje = 'Orden de trabajo creada exitosamente';
            if (!empty($validated['presupuesto_items']) && count($validated['presupuesto_items']) > 0) {
                $mensaje .= ' con presupuesto de ' . count($validated['presupuesto_items']) . ' partida(s)';
            }
            
            // Si es petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensaje
                ]);
            }
            
            return redirect()->route('ordenes-trabajo.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            
            $mensajeError = 'Error al crear la orden de trabajo: ' . $e->getMessage();
            
            // Si es petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->ajax()) {
                \Log::error('Error al crear OT: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError
                ], 500);
            }
            
            return back()->withErrors([
                'error' => $mensajeError
            ])->withInput();
        }
    }

    /**
     * Mostrar orden de trabajo específica (para AJAX)
     */
    public function show($id)
    {
        $orden = OrdenTrabajo::with([
            'comuna',
            'establecimiento',
            'oferente',
            'contrato',
            'ordenCompra',
            'presupuestoOt.items'
        ])->findOrFail($id);

        $presupuestoItems = [];
        if ($orden->presupuestoOt && $orden->presupuestoOt->items) {
            $presupuestoItems = $orden->presupuestoOt->items->map(function($item) {
                return [
                    'item' => $item->item,
                    'partida' => $item->partida,
                    'numero_partida' => $item->numero_partida,
                    'unidad' => $item->unidad,
                    'cantidad' => (float) $item->cantidad,
                    'precio' => (float) $item->precio,
                    'total' => (float) $item->total,
                ];
            });
        }

        // Formatear fecha para el formulario (YYYY-MM-DD)
        $fechaOtFormateada = $orden->fecha_ot ? $orden->fecha_ot->format('Y-m-d') : null;
        
        // Calcular saldo disponible del contrato excluyendo esta OT
        $saldoDisponible = 0;
        if ($orden->contrato_id) {
            $contrato = Contrato::find($orden->contrato_id);
            if ($contrato) {
                $montoUsado = OrdenTrabajo::where('contrato_id', $contrato->id)
                    ->where('id', '!=', $orden->id)
                    ->sum('monto') ?? 0;
                $saldoDisponible = (float) $contrato->monto_real - $montoUsado;
                if ($saldoDisponible < 0) {
                    $saldoDisponible = 0;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'orden' => [
                'id' => $orden->id,
                'numero_ot' => $orden->numero_ot,
                'fecha_ot' => $fechaOtFormateada,
                'contrato_id' => $orden->contrato_id,
                'comuna_id' => $orden->comuna_id,
                'establecimiento_id' => $orden->establecimiento_id,
                'rbd' => $orden->establecimiento ? $orden->establecimiento->rbd : null,
                'estado' => $orden->estado,
                'tipo' => $orden->tipo,
                'monto' => $orden->monto,
                'observacion' => $orden->observacion,
                'oferente_id' => $orden->oferente_id,
                'orden_compra_id' => $orden->orden_compra_id
            ],
            'presupuesto_items' => $presupuestoItems,
            'saldo_disponible_contrato' => $saldoDisponible
        ]);
    }

    /**
     * Obtener presupuesto de una orden de trabajo
     */
    public function getPresupuesto($id)
    {
        try {
            $orden = OrdenTrabajo::with([
                'contrato',
                'establecimiento',
                'establecimiento.comuna', // Cargar comuna del establecimiento como fallback
                'comuna',
                'presupuestoOt.items',
                'presupuestoOt.usuario'
            ])->findOrFail($id);

            $presupuesto = $orden->presupuestoOt;
            $items = [];

            if ($presupuesto) {
                $items = $presupuesto->items->map(function($item) {
                    return [
                        'item' => $item->item,
                        'partida' => $item->partida,
                        'numero_partida' => $item->numero_partida,
                        'unidad' => $item->unidad,
                        'cantidad' => (float) $item->cantidad,
                        'precio' => (float) $item->precio,
                        'total' => (float) $item->total,
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'orden' => [
                    'numero_ot' => $orden->numero_ot,
                    'fecha_ot' => $orden->fecha_ot ? $orden->fecha_ot->format('d-m-Y') : null,
                    'contrato' => $orden->contrato ? [
                        'nombre_contrato' => $orden->contrato->nombre_contrato,
                        'proveedor' => $orden->contrato->proveedor
                    ] : null,
                    'establecimiento' => $orden->establecimiento ? [
                        'nombre' => $orden->establecimiento->nombre,
                        'rbd' => $orden->establecimiento->rbd,
                        'comuna' => $orden->establecimiento->comuna ? [
                            'nombre' => $orden->establecimiento->comuna->nombre
                        ] : null
                    ] : null,
                    'comuna' => $orden->comuna ? [
                        'nombre' => $orden->comuna->nombre
                    ] : ($orden->establecimiento && $orden->establecimiento->comuna ? [
                        'nombre' => $orden->establecimiento->comuna->nombre
                    ] : null)
                ],
                'presupuesto' => $presupuesto ? [
                    'id' => $presupuesto->id,
                    'fecha' => $presupuesto->fecha ? $presupuesto->fecha->format('Y-m-d') : null,
                    'usuario' => $presupuesto->usuario ? [
                        'nombre' => $presupuesto->usuario->nombre ?? null,
                        'email' => $presupuesto->usuario->email ?? null,
                        'cargo' => $presupuesto->usuario->cargo ?? null
                    ] : null
                ] : null,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener presupuesto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el presupuesto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar formulario de Acta Recepción Conforme para una OT
     */
    public function actaRecepcionConforme($id)
    {
        $orden = OrdenTrabajo::with([
            'contrato',
            'contrato.proyecto',
            'establecimiento',
            'establecimiento.comuna',
            'comuna',
            'presupuestoOt.items',
            'presupuestoOt.usuario'
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

        // Obtener items del presupuesto
        $presupuestoItems = [];
        if ($orden->presupuestoOt && $orden->presupuestoOt->items) {
            $presupuestoItems = $orden->presupuestoOt->items->map(function($item) {
                return [
                    'item' => $item->item,
                    'partida' => $item->partida,
                    'numero_partida' => $item->numero_partida,
                    'unidad' => $item->unidad,
                    'cantidad' => (float) $item->cantidad,
                    'precio' => (float) $item->precio,
                    'total' => (float) $item->total,
                ];
            });
        }

        // Calcular totales del presupuesto
        $totalNeto = $presupuestoItems->sum('total');
        $iva = round($totalNeto * 0.19);
        $totalConIva = round($totalNeto + $iva);

        // Obtener RUT del proveedor desde la tabla oferentes
        $rutProveedor = '-';
        if ($orden->contrato && $orden->contrato->proveedor) {
            $oferente = \App\Models\Oferente::where('nombre', $orden->contrato->proveedor)->first();
            if ($oferente && $oferente->rut) {
                $rutProveedor = $oferente->rut;
            }
        }

        return view('ordenes-trabajo.acta-recepcion-conforme', compact(
            'orden',
            'userEmail',
            'userNombre',
            'presupuestoItems',
            'totalNeto',
            'iva',
            'totalConIva',
            'rutProveedor'
        ));
    }

    /**
     * Actualizar orden de trabajo
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $orden = OrdenTrabajo::findOrFail($id);

            $validated = $request->validate([
                'comuna_id' => 'nullable|exists:comunas,id',
                'establecimiento_id' => 'nullable|exists:establecimientos,id',
                'oferente_id' => 'nullable|exists:oferentes,id',
                'contrato_id' => 'nullable|exists:contratos,id',
                'numero_ot' => 'nullable|string|max:20',
                'fecha_ot' => 'nullable|date',
                'fecha_envio_oc' => 'nullable|date',
                'mes' => 'nullable|string|max:20',
                'sin_iva' => 'nullable|numeric|min:0',
                'monto' => 'nullable|numeric|min:0',
                'orden_compra' => 'nullable|string|max:100',
                'fecha_oc' => 'nullable|date',
                'fecha_recepcion' => 'nullable|date',
                'factura' => 'nullable|string|max:100',
                'fecha_factura' => 'nullable|date',
                'observacion' => 'nullable|string',
                'estado' => 'nullable|string|max:50',
                'tipo' => 'nullable|string|max:50',
                'medida' => 'nullable|string|max:100',
                'presupuesto_items' => 'nullable|array',
                'presupuesto_items.*.item' => 'required|integer|min:1',
                'presupuesto_items.*.partida' => 'required|string|max:500',
                'presupuesto_items.*.numero_partida' => 'nullable|string|max:20',
                'presupuesto_items.*.unidad' => 'required|string|max:50',
                'presupuesto_items.*.cantidad' => 'required|numeric|min:0',
                'presupuesto_items.*.precio' => 'required|numeric|min:0',
                'presupuesto_items.*.total' => 'required|numeric|min:0',
            ]);

            // Limpiar monto si viene con formato de moneda
            $montoLimpio = 0;
            if (isset($validated['monto'])) {
                if (is_string($validated['monto'])) {
                    // Remover símbolos de moneda y espacios
                    $montoLimpio = str_replace(['$', ' ', '€'], '', $validated['monto']);
                    // Remover puntos (separadores de miles) y reemplazar coma por punto (decimal)
                    $montoLimpio = str_replace('.', '', $montoLimpio);
                    $montoLimpio = str_replace(',', '.', $montoLimpio);
                    $montoLimpio = (float) $montoLimpio;
                } else {
                    $montoLimpio = (float) $validated['monto'];
                }
            }

            // Calcular monto total desde los items del presupuesto si existen
            // Si viene monto del frontend (ya incluye IVA), usarlo. Solo calcular desde items si no viene monto
            $montoOT = $montoLimpio;
            if (!empty($validated['presupuesto_items']) && $montoOT == 0) {
                // Solo calcular desde items si no hay monto enviado (el monto del frontend ya incluye IVA)
                $montoOT = array_sum(array_column($validated['presupuesto_items'], 'total'));
            }

            // Si hay monto del frontend (que ya incluye IVA), usarlo. Si no, usar el calculado
            if ($montoLimpio > 0) {
                $montoOT = $montoLimpio;
            }
            
            $validated['monto'] = $montoOT;

            // Validar saldo disponible del contrato (excluyendo la OT actual)
            $contratoId = $validated['contrato_id'] ?? $orden->contrato_id;
            if ($contratoId) {
                $contrato = Contrato::findOrFail($contratoId);
                // Calcular monto usado excluyendo la OT actual
                $montoUsado = OrdenTrabajo::where('contrato_id', $contratoId)
                    ->where('id', '!=', $orden->id)
                    ->sum('monto') ?? 0;
                $saldoDisponible = (float) $contrato->monto_real - $montoUsado;
                
                if ($montoOT > $saldoDisponible + 0.0001) {
                    $mensajeError = 'El monto total ($' . number_format($montoOT, 0, ',', '.') . ') supera el saldo disponible del contrato ($' . number_format($saldoDisponible, 0, ',', '.') . ').';
                    
                    DB::rollBack();
                    
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => $mensajeError
                        ], 422);
                    }
                    
                    return back()->withErrors([
                        'monto' => $mensajeError
                    ])->withInput();
                }
            }

            // Guardar el monto anterior para comparar
            $montoAnterior = $orden->monto ?? 0;
            
            $orden->update($validated);

            // Si la OT está asociada a una OC, actualizar el monto_total de la OC
            if ($orden->orden_compra_id) {
                $oc = OrdenCompra::find($orden->orden_compra_id);
                if ($oc) {
                    // Recalcular el monto_total sumando todos los montos de las OTs asociadas
                    $montoTotalOC = OrdenTrabajo::where('orden_compra_id', $oc->id)
                        ->sum('monto') ?? 0;
                    
                    $oc->update([
                        'monto_total' => $montoTotalOC
                    ]);
                }
            }

            // Si hay items del presupuesto, actualizar el presupuesto
            if (!empty($validated['presupuesto_items'])) {
                // Obtener o crear presupuesto
                $presupuestoOt = $orden->presupuestoOt;
                if (!$presupuestoOt) {
                    $presupuestoOt = PresupuestoOt::create([
                        'ot_id' => $orden->id,
                        'usuario_id' => auth()->id() ?? null,
                        'fecha' => $validated['fecha_ot'] ?? now(),
                    ]);
                } else {
                    // Actualizar fecha si es necesario
                    if (isset($validated['fecha_ot'])) {
                        $presupuestoOt->fecha = $validated['fecha_ot'];
                        $presupuestoOt->save();
                    }
                }

                // Eliminar items antiguos
                $presupuestoOt->items()->delete();

                // Crear nuevos items
                foreach ($validated['presupuesto_items'] as $item) {
                    $numeroPartida = $item['numero_partida'] ?? null;
                    $partidaTexto = $item['partida'] ?? '';
                    
                    // Si no viene numero_partida pero la partida tiene formato "numero - partida", extraerlo
                    if (!$numeroPartida && $partidaTexto && strpos($partidaTexto, ' - ') !== false) {
                        $partes = explode(' - ', $partidaTexto, 2);
                        $numeroPartida = trim($partes[0]);
                        $partidaTexto = trim($partes[1] ?? $partidaTexto);
                    }
                    
                    PresupuestoOtItem::create([
                        'presupuesto_ot_id' => $presupuestoOt->id,
                        'item' => $item['item'],
                        'partida' => $partidaTexto,
                        'numero_partida' => $numeroPartida,
                        'unidad' => $item['unidad'],
                        'cantidad' => round((float) $item['cantidad'], 2),
                        'precio' => (float) $item['precio'],
                        'total' => round((float) $item['total'], 0), // Redondear total a 0 decimales
                    ]);
                }
            }

            DB::commit();

            // Si es petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->ajax()) {
                $mensaje = 'Orden de trabajo actualizada exitosamente';
                if (!empty($validated['presupuesto_items']) && count($validated['presupuesto_items']) > 0) {
                    $mensaje .= ' con presupuesto de ' . count($validated['presupuesto_items']) . ' partida(s)';
                }
                return response()->json([
                    'success' => true,
                    'message' => $mensaje
                ]);
            }

            return redirect()->route('ordenes-trabajo.index')->with('success', 'Orden de trabajo actualizada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            
            $mensajeError = 'Error al actualizar la orden de trabajo: ' . $e->getMessage();
            
            if ($request->expectsJson() || $request->ajax()) {
                \Log::error('Error al actualizar OT: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError
                ], 500);
            }
            
            return back()->withErrors([
                'error' => $mensajeError
            ])->withInput();
        }
    }

    /**
     * Eliminar orden de trabajo
     */
    public function destroy(Request $request, $id)
    {
        try {
            $orden = OrdenTrabajo::findOrFail($id);
            
            // Verificar si tiene OC asociada
            if ($orden->orden_compra_id) {
                $mensaje = 'No se puede eliminar la orden de trabajo porque está asociada a una Orden de Compra. Primero debe desasociarla desde la Orden de Compra.';
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $mensaje
                    ], 422);
                }

                return redirect()->route('ordenes-trabajo.index')
                    ->with('error', $mensaje);
            }
            
            $orden->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orden de trabajo eliminada exitosamente'
                ]);
            }

            return redirect()->route('ordenes-trabajo.index')->with('success', 'Orden de trabajo eliminada exitosamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden de trabajo no encontrada'
                ], 404);
            }

            return redirect()->route('ordenes-trabajo.index')
                ->with('error', 'Orden de trabajo no encontrada');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la orden de trabajo: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('ordenes-trabajo.index')
                ->with('error', 'Error al eliminar la orden de trabajo: ' . $e->getMessage());
        }
    }

    /**
     * Crear múltiples órdenes de trabajo (ingreso masivo)
     */
    public function storeMasiva(Request $request)
    {
        $validated = $request->validate([
            'contrato_id_masiva' => 'required|exists:contratos,id',
            'estado_masiva' => 'required|in:Pendiente,Enviado',
            'fecha_masiva' => 'required|date',
            'establecimiento_id_masiva' => 'required|array',
            'establecimiento_id_masiva.*' => 'required|exists:establecimientos,id',
            'monto_masiva' => 'required|array',
            'monto_masiva.*' => 'required|numeric|min:0',
        ]);

        $contrato = Contrato::findOrFail($validated['contrato_id_masiva']);
        
        // Calcular monto total usado
        $montoUsado = OrdenTrabajo::where('contrato_id', $contrato->id)->sum('monto') ?? 0;
        $saldoDisponible = (float) $contrato->monto_real - $montoUsado;
        
        // Calcular monto total de las nuevas OT
        $montoTotal = array_sum($validated['monto_masiva']);
        
        // Validar que no exceda el saldo disponible
        if ($montoTotal > $saldoDisponible + 0.0001) {
            return back()->withErrors([
                'monto_total' => 'El monto total (' . number_format($montoTotal, 0, ',', '.') . ') supera el saldo disponible del contrato ($' . number_format($saldoDisponible, 0, ',', '.') . ').'
            ])->withInput();
        }

        // Generar números de OT secuenciales
        $añoActual = date('Y', strtotime($validated['fecha_masiva']));
        $ultimoNumero = OrdenTrabajo::where('numero_ot', 'LIKE', '%-' . $añoActual)
            ->whereNotNull('numero_ot')
            ->orderByRaw("CAST(SUBSTRING_INDEX(numero_ot, '-', 1) AS UNSIGNED) DESC")
            ->value('numero_ot');
        
        $proximoNumero = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            if (count($partes) === 2 && $partes[1] == $añoActual) {
                $numeroActual = (int) $partes[0];
                $proximoNumero = $numeroActual + 1;
            }
        }

        // Crear múltiples OT
        $creadas = 0;
        foreach ($validated['establecimiento_id_masiva'] as $index => $establecimientoId) {
            $establecimiento = Establecimiento::find($establecimientoId);
            
            $numeroOt = str_pad($proximoNumero, 4, '0', STR_PAD_LEFT) . '-' . $añoActual;
            
            OrdenTrabajo::create([
                'contrato_id' => $validated['contrato_id_masiva'],
                'establecimiento_id' => $establecimientoId,
                'comuna_id' => $establecimiento->comuna_id ?? null,
                'fecha_ot' => $validated['fecha_masiva'],
                'estado' => $validated['estado_masiva'],
                'tipo' => 'Normal', // Por defecto Normal en masiva
                'monto' => $validated['monto_masiva'][$index],
                'numero_ot' => $numeroOt,
            ]);
            $creadas++;
            $proximoNumero++;
        }

        return redirect()->route('ordenes-trabajo.index')
            ->with('success', "Se crearon {$creadas} órdenes de trabajo exitosamente");
    }
}

