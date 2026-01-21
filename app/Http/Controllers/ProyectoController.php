<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Item;
use App\Models\MontoConfiguracion;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    /**
     * Mostrar lista de proyectos en configuración
     */
    public function index(Request $request)
    {
        $itemFiltro = $request->get('item_id');
        $editId = $request->get('edit');
        
        // Filtro por año (por defecto 2025 si no hay proyectos de 2026, guardar en sesión)
        $anioPorDefecto = Proyecto::where('anio_ejecucion', date('Y'))->exists() ? date('Y') : 2025;
        $anioFiltro = $request->get('anio', session('proyectos_anio_filtro', $anioPorDefecto));
        session(['proyectos_anio_filtro' => $anioFiltro]);
        
        // Obtener años disponibles desde proyectos (anio_ejecucion)
        $añosDisponibles = Proyecto::selectRaw('anio_ejecucion as año')
            ->whereNotNull('anio_ejecucion')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año');
        
        // Si no hay años, agregar 2025 como mínimo
        if ($añosDisponibles->isEmpty()) {
            $añosDisponibles = collect([2025]);
        }
        
        // Obtener todos los items para el filtro
        $items = Item::orderBy('nombre')->get();
        
        // Ordenar items según orden específico:
        // 1. Convenio de Mantención
        // 2. Convenio de Suministro
        // 3. Compra Ágil
        // 4. Subtítulo 31
        // 5. Emergencia
        // Resto alfabético
        $items = $items->sortBy(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            // Normalizar acentos
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            
            // Orden específico
            if (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'mantencion') !== false) {
                return '01_mantencion';
            }
            elseif (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'suministro') !== false) {
                return '02_suministro';
            }
            elseif (strpos($nombreNormalizado, 'compra') !== false && strpos($nombreNormalizado, 'agil') !== false) {
                return '03_compra_agil';
            }
            elseif (strpos($nombreNormalizado, 'subtitulo') !== false && strpos($nombreNormalizado, '31') !== false) {
                return '04_subtitulo_31';
            }
            elseif (strpos($nombreNormalizado, 'emergencia') !== false) {
                return '05_emergencia';
            }
            elseif (strpos($nombreNormalizado, 'contingencia') !== false) {
                return '06_contingencia';
            }
            // Resto en orden alfabético normal
            else {
                return '99_' . $nombreNormalizado;
            }
        })->values();
        
        // Query base
        $query = Proyecto::with('item');
        
        // Aplicar filtro por año si existe
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $query->where('anio_ejecucion', $anioFiltro);
        }
        
        // Aplicar filtro por item si existe
        if ($itemFiltro) {
            $query->where('item_id', $itemFiltro);
        }
        
        $proyectos = $query->orderBy('nombre')->get();
        $proyectosPorItem = $proyectos->groupBy('item_id');
        $proyectoEdit = null;
        if ($editId) {
            $proyectoEdit = Proyecto::find($editId);
        }
        
        // Calcular saldo de "Mantención" (encabezado principal)
        // Presupuesto considerado: Subvención Mantenimiento + Convenio de Suministro + Mantención Jardines y Salas Cuna VTF
        $montoTotalMantencion = MontoConfiguracion::whereIn('codigo', [
            'subvencion_mantenimiento',
            'subvencion_general',
            'mantencion_vtf',
        ])->sum('monto');

        // Items cuyos proyectos descuentan del saldo de Mantención
        $itemsMantencion = Item::get()->filter(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            $esConvenioMantencion = (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'mantencion') !== false);
            $esConvenioSuministro = (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'suministro') !== false);
            $esMantencionVTF = (strpos($nombreNormalizado, 'vtf') !== false) ||
                               (strpos($nombreNormalizado, 'jardines') !== false && strpos($nombreNormalizado, 'mantencion') !== false);
            return $esConvenioMantencion || $esConvenioSuministro || $esMantencionVTF;
        })->pluck('id');

        // Monto asignado = suma de monto_asignado solo de proyectos de esos 3 items (aplicar filtro de año)
        $queryMontoAsignado = Proyecto::whereIn('item_id', $itemsMantencion);
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryMontoAsignado->where('anio_ejecucion', $anioFiltro);
        }
        $montoAsignado = $queryMontoAsignado->sum('monto_asignado') ?? 0;

        // Saldo disponible mostrado en el encabezado "Mantención"
        $saldoDisponible = $montoTotalMantencion - $montoAsignado;
        
        // Calcular saldos individuales para Subtítulo 31, Emergencia y Contingencia
        // Usar el monto específico del item en el presupuesto (MontoConfiguracion)
        $itemSubtitulo31 = Item::with('montosConfiguracion')->get()->first(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'subtitulo') !== false && strpos($nombreNormalizado, '31') !== false;
        });
        
        $itemEmergencia = Item::with('montosConfiguracion')->get()->first(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'emergencia') !== false;
        });
        
        $itemContingencia = Item::with('montosConfiguracion')->get()->first(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'contingencia') !== false;
        });
        
        $saldoSubtitulo31 = null;
        $saldoEmergencia = null;
        $saldoContingencia = null;
        
        if ($itemSubtitulo31) {
            // Obtener monto del presupuesto para este item (suma de montos asociados)
            $montoPresupuestoSubtitulo31 = $itemSubtitulo31->montosConfiguracion->sum('monto') ?? 0;
            $querySubtitulo31 = Proyecto::where('item_id', $itemSubtitulo31->id);
            if ($anioFiltro && $anioFiltro !== 'todos') {
                $querySubtitulo31->where('anio_ejecucion', $anioFiltro);
            }
            $montoAsignadoSubtitulo31 = $querySubtitulo31->sum('monto_asignado') ?? 0;
            $saldoSubtitulo31 = $montoPresupuestoSubtitulo31 - $montoAsignadoSubtitulo31;
        }
        
        if ($itemEmergencia) {
            // Obtener monto del presupuesto para este item (suma de montos asociados)
            $montoPresupuestoEmergencia = $itemEmergencia->montosConfiguracion->sum('monto') ?? 0;
            $queryEmergencia = Proyecto::where('item_id', $itemEmergencia->id);
            if ($anioFiltro && $anioFiltro !== 'todos') {
                $queryEmergencia->where('anio_ejecucion', $anioFiltro);
            }
            $montoAsignadoEmergencia = $queryEmergencia->sum('monto_asignado') ?? 0;
            $saldoEmergencia = $montoPresupuestoEmergencia - $montoAsignadoEmergencia;
        }
        
        if ($itemContingencia) {
            // Obtener monto del presupuesto para este item (suma de montos asociados)
            $montoPresupuestoContingencia = $itemContingencia->montosConfiguracion->sum('monto') ?? 0;
            $queryContingencia = Proyecto::where('item_id', $itemContingencia->id);
            if ($anioFiltro && $anioFiltro !== 'todos') {
                $queryContingencia->where('anio_ejecucion', $anioFiltro);
            }
            $montoAsignadoContingencia = $queryContingencia->sum('monto_asignado') ?? 0;
            $saldoContingencia = $montoPresupuestoContingencia - $montoAsignadoContingencia;
        }
        
        // Contar proyectos (aplicar filtro de año)
        $queryTotalProyectos = Proyecto::query();
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryTotalProyectos->where('anio_ejecucion', $anioFiltro);
        }
        $totalProyectos = $queryTotalProyectos->count();
        
        // Retornar la vista principal de configuración pasando todas las variables necesarias
        $usuarios = \App\Models\Usuario::select('id', 'nombre', 'email', 'rol', 'cargo', 'estado', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        $montos = \App\Models\MontoConfiguracion::orderBy('id')->get();
        $proveedores = \App\Models\Oferente::orderBy('nombre')->get();
        
        // Variables de establecimientos (inicializadas como vacías)
        $establecimientos = collect();
        $comunas = collect();
        $montoSubvencionMant = null;
        $montoSubvencionGeneral = null;
        $montoVTF = null;
        $totalMantenimientoRegulares = 0;
        $establecimientosVTF = 0;
        $sumaMontosRegulares = 0;
        $diferenciaMontos = null;
        $hayDiferencia = false;
        
        return view('configuracion.index', compact(
            'proyectos',
            'proyectosPorItem',
            'items',
            'itemFiltro',
            'anioFiltro',
            'añosDisponibles',
            'montoTotalMantencion',
            'montoAsignado',
            'saldoDisponible',
            'saldoSubtitulo31',
            'saldoEmergencia',
            'saldoContingencia',
            'itemContingencia',
            'totalProyectos',
            'usuarios',
            'montos',
            'proveedores',
            'establecimientos',
            'comunas',
            'montoSubvencionMant',
            'montoSubvencionGeneral',
            'montoVTF',
            'totalMantenimientoRegulares',
            'establecimientosVTF',
            'sumaMontosRegulares',
            'diferenciaMontos',
            'hayDiferencia',
            'proyectoEdit'
        ));
    }

    /**
     * Crear nuevo proyecto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'estado' => 'required|string|max:20',
            'codigo_idi' => 'nullable|string|max:50',
            'monto_asignado' => 'required|numeric|min:0',
            'item_id' => 'required|exists:items,id',
            'anio_ejecucion' => 'required|integer|min:2020|max:2030'
        ]);

        Proyecto::create($validated);

        return redirect()->route('configuracion.index', ['tab' => 'proyectos'])
            ->with('success', 'Proyecto creado exitosamente');
    }

    /**
     * Actualizar proyecto
     */
    public function update(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'estado' => 'required|string|max:20',
            'codigo_idi' => 'nullable|string|max:50',
            'monto_asignado' => 'required|numeric|min:0',
            'item_id' => 'required|exists:items,id',
            'anio_ejecucion' => 'required|integer|min:2020|max:2030'
        ]);

        $proyecto->update($validated);

        return redirect()->route('configuracion.index', ['tab' => 'proyectos'])
            ->with('success', 'Proyecto actualizado exitosamente');
    }

    /**
     * Obtener proyecto para edición (AJAX)
     */
    public function show($id)
    {
        try {
            $proyecto = Proyecto::with('item')->findOrFail($id);
            return response()->json([
                'success' => true,
                'proyecto' => [
                    'id' => $proyecto->id,
                    'nombre' => $proyecto->nombre,
                    'estado' => $proyecto->estado,
                    'codigo_idi' => $proyecto->codigo_idi,
                    'monto_asignado' => $proyecto->monto_asignado,
                    'item_id' => $proyecto->item_id,
                    'anio_ejecucion' => $proyecto->anio_ejecucion,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el proyecto: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Eliminar proyecto
     */
    public function destroy($id)
    {
        try {
            $proyecto = Proyecto::findOrFail($id);
            $nombre = $proyecto->nombre;
            $proyecto->delete();

            return redirect()->route('configuracion.index', ['tab' => 'proyectos'])
                ->with('success', "Proyecto '{$nombre}' eliminado exitosamente");
        } catch (\Exception $e) {
            return redirect()->route('configuracion.index', ['tab' => 'proyectos'])
                ->with('error', 'Error al eliminar el proyecto: ' . $e->getMessage());
        }
    }
}