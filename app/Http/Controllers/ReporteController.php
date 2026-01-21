<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use App\Models\OrdenTrabajo;
use App\Models\OrdenCompra;
use App\Models\Contrato;
use App\Models\Comuna;
use App\Models\MontoConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        // Obtener filtros del request
        $filtros = [
            'anio' => $request->get('anio'),
            'comuna_id' => $request->get('comuna_id'),
            'establecimiento_id' => $request->get('establecimiento_id'),
            'contrato_id' => $request->get('contrato_id'),
            'item_id' => $request->get('item_id')
        ];
        
        try {
            // Métricas principales
            $metricas = $this->obtenerMetricas($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerMetricas: ' . $e->getMessage());
            $metricas = [
                'requerimientos' => ['total' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'resueltos' => 0],
                'ordenes_trabajo' => ['total' => 0, 'sin_oc' => 0, 'con_oc' => 0],
                'ordenes_compra' => ['total' => 0, 'pagadas' => 0, 'pendientes' => 0],
                'presupuesto' => ['total' => 0, 'comprometido' => 0, 'ejecutado' => 0, 'facturado' => 0, 'saldo_disponible' => 0]
            ];
        }
        
        try {
            // Datos para gráficos
            $datosGraficos = $this->obtenerDatosGraficos($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosGraficos: ' . $e->getMessage());
            $datosGraficos = [
                'requerimientos_por_estado' => [],
                'tendencias_mensuales' => ['meses' => [], 'requerimientos' => [], 'ots' => [], 'ocs' => []],
                'presupuesto_por_fuente' => []
            ];
        }
        
        try {
            // Tablas de datos
            $tablasDatos = $this->obtenerTablasDatos($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerTablasDatos: ' . $e->getMessage());
            $tablasDatos = [
                'top_comunas' => collect(),
                'contratos_activos' => collect(),
                'emergencias_pendientes' => collect(),
                'ots_comprometidas' => collect(),
                'top_establecimientos' => collect()
            ];
        }
        
        try {
            // Alertas
            $alertas = $this->obtenerAlertas($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerAlertas: ' . $e->getMessage());
            $alertas = [];
        }
        
        try {
            // Datos para filtros
            $comunas = Comuna::orderBy('nombre')->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo comunas: ' . $e->getMessage());
            $comunas = collect();
        }
        
        try {
            // Datos adicionales para la pestaña de filtros
            $establecimientos = \App\Models\Establecimiento::orderBy('nombre')->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo establecimientos: ' . $e->getMessage());
            $establecimientos = collect();
        }
        
        try {
            $contratos = Contrato::with('proyecto')->orderBy('nombre_contrato')->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo contratos: ' . $e->getMessage());
            $contratos = collect();
        }
        
        try {
            $items = \App\Models\Item::orderBy('nombre')->get();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo items: ' . $e->getMessage());
            $items = collect();
        }
        
        // Obtener años disponibles desde las fechas de OTs, OCs y Requerimientos
        try {
            $añosOTs = OrdenTrabajo::selectRaw('YEAR(fecha_ot) as año')
                ->whereNotNull('fecha_ot')
                ->distinct()
                ->orderBy('año', 'desc')
                ->pluck('año');
            
            $añosOCs = OrdenCompra::selectRaw('YEAR(fecha) as año')
                ->whereNotNull('fecha')
                ->distinct()
                ->orderBy('año', 'desc')
                ->pluck('año');
            
            $añosRequerimientos = Requerimiento::selectRaw('YEAR(fecha_ingreso) as año')
                ->whereNotNull('fecha_ingreso')
                ->distinct()
                ->orderBy('año', 'desc')
                ->pluck('año');
            
            $añosDisponibles = $añosOTs->merge($añosOCs)->merge($añosRequerimientos)->unique()->sortDesc()->values();
            
            // Si no hay años, agregar el año actual
            if ($añosDisponibles->isEmpty()) {
                $añosDisponibles = collect([date('Y')]);
            }
        } catch (\Exception $e) {
            \Log::error('Error obteniendo años: ' . $e->getMessage());
            $añosDisponibles = collect([date('Y')]);
        }
        
        try {
            // Gasto por comuna (para el gráfico inicial)
            $gastoPorComuna = $this->obtenerGastoPorComuna($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerGastoPorComuna: ' . $e->getMessage());
            $gastoPorComuna = collect();
        }
        
        try {
            // Datos de tendencias y proyección
            $tendenciasProyeccion = $this->obtenerTendenciasProyeccion($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerTendenciasProyeccion: ' . $e->getMessage());
            $tendenciasProyeccion = [
                'primera_ot' => null,
                'meses_datos' => [],
                'promedio_mensual' => 0,
                'gasto_acumulado' => 0,
                'meses_transcurridos' => 0,
                'proyeccion_anual' => 0,
                'presupuesto_total' => 0,
                'diferencia' => 0,
                'porcentaje_utilizado' => 0
            ];
        }
        
        try {
            // OCs filtradas con sus OTs
            $ocsFiltradas = $this->obtenerOCsFiltradas($filtros);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerOCsFiltradas: ' . $e->getMessage());
            $ocsFiltradas = collect();
        }
        
        return view('reportes.index', compact(
            'metricas',
            'datosGraficos',
            'tablasDatos',
            'alertas',
            'comunas',
            'gastoPorComuna',
            'tendenciasProyeccion',
            'establecimientos',
            'contratos',
            'items',
            'añosDisponibles',
            'ocsFiltradas'
        ));
    }
    
    private function obtenerMetricas($filtros = [])
    {
        // Aplicar filtros a requerimientos
        $queryRequerimientos = Requerimiento::query();
        if (!empty($filtros['anio'])) {
            $queryRequerimientos->whereYear('fecha_ingreso', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryRequerimientos->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryRequerimientos->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryRequerimientos->where('contrato_id', $filtros['contrato_id']);
        }
        
        // Requerimientos
        $totalRequerimientos = (clone $queryRequerimientos)->count();
        $requerimientosPendientes = (clone $queryRequerimientos)->where(function($q) {
            $q->where('estado', 'pendiente')->orWhereNull('estado')->orWhere('estado', '');
        })->count();
        $requerimientosEnProceso = (clone $queryRequerimientos)->where(function($q) {
            $q->where('estado', 'en proceso')
              ->orWhere('estado', 'en_proceso')
              ->orWhere('estado', 'proceso');
        })->count();
        $requerimientosResueltos = (clone $queryRequerimientos)->where('estado', 'resuelto')->count();
        
        // Aplicar filtros a OTs
        $queryOTs = OrdenTrabajo::query();
        if (!empty($filtros['anio'])) {
            $queryOTs->whereYear('fecha_ot', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryOTs->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryOTs->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryOTs->where('contrato_id', $filtros['contrato_id']);
        }
        if (!empty($filtros['item_id'])) {
            $queryOTs->whereHas('contrato.proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        
        // Órdenes de Trabajo
        $totalOTs = (clone $queryOTs)->count();
        $otsSinOC = (clone $queryOTs)->whereNull('orden_compra_id')->count();
        $otsConOC = (clone $queryOTs)->whereNotNull('orden_compra_id')->count();
        
        // Aplicar filtros a OCs
        $queryOCs = OrdenCompra::query();
        if (!empty($filtros['anio'])) {
            $queryOCs->whereYear('fecha', $filtros['anio']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryOCs->where('contrato_id', $filtros['contrato_id']);
        }
        if (!empty($filtros['item_id'])) {
            $queryOCs->whereHas('contrato.proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        
        // Órdenes de Compra
        $totalOCs = (clone $queryOCs)->count();
        $ocsPagadas = (clone $queryOCs)->where('estado', 'Pagado')->count();
        $ocsPendientes = (clone $queryOCs)->where('estado', '!=', 'Pagado')->count();
        
        // Presupuesto (aplicar filtros a OTs y OCs)
        $presupuestoTotal = MontoConfiguracion::whereIn('codigo', [
            'subvencion_general',
            'subvencion_mantenimiento',
            'mantencion_vtf'
        ])->sum('monto');
        
        $comprometido = (clone $queryOTs)->whereNull('orden_compra_id')->sum('monto');
        $ejecutado = (clone $queryOTs)->whereNotNull('orden_compra_id')->sum('monto');
        $facturado = (clone $queryOCs)->where('estado', 'Pagado')->sum('monto_total');
        $saldoDisponible = $presupuestoTotal - ($comprometido + $ejecutado);
        
        return [
            'requerimientos' => [
                'total' => $totalRequerimientos,
                'pendientes' => $requerimientosPendientes,
                'en_proceso' => $requerimientosEnProceso,
                'resueltos' => $requerimientosResueltos
            ],
            'ordenes_trabajo' => [
                'total' => $totalOTs,
                'sin_oc' => $otsSinOC,
                'con_oc' => $otsConOC
            ],
            'ordenes_compra' => [
                'total' => $totalOCs,
                'pagadas' => $ocsPagadas,
                'pendientes' => $ocsPendientes
            ],
            'presupuesto' => [
                'total' => $presupuestoTotal,
                'comprometido' => $comprometido,
                'ejecutado' => $ejecutado,
                'facturado' => $facturado,
                'saldo_disponible' => $saldoDisponible
            ]
        ];
    }
    
    private function obtenerDatosGraficos($filtros = [])
    {
        // Aplicar filtros base a requerimientos
        $queryRequerimientos = Requerimiento::query();
        if (!empty($filtros['anio'])) {
            $queryRequerimientos->whereYear('fecha_ingreso', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryRequerimientos->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryRequerimientos->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryRequerimientos->where('contrato_id', $filtros['contrato_id']);
        }
        
        // Requerimientos por estado
        $requerimientosPorEstado = [
            'Pendientes' => (clone $queryRequerimientos)->where(function($q) {
                $q->where('estado', 'pendiente')->orWhereNull('estado')->orWhere('estado', '');
            })->count(),
            'En Proceso' => (clone $queryRequerimientos)->where(function($q) {
                $q->where('estado', 'en proceso')
                  ->orWhere('estado', 'en_proceso')
                  ->orWhere('estado', 'proceso');
            })->count(),
            'Resueltos' => (clone $queryRequerimientos)->where('estado', 'resuelto')->count()
        ];
        
        // Tendencias mensuales (últimos 6 meses)
        $meses = [];
        $requerimientosMensual = [];
        $otsMensual = [];
        $ocsMensual = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $mes = $fecha->format('M Y');
            $meses[] = $mes;
            
            // Aplicar filtros a requerimientos mensuales
            $queryReqMes = Requerimiento::whereYear('fecha_ingreso', $fecha->year)
                ->whereMonth('fecha_ingreso', $fecha->month);
            if (!empty($filtros['comuna_id'])) {
                $queryReqMes->where('comuna_id', $filtros['comuna_id']);
            }
            if (!empty($filtros['establecimiento_id'])) {
                $queryReqMes->where('establecimiento_id', $filtros['establecimiento_id']);
            }
            if (!empty($filtros['contrato_id'])) {
                $queryReqMes->where('contrato_id', $filtros['contrato_id']);
            }
            $requerimientosMensual[] = $queryReqMes->count();
            
            // Aplicar filtros a OTs mensuales
            $queryOTMes = OrdenTrabajo::whereYear('fecha_ot', $fecha->year)
                ->whereMonth('fecha_ot', $fecha->month);
            if (!empty($filtros['comuna_id'])) {
                $queryOTMes->where('comuna_id', $filtros['comuna_id']);
            }
            if (!empty($filtros['establecimiento_id'])) {
                $queryOTMes->where('establecimiento_id', $filtros['establecimiento_id']);
            }
            if (!empty($filtros['contrato_id'])) {
                $queryOTMes->where('contrato_id', $filtros['contrato_id']);
            }
            if (!empty($filtros['item_id'])) {
                $queryOTMes->whereHas('contrato.proyecto', function($q) use ($filtros) {
                    $q->where('item_id', $filtros['item_id']);
                });
            }
            $otsMensual[] = $queryOTMes->count();
            
            // Aplicar filtros a OCs mensuales
            $queryOCMes = OrdenCompra::whereYear('fecha', $fecha->year)
                ->whereMonth('fecha', $fecha->month);
            if (!empty($filtros['contrato_id'])) {
                $queryOCMes->where('contrato_id', $filtros['contrato_id']);
            }
            if (!empty($filtros['item_id'])) {
                $queryOCMes->whereHas('contrato.proyecto', function($q) use ($filtros) {
                    $q->where('item_id', $filtros['item_id']);
                });
            }
            $ocsMensual[] = $queryOCMes->count();
        }
        
        // Presupuesto por fuente de financiamiento
        $fuentesFinanciamiento = MontoConfiguracion::orderBy('id')->get();
        $presupuestoPorFuente = [];
        foreach ($fuentesFinanciamiento as $fuente) {
            $presupuestoPorFuente[] = [
                'nombre' => $fuente->nombre,
                'monto' => $fuente->monto
            ];
        }
        
        return [
            'requerimientos_por_estado' => $requerimientosPorEstado,
            'tendencias_mensuales' => [
                'meses' => $meses,
                'requerimientos' => $requerimientosMensual,
                'ots' => $otsMensual,
                'ocs' => $ocsMensual
            ],
            'presupuesto_por_fuente' => $presupuestoPorFuente
        ];
    }
    
    private function obtenerTablasDatos($filtros = [])
    {
        // Aplicar filtros base a requerimientos
        $queryRequerimientos = Requerimiento::query();
        if (!empty($filtros['anio'])) {
            $queryRequerimientos->whereYear('fecha_ingreso', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryRequerimientos->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryRequerimientos->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryRequerimientos->where('contrato_id', $filtros['contrato_id']);
        }
        
        // Top 5 comunas con más requerimientos
        $queryTopComunas = Comuna::query();
        if (!empty($filtros['anio'])) {
            $queryTopComunas->withCount(['requerimientos' => function($q) use ($filtros) {
                $q->whereYear('fecha_ingreso', $filtros['anio']);
                if (!empty($filtros['establecimiento_id'])) {
                    $q->where('establecimiento_id', $filtros['establecimiento_id']);
                }
                if (!empty($filtros['contrato_id'])) {
                    $q->where('contrato_id', $filtros['contrato_id']);
                }
            }]);
        } else {
            $queryTopComunas->withCount('requerimientos');
        }
        $topComunas = $queryTopComunas->orderBy('requerimientos_count', 'desc')->limit(5)->get();
        
        // Contratos más activos (por número de OTs) - aplicar filtros
        $queryContratosActivos = Contrato::query();
        if (!empty($filtros['anio'])) {
            $queryContratosActivos->where(function($q) use ($filtros) {
                $q->whereYear('fecha_inicio', $filtros['anio'])
                  ->orWhereYear('fecha_oc', $filtros['anio'])
                  ->orWhereYear('created_at', $filtros['anio']);
            });
        }
        if (!empty($filtros['item_id'])) {
            $queryContratosActivos->whereHas('proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        $contratosActivos = $queryContratosActivos->withCount(['ordenesTrabajo' => function($q) use ($filtros) {
            if (!empty($filtros['anio'])) {
                $q->whereYear('fecha_ot', $filtros['anio']);
            }
            if (!empty($filtros['comuna_id'])) {
                $q->where('comuna_id', $filtros['comuna_id']);
            }
            if (!empty($filtros['establecimiento_id'])) {
                $q->where('establecimiento_id', $filtros['establecimiento_id']);
            }
        }])->orderBy('ordenes_trabajo_count', 'desc')->limit(5)->get();
        
        // Requerimientos de emergencia pendientes - aplicar filtros
        $queryEmergencias = (clone $queryRequerimientos)->where('emergencia', true)
            ->where(function($q) {
                $q->where('estado', 'pendiente')
                  ->orWhereNull('estado')
                  ->orWhere('estado', '');
            })
            ->with(['comuna', 'establecimiento'])
            ->orderBy('fecha_ingreso', 'desc')
            ->limit(10);
        $emergenciasPendientes = $queryEmergencias->get();
        
        // OTs sin OC (comprometidas) por más tiempo - aplicar filtros
        $queryOTsComprometidas = OrdenTrabajo::whereNull('orden_compra_id');
        if (!empty($filtros['anio'])) {
            $queryOTsComprometidas->whereYear('fecha_ot', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryOTsComprometidas->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryOTsComprometidas->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryOTsComprometidas->where('contrato_id', $filtros['contrato_id']);
        }
        if (!empty($filtros['item_id'])) {
            $queryOTsComprometidas->whereHas('contrato.proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        $otsComprometidas = $queryOTsComprometidas->with(['comuna', 'establecimiento', 'contrato'])
            ->orderBy('fecha_ot', 'asc')
            ->limit(10)
            ->get();
        
        // Top 5 establecimientos con más OTs - aplicar filtros
        $queryTopEstablecimientos = \App\Models\Establecimiento::query();
        if (!empty($filtros['comuna_id'])) {
            $queryTopEstablecimientos->where('comuna_id', $filtros['comuna_id']);
        }
        $queryTopEstablecimientos->withCount(['ordenesTrabajo' => function($q) use ($filtros) {
            if (!empty($filtros['anio'])) {
                $q->whereYear('fecha_ot', $filtros['anio']);
            }
            if (!empty($filtros['contrato_id'])) {
                $q->where('contrato_id', $filtros['contrato_id']);
            }
            if (!empty($filtros['item_id'])) {
                $q->whereHas('contrato.proyecto', function($q2) use ($filtros) {
                    $q2->where('item_id', $filtros['item_id']);
                });
            }
        }]);
        $topEstablecimientos = $queryTopEstablecimientos->orderBy('ordenes_trabajo_count', 'desc')->limit(5)->get();
        
        return [
            'top_comunas' => $topComunas,
            'contratos_activos' => $contratosActivos,
            'emergencias_pendientes' => $emergenciasPendientes,
            'ots_comprometidas' => $otsComprometidas,
            'top_establecimientos' => $topEstablecimientos
        ];
    }
    
    private function obtenerAlertas($filtros = [])
    {
        $alertas = [];
        
        // Aplicar filtros a OTs para cálculos de saldo
        $queryOTs = OrdenTrabajo::query();
        if (!empty($filtros['anio'])) {
            $queryOTs->whereYear('fecha_ot', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryOTs->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryOTs->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryOTs->where('contrato_id', $filtros['contrato_id']);
        }
        if (!empty($filtros['item_id'])) {
            $queryOTs->whereHas('contrato.proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        
        // Saldos bajos o negativos
        $presupuestoTotal = MontoConfiguracion::whereIn('codigo', [
            'subvencion_general',
            'subvencion_mantenimiento',
            'mantencion_vtf'
        ])->sum('monto');
        
        $comprometido = (clone $queryOTs)->whereNull('orden_compra_id')->sum('monto');
        $ejecutado = (clone $queryOTs)->whereNotNull('orden_compra_id')->sum('monto');
        $saldoDisponible = $presupuestoTotal - ($comprometido + $ejecutado);
        
        if ($saldoDisponible < 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'icono' => 'exclamation-triangle',
                'titulo' => 'Saldo Negativo',
                'mensaje' => 'El saldo disponible es negativo: $' . number_format($saldoDisponible, 0, ',', '.')
            ];
        } elseif ($saldoDisponible < ($presupuestoTotal * 0.1)) {
            $alertas[] = [
                'tipo' => 'warning',
                'icono' => 'exclamation-circle',
                'titulo' => 'Saldo Bajo',
                'mensaje' => 'El saldo disponible es menor al 10% del presupuesto total'
            ];
        }
        
        // Requerimientos de emergencia sin resolver - aplicar filtros
        $queryEmergencias = Requerimiento::where('emergencia', true)
            ->where(function($q) {
                $q->where('estado', 'pendiente')
                  ->orWhereNull('estado')
                  ->orWhere('estado', '');
            });
        if (!empty($filtros['anio'])) {
            $queryEmergencias->whereYear('fecha_ingreso', $filtros['anio']);
        }
        if (!empty($filtros['comuna_id'])) {
            $queryEmergencias->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryEmergencias->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryEmergencias->where('contrato_id', $filtros['contrato_id']);
        }
        $emergenciasPendientes = $queryEmergencias->count();
        
        if ($emergenciasPendientes > 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'icono' => 'exclamation-triangle-fill',
                'titulo' => 'Emergencias Pendientes',
                'mensaje' => "Hay {$emergenciasPendientes} requerimiento(s) de emergencia sin resolver"
            ];
        }
        
        // OTs comprometidas por más de 30 días - aplicar filtros
        $queryOTsAntiguas = (clone $queryOTs)->whereNull('orden_compra_id')
            ->where('fecha_ot', '<', now()->subDays(30));
        $otsAntiguas = $queryOTsAntiguas->count();
        
        if ($otsAntiguas > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'icono' => 'clock-history',
                'titulo' => 'OTs Comprometidas Antiguas',
                'mensaje' => "Hay {$otsAntiguas} orden(es) de trabajo comprometida(s) por más de 30 días"
            ];
        }
        
        return $alertas;
    }
    
    public function obtenerGastoPorEstablecimientoFiltrado(Request $request)
    {
        $comunaId = $request->get('comuna_id');
        
        if ($comunaId) {
            // Si hay comuna seleccionada, mostrar establecimientos de esa comuna
            $gastoPorEstablecimiento = $this->obtenerGastoPorEstablecimiento($comunaId);
            return response()->json([
                'success' => true,
                'tipo' => 'establecimientos',
                'datos' => $gastoPorEstablecimiento
            ]);
        } else {
            // Si no hay comuna, mostrar comunas
            $gastoPorComuna = $this->obtenerGastoPorComuna();
            return response()->json([
                'success' => true,
                'tipo' => 'comunas',
                'datos' => $gastoPorComuna
            ]);
        }
    }
    
    private function obtenerGastoPorComuna($filtros = [])
    {
        $comunas = Comuna::with('establecimientos')->get();
        
        return $comunas->map(function($comuna) use ($filtros) {
            $queryGasto = \App\Models\OrdenTrabajo::whereHas('establecimiento', function($query) use ($comuna) {
                $query->where('comuna_id', $comuna->id);
            });
            
            // Aplicar filtros
            if (!empty($filtros['anio'])) {
                $queryGasto->whereYear('fecha_ot', $filtros['anio']);
            }
            if (!empty($filtros['establecimiento_id'])) {
                $queryGasto->where('establecimiento_id', $filtros['establecimiento_id']);
            }
            if (!empty($filtros['contrato_id'])) {
                $queryGasto->where('contrato_id', $filtros['contrato_id']);
            }
            if (!empty($filtros['item_id'])) {
                $queryGasto->whereHas('contrato.proyecto', function($q) use ($filtros) {
                    $q->where('item_id', $filtros['item_id']);
                });
            }
            
            $gastoTotal = $queryGasto->sum('monto');
            
            return [
                'id' => $comuna->id,
                'nombre' => $comuna->nombre,
                'gasto' => (float) $gastoTotal
            ];
        })->sortByDesc('gasto')->values(); // Mostrar todas, ordenadas por gasto
    }
    
    private function obtenerGastoPorEstablecimiento($comunaId)
    {
        $establecimientos = \App\Models\Establecimiento::where('comuna_id', $comunaId)
            ->with('comuna')
            ->get();
        
        return $establecimientos->map(function($establecimiento) {
            $gastoTotal = \App\Models\OrdenTrabajo::where('establecimiento_id', $establecimiento->id)
                ->sum('monto');
            
            return [
                'id' => $establecimiento->id,
                'nombre' => $establecimiento->nombre,
                'comuna' => $establecimiento->comuna ? $establecimiento->comuna->nombre : '-',
                'gasto' => (float) $gastoTotal
            ];
        })->sortByDesc('gasto')->values(); // Mostrar todos, ordenados por gasto
    }
    
    private function obtenerTendenciasProyeccion($filtros = [])
    {
        // Aplicar filtros base a OTs
        $queryOTsBase = OrdenTrabajo::whereNotNull('fecha_ot');
        if (!empty($filtros['comuna_id'])) {
            $queryOTsBase->where('comuna_id', $filtros['comuna_id']);
        }
        if (!empty($filtros['establecimiento_id'])) {
            $queryOTsBase->where('establecimiento_id', $filtros['establecimiento_id']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryOTsBase->where('contrato_id', $filtros['contrato_id']);
        }
        if (!empty($filtros['item_id'])) {
            $queryOTsBase->whereHas('contrato.proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        
        // Obtener la primera OT (fecha más antigua) con filtros
        $primeraOT = (clone $queryOTsBase)->orderBy('fecha_ot', 'asc')->first();
        
        if (!$primeraOT) {
            return [
                'primera_ot' => null,
                'meses_datos' => [],
                'promedio_mensual' => 0,
                'gasto_acumulado' => 0,
                'meses_transcurridos' => 0,
                'proyeccion_anual' => 0,
                'presupuesto_total' => 0,
                'diferencia' => 0,
                'porcentaje_utilizado' => 0
            ];
        }
        
        $fechaInicio = $primeraOT->fecha_ot;
        $fechaActual = now();
        // Si hay filtro de año, usar ese año, sino el año actual
        $anoActual = !empty($filtros['anio']) ? $filtros['anio'] : $fechaActual->year;
        
        // Calcular gasto mensual desde la primera OT hasta ahora
        $mesesDatos = [];
        $gastoAcumulado = 0;
        
        $fecha = $fechaInicio->copy()->startOfMonth();
        while ($fecha->year == $anoActual && $fecha->lte($fechaActual->copy()->endOfMonth())) {
            $queryGastoMensual = (clone $queryOTsBase)->whereYear('fecha_ot', $fecha->year)
                ->whereMonth('fecha_ot', $fecha->month);
            $gastoMensual = $queryGastoMensual->sum('monto');
            
            $mesesDatos[] = [
                'mes' => $fecha->locale('es')->translatedFormat('M Y'),
                'mes_numero' => $fecha->month,
                'gasto' => (float) $gastoMensual
            ];
            
            $gastoAcumulado += $gastoMensual;
            $fecha->addMonth();
        }
        
        $mesesTranscurridos = count($mesesDatos);
        $promedioMensual = $mesesTranscurridos > 0 ? $gastoAcumulado / $mesesTranscurridos : 0;
        
        // Presupuesto total
        $presupuestoTotal = MontoConfiguracion::whereIn('codigo', [
            'subvencion_general',
            'subvencion_mantenimiento',
            'mantencion_vtf'
        ])->sum('monto');
        
        // Calcular meses restantes en el año
        $mesActual = $fechaActual->month;
        $mesesRestantes = 12 - $mesActual;
        
        // Calcular cuánto queda por gastar
        $presupuestoRestante = $presupuestoTotal - $gastoAcumulado;
        
        // Calcular cuánto se necesita gastar mensualmente para alcanzar el presupuesto
        $gastoNecesarioMensual = $mesesRestantes > 0 ? $presupuestoRestante / $mesesRestantes : 0;
        
        // Proyección anual basada en el promedio mensual actual
        // Fórmula: Promedio Mensual × 12 meses
        $proyeccionAnual = $promedioMensual * 12;
        
        // Diferencia entre lo que se necesita gastar mensualmente vs el promedio histórico
        $diferenciaMensual = $gastoNecesarioMensual - $promedioMensual;
        
        // Porcentaje utilizado
        $porcentajeUtilizado = $presupuestoTotal > 0 ? ($gastoAcumulado / $presupuestoTotal) * 100 : 0;
        
        return [
            'primera_ot' => $fechaInicio->format('d/m/Y'),
            'meses_datos' => $mesesDatos,
            'promedio_mensual' => $promedioMensual,
            'gasto_acumulado' => $gastoAcumulado,
            'meses_transcurridos' => $mesesTranscurridos,
            'meses_restantes' => $mesesRestantes,
            'presupuesto_restante' => $presupuestoRestante,
            'gasto_necesario_mensual' => $gastoNecesarioMensual,
            'proyeccion_anual' => $proyeccionAnual, // Promedio mensual × 12
            'presupuesto_total' => $presupuestoTotal,
            'diferencia_mensual' => $diferenciaMensual,
            'porcentaje_utilizado' => $porcentajeUtilizado,
            'diferencia_proyeccion_presupuesto' => $proyeccionAnual - $presupuestoTotal
        ];
    }
    
    private function obtenerOCsFiltradas($filtros = [])
    {
        // Query base para OCs
        $queryOCs = OrdenCompra::with(['contrato.proyecto.item', 'oferente']);
        
        // Aplicar filtros
        if (!empty($filtros['anio'])) {
            $queryOCs->whereYear('fecha', $filtros['anio']);
        }
        if (!empty($filtros['contrato_id'])) {
            $queryOCs->where('contrato_id', $filtros['contrato_id']);
        }
        if (!empty($filtros['item_id'])) {
            $queryOCs->whereHas('contrato.proyecto', function($q) use ($filtros) {
                $q->where('item_id', $filtros['item_id']);
            });
        }
        
        // Obtener OCs
        $ocs = $queryOCs->orderBy('fecha', 'desc')->get();
        
        // Para cada OC, obtener sus OTs filtradas
        return $ocs->map(function($oc) use ($filtros) {
            $queryOTs = $oc->ordenesTrabajo();
            
            // Aplicar filtros a las OTs
            if (!empty($filtros['anio'])) {
                $queryOTs->whereYear('fecha_ot', $filtros['anio']);
            }
            if (!empty($filtros['comuna_id'])) {
                $queryOTs->where('comuna_id', $filtros['comuna_id']);
            }
            if (!empty($filtros['establecimiento_id'])) {
                $queryOTs->where('establecimiento_id', $filtros['establecimiento_id']);
            }
            if (!empty($filtros['contrato_id'])) {
                $queryOTs->where('contrato_id', $filtros['contrato_id']);
            }
            if (!empty($filtros['item_id'])) {
                $queryOTs->whereHas('contrato.proyecto', function($q) use ($filtros) {
                    $q->where('item_id', $filtros['item_id']);
                });
            }
            
            $oc->ots_filtradas = $queryOTs->with(['comuna', 'establecimiento', 'contrato'])->get();
            $oc->total_ots_filtradas = $oc->ots_filtradas->count();
            $oc->monto_total_ots_filtradas = $oc->ots_filtradas->sum('monto');
            
            return $oc;
        })->filter(function($oc) {
            // Solo incluir OCs que tengan al menos una OT que cumpla los filtros
            return $oc->total_ots_filtradas > 0;
        });
    }
}
