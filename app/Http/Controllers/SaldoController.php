<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Proyecto;
use App\Models\MontoConfiguracion;
use App\Models\Contrato;
use App\Models\OrdenCompra;
use App\Models\OrdenTrabajo;
use App\Models\Establecimiento;
use App\Models\Comuna;
use Illuminate\Http\Request;

class SaldoController extends Controller
{
    public function index(Request $request)
    {
        // Filtro por año (por defecto año actual, guardar en sesión)
        $anioFiltro = $request->get('anio', session('saldos_anio_filtro', date('Y')));
        session(['saldos_anio_filtro' => $anioFiltro]);
        
        // Obtener años disponibles desde contratos
        $añosDisponibles = Contrato::selectRaw('YEAR(fecha_inicio) as año')
            ->whereNotNull('fecha_inicio')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año')
            ->merge(
                Contrato::selectRaw('YEAR(fecha_oc) as año')
                    ->whereNotNull('fecha_oc')
                    ->distinct()
                    ->pluck('año')
            )
            ->merge(
                Contrato::selectRaw('YEAR(created_at) as año')
                    ->distinct()
                    ->pluck('año')
            )
            ->unique()
            ->sortDesc()
            ->values();
        
        // Si no hay años, agregar el año actual
        if ($añosDisponibles->isEmpty()) {
            $añosDisponibles = collect([date('Y')]);
        }
        
        $items = Item::with('montosConfiguracion')->get();
        
        // Construir conjunto Mantención: solo Convenio de Mantención, Convenio de Suministro y Compra Ágil
        $itemsMantencion = $items->filter(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            $esConvenioMantencion = (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'mantencion') !== false);
            $esConvenioSuministro = (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'suministro') !== false);
            $esCompraAgil = (strpos($nombreNormalizado, 'compra') !== false && strpos($nombreNormalizado, 'agil') !== false);
            return $esConvenioMantencion || $esConvenioSuministro || $esCompraAgil;
        })->sortBy(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            
            // Orden: 1. Convenio de Mantención, 2. Convenio de Suministro, 3. Compra Ágil
            if (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'mantencion') !== false) {
                return '01_mantencion';
            } elseif (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'suministro') !== false) {
                return '02_suministro';
            } elseif (strpos($nombreNormalizado, 'compra') !== false && strpos($nombreNormalizado, 'agil') !== false) {
                return '03_compra_agil';
            }
            return '99_otros';
        })->values();
        
        // Identificar items especiales que deben tener su propio cuadro
        $itemSubtitulo31 = $items->first(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'subtitulo') !== false && strpos($nombreNormalizado, '31') !== false;
        });
        
        $itemEmergencia = $items->first(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'emergencia') !== false;
        });
        
        $itemContingencia = $items->first(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'contingencia') !== false;
        });
        
        // Excluir items de Mantención y los especiales de los items dinámicos
        $idsEspeciales = collect([
            $itemSubtitulo31?->id,
            $itemEmergencia?->id,
            $itemContingencia?->id
        ])->filter();
        
        $itemsDinamicos = $items->reject(function($item) use ($itemsMantencion, $idsEspeciales) {
            return $itemsMantencion->contains('id', $item->id) || $idsEspeciales->contains($item->id);
        });
        
        $datosMantencion = $this->calcularDatosMantencion($itemsMantencion, $anioFiltro);
        
        // Calcular datos para items especiales
        $datosSubtitulo31 = $itemSubtitulo31 ? $this->calcularDatosItem($itemSubtitulo31, $anioFiltro) : null;
        $datosEmergencia = $itemEmergencia ? $this->calcularDatosItem($itemEmergencia, $anioFiltro) : null;
        $datosContingencia = $itemContingencia ? $this->calcularDatosItem($itemContingencia, $anioFiltro) : null;
        
        $datosItemsDinamicos = [];
        foreach ($itemsDinamicos as $item) {
            $datosItemsDinamicos[] = $this->calcularDatosItem($item, $anioFiltro);
        }
        
        // Obtener contratos agrupados por item (igual que en ContratoController)
        // Aplicar filtro por año si existe
        $queryContratos = Contrato::with(['proyecto.item']);
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryContratos->where(function($q) use ($anioFiltro) {
                $q->whereYear('fecha_inicio', $anioFiltro)
                  ->orWhereYear('fecha_oc', $anioFiltro)
                  ->orWhereYear('created_at', $anioFiltro);
            });
        }
        $contratos = $queryContratos->orderBy('created_at', 'desc')->get();
        
        // Agregar información de precios unitarios a cada contrato
        $contratos = $contratos->map(function($contrato) {
            try {
                $contrato->cantidad_precios = \App\Models\PrecioUnitario::where('contrato_id', $contrato->id)->count();
            } catch (\Exception $e) {
                $contrato->cantidad_precios = 0;
            }
            return $contrato;
        });
        
        // Agrupar contratos por item_id (a través del proyecto)
        $contratosPorItem = $contratos->groupBy(function($contrato) {
            return $contrato->proyecto->item_id ?? 0;
        });
        
        return view('saldos.index', compact(
            'datosMantencion', 
            'datosItemsDinamicos',
            'datosSubtitulo31',
            'datosEmergencia',
            'datosContingencia',
            'contratosPorItem',
            'itemsMantencion',
            'itemSubtitulo31',
            'itemEmergencia',
            'itemContingencia',
            'anioFiltro',
            'añosDisponibles'
        ));
    }
    
    private function calcularDatosMantencion($itemsMantencion, $anioFiltro = null)
    {
        // Presupuesto de Mantención: Subvención General + Subvención Mantenimiento + Mantención VTF
        $montoGeneral = MontoConfiguracion::where('codigo', 'subvencion_general')->first();
        $montoMantencion = MontoConfiguracion::where('codigo', 'subvencion_mantenimiento')->first();
        $montoVTF = MontoConfiguracion::where('codigo', 'mantencion_vtf')->first();
        
        $presupuesto = ($montoGeneral->monto ?? 0) + ($montoMantencion->monto ?? 0) + ($montoVTF->monto ?? 0);
        
        $itemIds = $itemsMantencion->pluck('id');
        
        // Contratado: suma de monto_real de contratos
        $queryContratado = Contrato::whereHas('proyecto', function($q) use ($itemIds) {
            $q->whereIn('item_id', $itemIds);
        });
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryContratado->where(function($q) use ($anioFiltro) {
                $q->whereYear('fecha_inicio', $anioFiltro)
                  ->orWhereYear('fecha_oc', $anioFiltro)
                  ->orWhereYear('created_at', $anioFiltro);
            });
        }
        $contratado = $queryContratado->sum('monto_real') ?? 0;
        
        // Comprometido: suma de montos de OTs sin OC asignada (orden_compra_id es null)
        $queryComprometido = OrdenTrabajo::whereHas('contrato.proyecto', function($q) use ($itemIds) {
            $q->whereIn('item_id', $itemIds);
        })->whereNull('orden_compra_id');
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryComprometido->whereHas('contrato', function($q) use ($anioFiltro) {
                $q->where(function($q2) use ($anioFiltro) {
                    $q2->whereYear('fecha_inicio', $anioFiltro)
                       ->orWhereYear('fecha_oc', $anioFiltro)
                       ->orWhereYear('created_at', $anioFiltro);
                });
            });
        }
        $comprometido = $queryComprometido->sum('monto') ?? 0;
        
        // Ejecutado: suma de montos de OTs con OC asignada (orden_compra_id no es null)
        $queryEjecutado = OrdenTrabajo::whereHas('contrato.proyecto', function($q) use ($itemIds) {
            $q->whereIn('item_id', $itemIds);
        })->whereNotNull('orden_compra_id');
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryEjecutado->whereHas('contrato', function($q) use ($anioFiltro) {
                $q->where(function($q2) use ($anioFiltro) {
                    $q2->whereYear('fecha_inicio', $anioFiltro)
                       ->orWhereYear('fecha_oc', $anioFiltro)
                       ->orWhereYear('created_at', $anioFiltro);
                });
            });
        }
        $ejecutado = $queryEjecutado->sum('monto') ?? 0;
        
        // Facturado: suma de monto_total de OCs con estado "Pagado"
        $queryFacturado = OrdenCompra::whereHas('contrato.proyecto', function($q) use ($itemIds) {
            $q->whereIn('item_id', $itemIds);
        })->where('estado', 'Pagado');
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryFacturado->whereHas('contrato', function($q) use ($anioFiltro) {
                $q->where(function($q2) use ($anioFiltro) {
                    $q2->whereYear('fecha_inicio', $anioFiltro)
                       ->orWhereYear('fecha_oc', $anioFiltro)
                       ->orWhereYear('created_at', $anioFiltro);
                });
            });
        }
        $facturado = $queryFacturado->sum('monto_total') ?? 0;
        
        // Saldo disponible: Presupuesto - (Comprometido + Ejecutado)
        $saldo = $presupuesto - ($comprometido + $ejecutado);
        
        return [
            'presupuesto' => $presupuesto,
            'contratado' => $contratado,
            'comprometido' => $comprometido,
            'ejecutado' => $ejecutado,
            'facturado' => $facturado,
            'saldo' => $saldo,
            'items' => $itemsMantencion
        ];
    }
    
    private function calcularDatosItem($item, $anioFiltro = null)
    {
        $presupuesto = $item->montosConfiguracion->sum('monto') ?? 0;
        
        // Contratado: suma de monto_real de contratos
        $queryContratado = Contrato::whereHas('proyecto', function($q) use ($item) {
            $q->where('item_id', $item->id);
        });
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryContratado->where(function($q) use ($anioFiltro) {
                $q->whereYear('fecha_inicio', $anioFiltro)
                  ->orWhereYear('fecha_oc', $anioFiltro)
                  ->orWhereYear('created_at', $anioFiltro);
            });
        }
        $contratado = $queryContratado->sum('monto_real') ?? 0;
        
        // Comprometido: suma de montos de OTs sin OC asignada (orden_compra_id es null)
        $queryComprometido = OrdenTrabajo::whereHas('contrato.proyecto', function($q) use ($item) {
            $q->where('item_id', $item->id);
        })->whereNull('orden_compra_id');
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryComprometido->whereHas('contrato', function($q) use ($anioFiltro) {
                $q->where(function($q2) use ($anioFiltro) {
                    $q2->whereYear('fecha_inicio', $anioFiltro)
                       ->orWhereYear('fecha_oc', $anioFiltro)
                       ->orWhereYear('created_at', $anioFiltro);
                });
            });
        }
        $comprometido = $queryComprometido->sum('monto') ?? 0;
        
        // Ejecutado: suma de montos de OTs con OC asignada (orden_compra_id no es null)
        $queryEjecutado = OrdenTrabajo::whereHas('contrato.proyecto', function($q) use ($item) {
            $q->where('item_id', $item->id);
        })->whereNotNull('orden_compra_id');
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryEjecutado->whereHas('contrato', function($q) use ($anioFiltro) {
                $q->where(function($q2) use ($anioFiltro) {
                    $q2->whereYear('fecha_inicio', $anioFiltro)
                       ->orWhereYear('fecha_oc', $anioFiltro)
                       ->orWhereYear('created_at', $anioFiltro);
                });
            });
        }
        $ejecutado = $queryEjecutado->sum('monto') ?? 0;
        
        // Facturado: suma de monto_total de OCs con estado "Pagado"
        $queryFacturado = OrdenCompra::whereHas('contrato.proyecto', function($q) use ($item) {
            $q->where('item_id', $item->id);
        })->where('estado', 'Pagado');
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryFacturado->whereHas('contrato', function($q) use ($anioFiltro) {
                $q->where(function($q2) use ($anioFiltro) {
                    $q2->whereYear('fecha_inicio', $anioFiltro)
                       ->orWhereYear('fecha_oc', $anioFiltro)
                       ->orWhereYear('created_at', $anioFiltro);
                });
            });
        }
        $facturado = $queryFacturado->sum('monto_total') ?? 0;
        
        // Saldo disponible: Presupuesto - (Comprometido + Ejecutado)
        $saldo = $presupuesto - ($comprometido + $ejecutado);
        
        return [
            'item' => $item,
            'presupuesto' => $presupuesto,
            'contratado' => $contratado,
            'comprometido' => $comprometido,
            'ejecutado' => $ejecutado,
            'facturado' => $facturado,
            'saldo' => $saldo
        ];
    }
    
    /**
     * Buscar comunas para autocompletado
     */
    public function buscarComunas(Request $request)
    {
        $termino = $request->get('q', '');
        
        $comunas = Comuna::where('nombre', 'like', '%' . $termino . '%')
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre']);
        
        return response()->json($comunas);
    }
    
    /**
     * Buscar establecimientos para autocompletado
     */
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
    
    /**
     * Obtener datos de saldos por comuna o establecimiento
     */
    public function obtenerSaldosDetalle(Request $request)
    {
        $comunaId = $request->get('comuna_id');
        $establecimientoId = $request->get('establecimiento_id');
        
        if ($establecimientoId) {
            // Mostrar solo el establecimiento seleccionado
            $establecimiento = Establecimiento::with('comuna')->findOrFail($establecimientoId);
            $datos = $this->calcularDatosEstablecimiento($establecimiento);
            
            return response()->json([
                'tipo' => 'establecimiento',
                'establecimiento' => [
                    'id' => $establecimiento->id,
                    'nombre' => $establecimiento->nombre,
                    'comuna' => $establecimiento->comuna ? $establecimiento->comuna->nombre : ''
                ],
                'datos' => [$datos]
            ]);
        } elseif ($comunaId) {
            // Mostrar todos los establecimientos de la comuna
            $establecimientos = Establecimiento::with('comuna')
                ->where('comuna_id', $comunaId)
                ->orderBy('nombre')
                ->get();
            
            $datos = [];
            foreach ($establecimientos as $establecimiento) {
                $datos[] = $this->calcularDatosEstablecimiento($establecimiento);
            }
            
            $comuna = Comuna::findOrFail($comunaId);
            
            return response()->json([
                'tipo' => 'comuna',
                'comuna' => [
                    'id' => $comuna->id,
                    'nombre' => $comuna->nombre
                ],
                'datos' => $datos
            ]);
        }
        
        return response()->json(['error' => 'Debe seleccionar una comuna o un establecimiento'], 400);
    }
    
    /**
     * Calcular datos de un establecimiento
     */
    private function calcularDatosEstablecimiento($establecimiento)
    {
        // Subv. Mantenimiento y Aporte desde el establecimiento
        $subvencionMantenimiento = $establecimiento->subvencion_mantenimiento ?? 0;
        $aporte = $establecimiento->aporte_subvencion_general ?? 0;
        
        // Comprometido: suma de montos de OTs sin OC asignada (orden_compra_id es null)
        $comprometido = OrdenTrabajo::where('establecimiento_id', $establecimiento->id)
            ->whereNull('orden_compra_id')
            ->sum('monto') ?? 0;
        
        // Ejecutado: suma de montos de OTs con OC asignada (orden_compra_id no es null)
        $ejecutado = OrdenTrabajo::where('establecimiento_id', $establecimiento->id)
            ->whereNotNull('orden_compra_id')
            ->sum('monto') ?? 0;
        
        // Saldo: (Subv. Mantenimiento + Aporte) - (Comprometido + Ejecutado)
        $saldo = ($subvencionMantenimiento + $aporte) - ($comprometido + $ejecutado);
        
        return [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_nombre' => $establecimiento->nombre,
            'comuna_nombre' => $establecimiento->comuna ? $establecimiento->comuna->nombre : '',
            'subvencion_mantenimiento' => $subvencionMantenimiento,
            'aporte' => $aporte,
            'comprometido' => $comprometido,
            'ejecutado' => $ejecutado,
            'saldo' => $saldo
        ];
    }
    
    /**
     * Obtener información detallada de un contrato
     */
    public function obtenerDetalleContrato($id)
    {
        $contrato = Contrato::with([
            'proyecto.item',
            'ordenesCompra.ordenesTrabajo' => function($query) {
                $query->with(['establecimiento.comuna', 'presupuestoOt']);
            },
            'ordenesTrabajo' => function($query) {
                $query->whereNull('orden_compra_id')
                      ->with(['establecimiento.comuna', 'presupuestoOt']);
            }
        ])->findOrFail($id);
        
        // Formatear datos del contrato
        $datosContrato = [
            'id' => $contrato->id,
            'nombre_contrato' => $contrato->nombre_contrato,
            'numero_contrato' => $contrato->numero_contrato,
            'proyecto' => $contrato->proyecto ? $contrato->proyecto->nombre : '-',
            'item' => $contrato->proyecto && $contrato->proyecto->item ? $contrato->proyecto->item->nombre : '-',
            'id_licitacion' => $contrato->id_licitacion,
            'monto_real' => $contrato->monto_real,
            'estado' => $contrato->estado,
            'fecha_inicio' => $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : '-',
            'fecha_fin' => $contrato->fecha_fin ? $contrato->fecha_fin->format('d/m/Y') : '-',
            'duracion_dias' => $contrato->duracion_dias,
            'proveedor' => $contrato->proveedor,
            'observaciones' => $contrato->observaciones,
            'orden_compra' => $contrato->orden_compra,
            'fecha_oc' => $contrato->fecha_oc ? $contrato->fecha_oc->format('d/m/Y') : '-',
            'archivo_contrato' => $contrato->archivo_contrato,
            'archivo_bases' => $contrato->archivo_bases,
            'archivo_oferta' => $contrato->archivo_oferta,
        ];
        
        // Formatear órdenes de compra con sus OTs
        $ordenesCompra = $contrato->ordenesCompra->map(function($oc) {
            return [
                'id' => $oc->id,
                'numero' => $oc->numero,
                'fecha' => $oc->fecha ? $oc->fecha->format('d/m/Y') : '-',
                'monto_total' => $oc->monto_total,
                'estado' => $oc->estado,
                'descripcion' => $oc->descripcion,
                'ordenes_trabajo' => $oc->ordenesTrabajo->map(function($ot) {
                    return [
                        'id' => $ot->id,
                        'numero_ot' => $ot->numero_ot,
                        'fecha_ot' => $ot->fecha_ot ? $ot->fecha_ot->format('d/m/Y') : '-',
                        'establecimiento' => $ot->establecimiento ? $ot->establecimiento->nombre : '-',
                        'comuna' => $ot->establecimiento && $ot->establecimiento->comuna ? $ot->establecimiento->comuna->nombre : '-',
                        'medida' => $ot->medida,
                        'monto' => $ot->monto,
                        'observacion' => $ot->observacion,
                        'tiene_presupuesto' => $ot->presupuestoOt ? true : false,
                    ];
                })
            ];
        });
        
        // Formatear OTs sin OC
        $otsSinOC = $contrato->ordenesTrabajo->map(function($ot) {
            return [
                'id' => $ot->id,
                'numero_ot' => $ot->numero_ot,
                'fecha_ot' => $ot->fecha_ot ? $ot->fecha_ot->format('d/m/Y') : '-',
                'establecimiento' => $ot->establecimiento ? $ot->establecimiento->nombre : '-',
                'comuna' => $ot->establecimiento && $ot->establecimiento->comuna ? $ot->establecimiento->comuna->nombre : '-',
                'medida' => $ot->medida,
                'monto' => $ot->monto,
                'observacion' => $ot->observacion,
                'tiene_presupuesto' => $ot->presupuestoOt ? true : false,
            ];
        });
        
        // Calcular saldos del contrato
        // Comprometido: suma de montos de OTs sin OC asignada (orden_compra_id es null)
        $comprometido = OrdenTrabajo::where('contrato_id', $contrato->id)
            ->whereNull('orden_compra_id')
            ->sum('monto') ?? 0;
        
        // Ejecutado: suma de montos de OTs con OC asignada (orden_compra_id no es null)
        $ejecutado = OrdenTrabajo::where('contrato_id', $contrato->id)
            ->whereNotNull('orden_compra_id')
            ->sum('monto') ?? 0;
        
        // Facturado: suma de monto_total de OCs con estado "Pagado"
        // Buscar OCs relacionadas con este contrato directamente o a través de las OTs
        $facturado = OrdenCompra::where('contrato_id', $contrato->id)
            ->where('estado', 'Pagado')
            ->sum('monto_total') ?? 0;
        
        // Saldo: Monto Real del Contrato - (Comprometido + Ejecutado)
        $saldo = ($contrato->monto_real ?? 0) - ($comprometido + $ejecutado);
        
        $saldosContrato = [
            'comprometido' => $comprometido,
            'ejecutado' => $ejecutado,
            'facturado' => $facturado,
            'saldo' => $saldo,
            'monto_contrato' => $contrato->monto_real ?? 0
        ];
        
        return response()->json([
            'contrato' => $datosContrato,
            'ordenes_compra' => $ordenesCompra,
            'ots_sin_oc' => $otsSinOC,
            'saldos' => $saldosContrato
        ]);
    }
}

