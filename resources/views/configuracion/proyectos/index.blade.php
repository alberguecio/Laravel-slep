@php
    // Inicializar variables si no existen (para evitar errores cuando no se está en esta pestaña)
    $proyectos = $proyectos ?? collect();
    $items = $items ?? collect();
    $itemFiltro = $itemFiltro ?? null;
    $anioFiltro = $anioFiltro ?? null;
    $añosDisponibles = $añosDisponibles ?? collect();
    $montoTotalMantencion = $montoTotalMantencion ?? 0;
    $montoAsignado = $montoAsignado ?? 0;
    $saldoDisponible = $saldoDisponible ?? 0;
    $saldoSubtitulo31 = $saldoSubtitulo31 ?? null;
    $saldoEmergencia = $saldoEmergencia ?? null;
    $saldoContingencia = $saldoContingencia ?? null;
    $itemContingencia = $itemContingencia ?? null;
    $totalProyectos = $totalProyectos ?? 0;
@endphp

<div class="container-fluid">
    <!-- Título -->
    <h4 class="mb-4">
        <i class="bi bi-folder"></i> Gestión de Proyectos
    </h4>

    <!-- Saldo Global Subvención -->
    <div class="card shadow mb-4">
        <div class="card-body bg-light">
            <h5 class="mb-3 fw-bold">Saldo Global Subvención</h5>
            <div class="row align-items-baseline">
                <div class="col-auto">
                    <h2 class="text-success mb-1 fw-bold" style="font-size: 2.5rem;">$ {{ number_format($saldoDisponible, 0, ',', '.') }}</h2>
                </div>
                <div class="col-auto ms-4">
                    <div class="text-muted" style="font-size: 0.9rem;">
                        <strong>Monto total:</strong> $ {{ number_format($montoTotalMantencion ?? 0, 0, ',', '.') }} | 
                        <strong>Asignado:</strong> $ {{ number_format($montoAsignado, 0, ',', '.') }} | 
                        <strong>Proyectos:</strong> {{ $totalProyectos }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Botón Agregar -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div class="d-flex gap-3">
            <div style="width: 200px;">
                <label for="filtroAnioProyectos" class="form-label fw-bold mb-2">
                    <i class="bi bi-calendar"></i> Año
                </label>
                <select class="form-select" id="filtroAnioProyectos" onchange="filtrarPorAnioProyectos(this.value)">
                    @php
                        // Crear lista de años disponibles
                        $añosList = collect();
                        
                        // Siempre incluir años fijos: 2024, 2025, 2026, 2027, 2028
                        for ($y = 2024; $y <= 2028; $y++) {
                            $añosList->push($y);
                        }
                        
                        // Agregar años desde proyectos existentes (por si hay años fuera del rango)
                        $añosProyectos = \App\Models\Proyecto::selectRaw('anio_ejecucion as año')
                            ->whereNotNull('anio_ejecucion')
                            ->distinct()
                            ->pluck('año');
                        
                        foreach ($añosProyectos as $año) {
                            if (!$añosList->contains($año)) {
                                $añosList->push($año);
                            }
                        }
                        
                        // Ordenar de forma descendente y eliminar duplicados
                        $añosList = $añosList->unique()->sortDesc()->values();
                        
                        $anioPorDefecto = $añosList->first() ?? 2025;
                        $anioSeleccionado = $anioFiltro ?? $anioPorDefecto;
                    @endphp
                    @foreach($añosList as $año)
                        <option value="{{ $año }}" {{ $anioSeleccionado == $año ? 'selected' : '' }}>
                            {{ $año }}@if($año == date('Y')) (Actual)@endif
                        </option>
                    @endforeach
                    <option value="todos" {{ $anioSeleccionado == 'todos' ? 'selected' : '' }}>Todos los años</option>
                </select>
            </div>
            <div style="width: 250px;">
                <label for="filtroItem" class="form-label fw-bold mb-2">Filtrar por Item</label>
                <select class="form-select" id="filtroItem" onchange="filtrarPorItem(this.value)">
                    <option value="">Todos los items</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                        {{ $item->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary" onclick="filtrarPorAnioProyectos('{{ date('Y') }}')" title="Volver a año actual">
                    <i class="bi bi-arrow-clockwise"></i> Año Actual
                </button>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProyecto">
                <i class="bi bi-plus-circle"></i> AGREGAR PROYECTO
            </button>
        </div>
    </div>

    <!-- Mantención -->
    <div class="card shadow">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Mantención</h5>
                <small class="text-muted">Saldo disponible: $ {{ number_format($saldoDisponible, 0, ',', '.') }}</small>
            </div>
        </div>
        <div class="card-body">
            @php
                $hayProyectos = false;
                $todosLosItems = $items;
                // Identificar especiales
                $itemSub31 = $todosLosItems->first(function ($i) {
                    $n = mb_strtolower($i->nombre ?? '');
                    $n = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $n);
                    return strpos($n, 'subtitulo') !== false && strpos($n, '31') !== false;
                });
                $itemEmerg = $todosLosItems->first(function ($i) {
                    $n = mb_strtolower($i->nombre ?? '');
                    $n = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $n);
                    return strpos($n, 'emergencia') !== false;
                });
                $itemCont = $todosLosItems->first(function ($i) {
                    $n = mb_strtolower($i->nombre ?? '');
                    $n = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $n);
                    return strpos($n, 'contingencia') !== false;
                });
                $idsEspeciales = collect([$itemSub31?->id, $itemEmerg?->id, $itemCont?->id])->filter();

                // Construir conjunto Mantención (excluye especiales)
                $itemsMantencion = $todosLosItems->reject(function ($i) use ($idsEspeciales) { return $idsEspeciales->contains($i->id); });

                // Aplicar filtro si corresponde
                if (!empty($itemFiltro)) {
                    if ($idsEspeciales->contains((int)$itemFiltro)) {
                        // Si filtra por un especial, no mostrar nada en Mantención
                        $itemsMantencion = collect();
                    } else {
                        $itemsMantencion = $itemsMantencion->where('id', (int)$itemFiltro);
                    }
                }
            @endphp

            @forelse($itemsMantencion as $itemLoop)
                @php
                    $lista = isset($proyectosPorItem) ? ($proyectosPorItem[$itemLoop->id] ?? collect()) : collect();
                @endphp
                @if($lista->count() > 0)
                    @php 
                        $hayProyectos = true;
                        $nombreNormalizadoItem = mb_strtolower($itemLoop->nombre ?? '');
                        $nombreNormalizadoItem = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombreNormalizadoItem);
                        $esSubtitulo31 = strpos($nombreNormalizadoItem, 'subtitulo') !== false && strpos($nombreNormalizadoItem, '31') !== false;
                        $esEmergencia = strpos($nombreNormalizadoItem, 'emergencia') !== false;
                        $saldoMostrar = null;
                        if ($esSubtitulo31 && isset($saldoSubtitulo31)) {
                            $saldoMostrar = $saldoSubtitulo31;
                        } elseif ($esEmergencia && isset($saldoEmergencia)) {
                            $saldoMostrar = $saldoEmergencia;
                        }
                    @endphp
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemLoop->nombre }}</h6>
                                @if($saldoMostrar !== null)
                                <small class="text-muted">Saldo disponible: $ {{ number_format($saldoMostrar, 0, ',', '.') }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-primary">
                                        <tr>
                                            <th class="ps-3">Nombre</th>
                                            <th>Item</th>
                                            <th>Código IDI</th>
                                            <th>Estado</th>
                                            <th class="text-end">Monto</th>
                                            <th class="text-end pe-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lista as $proyecto)
                                        <tr>
                                            <td class="ps-3">
                                                <span class="fw-medium">{{ $proyecto->nombre }}</span>
                                            </td>
                                            <td>{{ $proyecto->item->nombre ?? '-' }}</td>
                                            <td>{{ $proyecto->codigo_idi ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $proyecto->estado === 'Aprobado' ? 'success' : ($proyecto->estado === 'Formulación' ? 'warning' : ($proyecto->estado === 'Evaluación técnica' ? 'info' : ($proyecto->estado === 'Finalizado' ? 'primary' : 'secondary'))) }}">
                                                    {{ $proyecto->estado ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-end">$ {{ number_format($proyecto->monto_asignado ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end pe-3">
                                                <button class="btn btn-sm btn-outline-primary btn-editar-proyecto" 
                                                        data-id="{{ $proyecto->id }}"
                                                        onclick="editarProyectoDesdeBotón(this); return false;">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="eliminarProyecto({{ $proyecto->id }}, '{{ addslashes($proyecto->nombre) }}')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-secondary">
                                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                                            <td class="text-end fw-bold">$ {{ number_format($lista->sum('monto_asignado') ?? 0, 0, ',', '.') }}</td>
                                            <td class="pe-3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
            @endforelse

            @if(!$hayProyectos)
                <div class="text-center text-muted py-5">
                    No hay proyectos registrados. Crea uno nuevo para comenzar.
                </div>
            @endif
        </div>
    </div>

    @php
        // Renderizar tarjetas separadas para Subtítulo 31, Emergencia y Contingencia
        $debeMostrarSub31 = $itemSub31 && ($itemFiltro === null || (int)$itemFiltro === (int)$itemSub31->id);
        $debeMostrarEmerg = $itemEmerg && ($itemFiltro === null || (int)$itemFiltro === (int)$itemEmerg->id);
        $debeMostrarCont = $itemCont && ($itemFiltro === null || (int)$itemFiltro === (int)$itemCont->id);
    @endphp

    @if($debeMostrarSub31)
    <div class="card shadow-sm mb-3" style="margin-top: 1rem;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemSub31->nombre }}</h6>
                @if(isset($saldoSubtitulo31))
                <small class="text-muted">Saldo disponible: $ {{ number_format($saldoSubtitulo31, 0, ',', '.') }}</small>
                @endif
            </div>
        </div>
        @php $listaSub31 = isset($proyectosPorItem) ? ($proyectosPorItem[$itemSub31->id] ?? collect()) : collect(); @endphp
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Item</th>
                            <th>Código IDI</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listaSub31 as $proyecto)
                        <tr>
                            <td class="ps-3"><span class="fw-medium">{{ $proyecto->nombre }}</span></td>
                            <td>{{ $proyecto->item->nombre ?? '-' }}</td>
                            <td>{{ $proyecto->codigo_idi ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $proyecto->estado === 'Aprobado' ? 'success' : ($proyecto->estado === 'Formulación' ? 'warning' : ($proyecto->estado === 'Evaluación técnica' ? 'info' : ($proyecto->estado === 'Finalizado' ? 'primary' : 'secondary'))) }}">
                                    {{ $proyecto->estado ?? '-' }}
                                </span>
                            </td>
                            <td class="text-end">$ {{ number_format($proyecto->monto_asignado ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary btn-editar-proyecto" data-id="{{ $proyecto->id }}" onclick="editarProyectoDesdeBotón(this); return false;"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarProyecto({{ $proyecto->id }}, '{{ addslashes($proyecto->nombre) }}')"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                            <td class="text-end fw-bold">$ {{ number_format($listaSub31->sum('monto_asignado') ?? 0, 0, ',', '.') }}</td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($debeMostrarEmerg)
    <div class="card shadow-sm mb-3" style="margin-top: 0;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemEmerg->nombre }}</h6>
                @if(isset($saldoEmergencia))
                <small class="text-muted">Saldo disponible: $ {{ number_format($saldoEmergencia, 0, ',', '.') }}</small>
                @endif
            </div>
        </div>
        @php $listaEmerg = isset($proyectosPorItem) ? ($proyectosPorItem[$itemEmerg->id] ?? collect()) : collect(); @endphp
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Item</th>
                            <th>Código IDI</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listaEmerg as $proyecto)
                        <tr>
                            <td class="ps-3"><span class="fw-medium">{{ $proyecto->nombre }}</span></td>
                            <td>{{ $proyecto->item->nombre ?? '-' }}</td>
                            <td>{{ $proyecto->codigo_idi ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $proyecto->estado === 'Aprobado' ? 'success' : ($proyecto->estado === 'Formulación' ? 'warning' : ($proyecto->estado === 'Evaluación técnica' ? 'info' : ($proyecto->estado === 'Finalizado' ? 'primary' : 'secondary'))) }}">
                                    {{ $proyecto->estado ?? '-' }}
                                </span>
                            </td>
                            <td class="text-end">$ {{ number_format($proyecto->monto_asignado ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary btn-editar-proyecto" data-id="{{ $proyecto->id }}" onclick="editarProyectoDesdeBotón(this); return false;"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarProyecto({{ $proyecto->id }}, '{{ addslashes($proyecto->nombre) }}')"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                            <td class="text-end fw-bold">$ {{ number_format($listaEmerg->sum('monto_asignado') ?? 0, 0, ',', '.') }}</td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($debeMostrarCont)
    <div class="card shadow-sm mb-3" style="margin-top: 0;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemCont->nombre }}</h6>
                @if(isset($saldoContingencia))
                <small class="text-muted">Saldo disponible: $ {{ number_format($saldoContingencia, 0, ',', '.') }}</small>
                @endif
            </div>
        </div>
        @php $listaCont = isset($proyectosPorItem) ? ($proyectosPorItem[$itemCont->id] ?? collect()) : collect(); @endphp
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Item</th>
                            <th>Código IDI</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listaCont as $proyecto)
                        <tr>
                            <td class="ps-3"><span class="fw-medium">{{ $proyecto->nombre }}</span></td>
                            <td>{{ $proyecto->item->nombre ?? '-' }}</td>
                            <td>{{ $proyecto->codigo_idi ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $proyecto->estado === 'Aprobado' ? 'success' : ($proyecto->estado === 'Formulación' ? 'warning' : ($proyecto->estado === 'Evaluación técnica' ? 'info' : ($proyecto->estado === 'Finalizado' ? 'primary' : 'secondary'))) }}">
                                    {{ $proyecto->estado ?? '-' }}
                                </span>
                            </td>
                            <td class="text-end">$ {{ number_format($proyecto->monto_asignado ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary btn-editar-proyecto" data-id="{{ $proyecto->id }}" onclick="editarProyectoDesdeBotón(this); return false;"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarProyecto({{ $proyecto->id }}, '{{ addslashes($proyecto->nombre) }}')"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                            <td class="text-end fw-bold">$ {{ number_format($listaCont->sum('monto_asignado') ?? 0, 0, ',', '.') }}</td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif


<!-- Modal Crear/Editar Proyecto -->
<div class="modal fade" id="modalCrearProyecto" tabindex="-1" aria-labelledby="modalCrearProyectoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalCrearProyectoLabel">{{ isset($proyectoEdit) && $proyectoEdit ? 'Editar Proyecto' : 'Agregar Proyecto' }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formProyecto" method="POST" action="{{ isset($proyectoEdit) && $proyectoEdit ? route('proyectos.update', $proyectoEdit->id) : route('proyectos.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodFieldProyecto" value="{{ isset($proyectoEdit) && $proyectoEdit ? 'PUT' : 'POST' }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label fw-bold">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="{{ isset($proyectoEdit) && $proyectoEdit ? $proyectoEdit->nombre : '' }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estado" class="form-label fw-bold">Estado *</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Formulación" {{ (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->estado==='Formulación') ? 'selected' : '' }}>Formulación</option>
                                    <option value="Evaluación técnica" {{ (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->estado==='Evaluación técnica') ? 'selected' : '' }}>Evaluación técnica</option>
                                    <option value="Aprobado" {{ (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->estado==='Aprobado') ? 'selected' : '' }}>Aprobado</option>
                                    <option value="Finalizado" {{ (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->estado==='Finalizado') ? 'selected' : '' }}>Finalizado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_id" class="form-label fw-bold">Modalidad de Gasto *</label>
                                <select class="form-select" id="item_id" name="item_id" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}" {{ (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->item_id==$item->id) ? 'selected' : '' }}>{{ $item->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="codigo_idi" class="form-label">Código IDI (opcional)</label>
                                <input type="text" class="form-control" id="codigo_idi" name="codigo_idi" value="{{ isset($proyectoEdit) && $proyectoEdit ? $proyectoEdit->codigo_idi : '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="anio_ejecucion" class="form-label fw-bold">Año Ejecución *</label>
                                <select class="form-select" id="anio_ejecucion" name="anio_ejecucion" required>
                                    @php
                                        // Crear lista de años disponibles
                                        $añosDisponiblesForm = collect();
                                        
                                        // Siempre incluir años fijos: 2024, 2025, 2026, 2027, 2028
                                        for ($y = 2024; $y <= 2028; $y++) {
                                            $añosDisponiblesForm->push($y);
                                        }
                                        
                                        // Agregar años desde proyectos existentes (por si hay años fuera del rango)
                                        $añosProyectos = \App\Models\Proyecto::selectRaw('anio_ejecucion as año')
                                            ->whereNotNull('anio_ejecucion')
                                            ->distinct()
                                            ->pluck('año');
                                        
                                        foreach ($añosProyectos as $año) {
                                            if (!$añosDisponiblesForm->contains($año)) {
                                                $añosDisponiblesForm->push($año);
                                            }
                                        }
                                        
                                        // Si estamos editando un proyecto, asegurar que su año esté incluido
                                        if (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->anio_ejecucion) {
                                            if (!$añosDisponiblesForm->contains($proyectoEdit->anio_ejecucion)) {
                                                $añosDisponiblesForm->push($proyectoEdit->anio_ejecucion);
                                            }
                                        }
                                        
                                        // Ordenar de forma descendente y eliminar duplicados
                                        $añosDisponiblesForm = $añosDisponiblesForm->unique()->sortDesc()->values();
                                        
                                        // Determinar año por defecto
                                        $añoPorDefecto = 2025;
                                        if (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->anio_ejecucion) {
                                            $añoPorDefecto = $proyectoEdit->anio_ejecucion;
                                        } elseif ($añosDisponiblesForm->contains(2026)) {
                                            $añoPorDefecto = 2026;
                                        }
                                    @endphp
                                    @foreach($añosDisponiblesForm as $year)
                                        <option value="{{ $year }}" {{ (isset($proyectoEdit) && $proyectoEdit && $proyectoEdit->anio_ejecucion == $year) ? 'selected' : (!isset($proyectoEdit) && $year == $añoPorDefecto ? 'selected' : '') }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="monto_asignado" class="form-label fw-bold">Monto *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control text-end" id="monto_asignado" name="monto_asignado" step="0.01" min="0" value="{{ isset($proyectoEdit) && $proyectoEdit ? (0 + $proyectoEdit->monto_asignado) : '' }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary">{{ isset($proyectoEdit) && $proyectoEdit ? 'GUARDAR' : 'AGREGAR' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-abrir el modal si venimos con ?edit=ID
(function() {
    const url = new URL(window.location.href);
    const editId = url.searchParams.get('edit');
    const activeTab = new URLSearchParams(window.location.search).get('tab') || 'proveedores';
    if (activeTab === 'proyectos' && editId) {
        const modalEl = document.getElementById('modalCrearProyecto');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
})();
function filtrarPorAnioProyectos(anio) {
    const url = new URL(window.location.href);
    if (anio === 'todos') {
        url.searchParams.set('anio', 'todos');
    } else if (anio) {
        // Siempre establecer el parámetro anio, incluso si es el año actual
        url.searchParams.set('anio', anio);
    } else {
        url.searchParams.delete('anio');
    }
    // Mantener filtro de item si existe
    const itemId = document.getElementById('filtroItem')?.value;
    if (itemId) {
        url.searchParams.set('item_id', itemId);
    }
    window.location.href = url.toString();
}

function filtrarPorItem(itemId) {
    const url = new URL(window.location.href);
    // Mantener filtro de año si existe
    const anio = document.getElementById('filtroAnioProyectos')?.value;
    if (anio && anio !== '{{ date('Y') }}' && anio !== 'todos') {
        url.searchParams.set('anio', anio);
    } else if (anio === 'todos') {
        url.searchParams.set('anio', 'todos');
    }
    if (itemId) {
        url.searchParams.set('item_id', itemId);
    } else {
        url.searchParams.delete('item_id');
    }
    window.location.href = url.toString();
}

// Variable global para rastrear botones en estado de carga
const botonesEnCarga = new Map();

// Función para obtener datos desde el servidor via AJAX
function editarProyectoDesdeBotón(button) {
    const id = button.getAttribute('data-id');
    
    if (!id) {
        console.error('❌ No se encontró el ID del proyecto');
        return;
    }
    
    console.log('📡 Cargando datos del proyecto ID:', id);
    
    // Guardar referencia original del botón
    const originalHTML = button.innerHTML;
    const originalDisabled = button.disabled;
    
    // Mostrar indicador de carga
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    
    // Timeout de seguridad: restaurar después de 30 segundos si no se restauró antes
    const seguridadTimeout = setTimeout(() => {
        if (botonesEnCarga.has(button)) {
            console.warn('⚠️ Timeout de seguridad: restaurando botón que quedó en carga');
            const botonData = botonesEnCarga.get(button);
            if (botonData && botonData.restaurarBoton) {
                botonData.restaurarBoton();
            }
        }
    }, 30000);
    
    // Función para restaurar el botón
    const restaurarBoton = function() {
        try {
            // Limpiar timeout de seguridad si existe
            const botonData = botonesEnCarga.get(button);
            if (botonData && botonData.seguridadTimeout) {
                clearTimeout(botonData.seguridadTimeout);
            }
            
            button.disabled = originalDisabled;
            button.innerHTML = originalHTML;
            // Remover del mapa cuando se restaura
            botonesEnCarga.delete(button);
        } catch(e) {
            console.error('Error al restaurar botón:', e);
            // Intentar remover del mapa de todas formas
            botonesEnCarga.delete(button);
        }
    };
    
    // Registrar este botón en el mapa global con toda la información necesaria
    botonesEnCarga.set(button, { 
        originalHTML, 
        originalDisabled, 
        seguridadTimeout,
        restaurarBoton 
    });
    
    // Cargar datos del proyecto desde el servidor con timeout
    const timeoutId = setTimeout(function() {
        console.error('❌ Timeout: La petición tardó demasiado');
        restaurarBoton();
        alert('La petición tardó demasiado tiempo. Por favor, intenta de nuevo.');
    }, 10000); // 10 segundos de timeout
    
    fetch(`/configuracion/proyectos/${id}`, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            clearTimeout(timeoutId);
            console.log('📡 Respuesta recibida, status:', response.status);
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            // Verificar que la respuesta sea JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta del servidor no es JSON válido');
            }
            
            return response.json().catch(err => {
                throw new Error('Error al parsear la respuesta JSON: ' + err.message);
            });
        })
        .then(data => {
            clearTimeout(timeoutId);
            console.log('📦 Datos recibidos:', data);
            
            if (data.success && data.proyecto) {
                console.log('✅ Datos del proyecto cargados desde BD:', data.proyecto);
                console.log('✅ Nombre del proyecto:', data.proyecto.nombre);
                
                // Restaurar el botón ANTES de llamar a editarProyecto
                restaurarBoton();
                
                // Llamar a la función de edición con los datos obtenidos dentro de un try-catch
                try {
                    editarProyecto(
                        data.proyecto.id,
                        data.proyecto.nombre || '',
                        data.proyecto.estado || '',
                        data.proyecto.codigo_idi || '',
                        parseFloat(data.proyecto.monto_asignado) || 0,
                        data.proyecto.item_id || ''
                    );
                } catch(e) {
                    console.error('❌ Error al ejecutar editarProyecto:', e);
                    alert('Error al preparar el formulario de edición: ' + e.message);
                    // Asegurarse de restaurar el botón en caso de error
                    restaurarBoton();
                }
            } else {
                restaurarBoton();
                console.error('❌ Error al cargar proyecto:', data.message);
                alert('Error al cargar los datos del proyecto: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            restaurarBoton();
            console.error('❌ Error en la petición:', error);
            console.error('❌ Stack:', error.stack);
            alert('Error al cargar los datos del proyecto: ' + error.message);
        });
}

function editarProyecto(id, nombre, estado, codigoIdi, monto, itemId) {
    console.log('📝 Editar proyecto - Datos recibidos:', {id, nombre, estado, codigoIdi, monto, itemId});
    
    // Guardar los valores globalmente para establecerlos después de que el modal se muestre
    datosProyectoEdicion = {
        nombre: nombre || '',
        estado: estado || '',
        codigoIdi: codigoIdi || '',
        monto: monto || 0,
        itemId: (itemId && itemId !== 'null' && itemId !== null && itemId !== '' && itemId !== 'undefined') ? itemId : ''
    };
    
    console.log('💾 datosProyectoEdicion guardado:', datosProyectoEdicion);
    
    // Actualizar modal label y action ANTES de mostrar
    document.getElementById('modalCrearProyectoLabel').textContent = 'Editar Proyecto';
    document.getElementById('formProyecto').action = '/configuracion/proyectos/' + id;
    
    // CRÍTICO: Eliminar TODOS los campos _method duplicados y crear uno nuevo limpio
    const form = document.getElementById('formProyecto');
    const allMethodFields = form.querySelectorAll('input[name="_method"]');
    allMethodFields.forEach(field => field.remove());
    
    // Crear UN SOLO campo _method con valor PUT
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.id = 'methodFieldProyecto';
    methodField.value = 'PUT';
    methodField.setAttribute('value', 'PUT');
    
    // Insertar justo después del CSRF token
    const csrfField = form.querySelector('input[name="_token"]');
    if (csrfField && csrfField.parentNode) {
        csrfField.parentNode.insertBefore(methodField, csrfField.nextSibling);
    } else {
        form.insertBefore(methodField, form.firstChild);
    }
    
    // Actualizar botón
    form.querySelector('button[type="submit"]').textContent = 'GUARDAR';
    
    // Obtener referencia al modal
    const modalElement = document.getElementById('modalCrearProyecto');
    
    // Remover cualquier listener anterior de shown.bs.modal
    if (modalResetHandler) {
        modalElement.removeEventListener('shown.bs.modal', modalResetHandler);
    }
    
    // Establecer valores DIRECTAMENTE en los campos ANTES de mostrar el modal (scoped al modal)
    const nombreField = modalElement.querySelector('#nombre');
    const estadoField = modalElement.querySelector('#estado');
    const codigoIdiField = modalElement.querySelector('#codigo_idi');
    const montoField = modalElement.querySelector('#monto_asignado');
    const itemField = modalElement.querySelector('#item_id');
    
    console.log('🔧 Estableciendo valores ANTES de mostrar modal...');
    console.log('🔍 Valor de nombre a establecer:', datosProyectoEdicion.nombre);
    console.log('🔍 Tipo de valor nombre:', typeof datosProyectoEdicion.nombre);
    console.log('🔍 Nombre es string?:', typeof datosProyectoEdicion.nombre === 'string');
    
    if (nombreField) {
        // El nombre viene directamente de la BD via AJAX, así que debería estar correcto
        const nombreValue = String(datosProyectoEdicion.nombre || '').trim();
        
        console.log('🔍 Campo nombre encontrado');
        console.log('🔍 Valor de nombreValue:', nombreValue);
        console.log('🔍 Longitud:', nombreValue.length);
        
        // Establecer el valor de forma simple y directa
        nombreField.value = nombreValue;
        nombreField.defaultValue = nombreValue;
        
        console.log('✅ Nombre establecido - value:', nombreField.value);
        console.log('✅ Nombre establecido - defaultValue:', nombreField.defaultValue);
        
        // Verificar inmediatamente
        if (nombreField.value !== nombreValue) {
            console.error('❌ PROBLEMA: El valor no se estableció correctamente');
            console.error('❌ Esperado:', nombreValue);
            console.error('❌ Obtenido:', nombreField.value);
            // Intentar de nuevo
            nombreField.value = nombreValue;
        }
        
        // Verificar que no esté vacío
        if (!nombreField.value || nombreField.value === '') {
            console.error('❌ PROBLEMA CRÍTICO: El nombre está vacío después de establecerlo');
            console.error('❌ datosProyectoEdicion completo:', datosProyectoEdicion);
        } else {
            console.log('✅ NOMBRE ESTABLECIDO CORRECTAMENTE:', nombreField.value);
        }
    } else {
        console.error('❌ Campo nombre NO encontrado en el DOM');
    }
    
    if (estadoField) {
        estadoField.value = datosProyectoEdicion.estado;
        console.log('✅ Estado establecido antes de mostrar:', estadoField.value);
    }
    
    if (codigoIdiField) {
        codigoIdiField.value = datosProyectoEdicion.codigoIdi || '';
        codigoIdiField.setAttribute('value', datosProyectoEdicion.codigoIdi || '');
        console.log('✅ Código IDI establecido antes de mostrar:', codigoIdiField.value);
    }
    
    if (montoField) {
        montoField.value = datosProyectoEdicion.monto;
        montoField.setAttribute('value', datosProyectoEdicion.monto);
        console.log('✅ Monto establecido antes de mostrar:', montoField.value);
    }
    
    if (itemField) {
        itemField.value = datosProyectoEdicion.itemId;
        console.log('✅ Item ID establecido antes de mostrar:', itemField.value);
    }
    
    // Marcar como editando
    modalElement.dataset.editing = 'true';
    
    // Función para RE-establecer los valores cuando el modal esté visible (por si acaso)
    modalResetHandler = function() {
        console.log('👁️ Modal visible, verificando valores...');
        console.log('📦 datosProyectoEdicion.nombre:', datosProyectoEdicion.nombre);
        
        // Guardar referencia global para el intervalo
        let nombreIntervalId = null;
        let intentos = 0;
        const maxIntentos = 20; // Intentar 20 veces
        
        // Re-establecer valores por si algo los limpió
        setTimeout(function() {
            const nombreFieldCheck = modalElement.querySelector('#nombre');
            console.log('🔍 Campo nombre encontrado:', !!nombreFieldCheck);
            if (nombreFieldCheck) {
                console.log('🔍 Valor actual del nombre:', nombreFieldCheck.value);
                console.log('🔍 Valor esperado:', datosProyectoEdicion.nombre);
            }
            
            // SIEMPRE re-establecer el nombre, incluso si parece tener un valor
            if (nombreFieldCheck && datosProyectoEdicion.nombre) {
                console.log('⚠️ Iniciando establecimiento agresivo del nombre...');
                
                const nombreToSet = datosProyectoEdicion.nombre;
                
                // Función para establecer el nombre
                function establecerNombre() {
                    intentos++;
                    nombreFieldCheck.value = nombreToSet;
                    nombreFieldCheck.defaultValue = nombreToSet;
                    nombreFieldCheck.setAttribute('value', nombreToSet);
                    nombreFieldCheck.dataset.editingValue = nombreToSet;
                    
                    // Verificar si se estableció correctamente
                    if (nombreFieldCheck.value === nombreToSet) {
                        console.log('✅ Nombre establecido correctamente después de', intentos, 'intentos');
                        if (nombreIntervalId) {
                            clearInterval(nombreIntervalId);
                            nombreIntervalId = null;
                        }
                        return true;
                    }
                    
                    // Si no se estableció y aún quedan intentos, continuar
                    if (intentos >= maxIntentos) {
                        console.error('❌ No se pudo establecer el nombre después de', maxIntentos, 'intentos');
                        console.error('❌ Valor actual:', nombreFieldCheck.value);
                        console.error('❌ Valor esperado:', nombreToSet);
                        if (nombreIntervalId) {
                            clearInterval(nombreIntervalId);
                            nombreIntervalId = null;
                        }
                        return false;
                    }
                    
                    return false;
                }
                
                // Establecer inmediatamente
                establecerNombre();
                
                // Si no funcionó, establecer interval para intentar repetidamente
                if (nombreFieldCheck.value !== nombreToSet) {
                    nombreIntervalId = setInterval(function() {
                        if (establecerNombre()) {
                            // Se estableció correctamente, limpiar
                            clearInterval(nombreIntervalId);
                            nombreIntervalId = null;
                        }
                    }, 50); // Intentar cada 50ms
                }
                
                // También intentar después de focus/blur
                setTimeout(function() {
                    nombreFieldCheck.focus();
                    setTimeout(function() {
                        nombreFieldCheck.value = nombreToSet;
                        nombreFieldCheck.blur();
                        console.log('✅ Verificación post-focus/blur - nombre:', nombreFieldCheck.value);
                    }, 10);
                }, 100);
            }
            
            if (nombreFieldCheck && nombreFieldCheck.value !== datosProyectoEdicion.nombre && nombreFieldCheck.value === '') {
                console.log('⚠️ Nombre está VACÍO, re-estableciendo por segunda vez...');
                nombreFieldCheck.value = datosProyectoEdicion.nombre;
                nombreFieldCheck.setAttribute('value', datosProyectoEdicion.nombre);
                nombreFieldCheck.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            if (estadoField && estadoField.value !== datosProyectoEdicion.estado) {
                estadoField.value = datosProyectoEdicion.estado;
                estadoField.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            if (codigoIdiField && codigoIdiField.value !== datosProyectoEdicion.codigoIdi) {
                codigoIdiField.value = datosProyectoEdicion.codigoIdi || '';
                codigoIdiField.setAttribute('value', datosProyectoEdicion.codigoIdi || '');
            }
            
            if (montoField && montoField.value != datosProyectoEdicion.monto) {
                montoField.value = datosProyectoEdicion.monto;
                montoField.setAttribute('value', datosProyectoEdicion.monto);
            }
            
            if (itemField && itemField.value != datosProyectoEdicion.itemId) {
                itemField.value = datosProyectoEdicion.itemId;
            }
            
            // Verificación final - ESPECIALMENTE para el nombre
            const nombreFieldFinal = modalElement.querySelector('#nombre');
            const estadoFieldFinal = modalElement.querySelector('#estado');
            const codigoIdiFieldFinal = modalElement.querySelector('#codigo_idi');
            const montoFieldFinal = modalElement.querySelector('#monto_asignado');
            const itemFieldFinal = modalElement.querySelector('#item_id');
            
            console.log('📋 Verificación final de valores:', {
                nombre: nombreFieldFinal?.value || 'VACÍO',
                'nombre (atributo)': nombreFieldFinal?.getAttribute('value') || 'VACÍO',
                'nombre (defaultValue)': nombreFieldFinal?.defaultValue || 'VACÍO',
                estado: estadoFieldFinal?.value || 'VACÍO',
                codigoIdi: codigoIdiFieldFinal?.value || 'VACÍO',
                monto: montoFieldFinal?.value || 'VACÍO',
                itemId: itemFieldFinal?.value || 'VACÍO'
            });
            
            // Si el nombre sigue vacío, intentar una última vez después de un delay más largo
            if (nombreFieldFinal && (!nombreFieldFinal.value || nombreFieldFinal.value === '')) {
                setTimeout(function() {
                    console.log('🔴 ÚLTIMO INTENTO - estableciendo nombre después de 200ms adicionales');
                    const nombreFinal = datosProyectoEdicion.nombre;
                    
                    // Método desesperado: reemplazar el input completo si es necesario
                    if (nombreFinal && nombreFinal !== '') {
                        nombreFieldFinal.value = nombreFinal;
                        nombreFieldFinal.defaultValue = nombreFinal;
                        nombreFieldFinal.setAttribute('value', nombreFinal);
                        
                        // Si aún está vacío después de esto, intentar reemplazar el input
                        setTimeout(function() {
                            if (!nombreFieldFinal.value || nombreFieldFinal.value === '') {
                                console.log('🔥 MÉTODO DESESPERADO - Reemplazando input completo');
                                const nuevoInput = document.createElement('input');
                                nuevoInput.type = 'text';
                                nuevoInput.className = nombreFieldFinal.className;
                                nuevoInput.id = 'nombre';
                                nuevoInput.name = 'nombre';
                                nuevoInput.value = nombreFinal;
                                nuevoInput.defaultValue = nombreFinal;
                                nuevoInput.setAttribute('value', nombreFinal);
                                nuevoInput.required = true;
                                
                                // Reemplazar el input original
                                if (nombreFieldFinal.parentNode) {
                                    nombreFieldFinal.parentNode.replaceChild(nuevoInput, nombreFieldFinal);
                                    console.log('✅ Input reemplazado, nuevo valor:', nuevoInput.value);
                                }
                            } else {
                                console.log('✅ Nombre final establecido:', nombreFieldFinal.value);
                            }
                        }, 100);
                    }
                }, 200);
            }
        }, 100);
    };
    
    // Agregar listener ANTES de mostrar el modal
    modalElement.addEventListener('shown.bs.modal', modalResetHandler, { once: true });
    
    // Mostrar modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

function eliminarProyecto(id, nombre) {
    if (confirm('¿Estás seguro de eliminar el proyecto "' + nombre + '"?\n\nNota: No se puede eliminar si tiene contratos asociados.')) {
        // Crear un formulario temporal para enviar la petición DELETE
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/configuracion/proyectos/' + id;
        
        // Agregar CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);
        
        // Agregar method spoofing para DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Agregar al body y enviar
        document.body.appendChild(form);
        form.submit();
    }
}

// Variables globales para edición
let modalResetHandler = null;
let datosProyectoEdicion = null;

// Función para restaurar todos los botones en carga
function restaurarTodosLosBotones() {
    if (botonesEnCarga.size === 0) return;
    
    console.log('🔧 Restaurando botones en estado de carga:', botonesEnCarga.size);
    const botonesParaRestaurar = Array.from(botonesEnCarga.entries());
    
    botonesParaRestaurar.forEach(([button, data]) => {
        try {
            console.log('🔄 Restaurando botón con ID:', button.getAttribute('data-id'));
            
            // Limpiar timeout de seguridad si existe
            if (data.seguridadTimeout) {
                clearTimeout(data.seguridadTimeout);
            }
            
            // Forzar restauración incluso si el botón fue eliminado del DOM
            if (button && button.parentNode) {
                button.disabled = data.originalDisabled;
                button.innerHTML = data.originalHTML;
                
                // Forzar re-render eliminando y recreando si es necesario
                if (button.innerHTML.includes('spinner') || button.innerHTML.trim() === '') {
                    const nuevoBoton = button.cloneNode(false);
                    nuevoBoton.innerHTML = data.originalHTML;
                    nuevoBoton.disabled = data.originalDisabled;
                    nuevoBoton.setAttribute('data-id', button.getAttribute('data-id'));
                    nuevoBoton.setAttribute('onclick', button.getAttribute('onclick') || '');
                    nuevoBoton.className = button.className;
                    nuevoBoton.classList.add('btn-editar-proyecto');
                    button.parentNode.replaceChild(nuevoBoton, button);
                    console.log('✅ Botón recreado');
                } else {
                    console.log('✅ Botón restaurado correctamente');
                }
            }
        } catch(e) {
            console.error('❌ Error al restaurar botón:', e);
        }
    });
    
    botonesEnCarga.clear();
    console.log('✅ Todos los botones restaurados');
}

// Resetear modal al cerrar (SOLO cuando no estamos editando)
const modalElement = document.getElementById('modalCrearProyecto');

// Múltiples eventos para asegurar restauración
modalElement.addEventListener('hide.bs.modal', function () {
    console.log('🔒 Modal cerrándose (hide), restaurando botones...');
    restaurarTodosLosBotones();
}, { capture: true });

modalElement.addEventListener('hidden.bs.modal', function () {
    console.log('🔒 Modal cerrado (hidden), reseteando...');
    console.log('🔍 Flag de edición:', this.dataset.editing);
    
    // RESTAURAR TODOS LOS BOTONES QUE ESTÉN EN ESTADO DE CARGA (backup)
    restaurarTodosLosBotones();
    
    // Si estábamos editando, no resetear todavía (los datos ya deberían estar guardados)
    // Solo limpiar datos después de un momento
    setTimeout(function() {
        // Limpiar el flag de edición
        document.getElementById('modalCrearProyecto').dataset.editing = '';
        
        // Limpiar datos de edición
        datosProyectoEdicion = null;
        
        // Resetear formulario
        const form = document.getElementById('formProyecto');
        form.reset();
        form.action = '{{ route("proyectos.store") }}';
        const methodField = document.getElementById('methodFieldProyecto');
        if (methodField) {
            methodField.value = 'POST';
            methodField.setAttribute('value', 'POST');
        }
        document.getElementById('modalCrearProyectoLabel').textContent = 'Agregar Proyecto';
        form.querySelector('button[type="submit"]').textContent = 'AGREGAR';
        
        console.log('✅ Modal reseteado para próximo uso');
    }, 100);
    
    // Limpiar listeners temporales
    if (modalResetHandler) {
        document.getElementById('modalCrearProyecto').removeEventListener('shown.bs.modal', modalResetHandler);
        modalResetHandler = null;
    }
}, { capture: true });

// También escuchar clics en los botones de cerrar y el backdrop
modalElement.addEventListener('click', function(e) {
    // Si se hace clic en el backdrop o en un botón de cerrar
    if (e.target.classList.contains('btn-close') || 
        e.target.classList.contains('close') ||
        (e.target === modalElement && e.target.classList.contains('show'))) {
        console.log('🔒 Click detectado en cerrar modal, restaurando botones...');
        setTimeout(() => restaurarTodosLosBotones(), 50);
    }
}, { capture: true });

// Prevenir reset cuando se abre el modal para editar (solo marcar, no prevenir)
document.getElementById('modalCrearProyecto').addEventListener('show.bs.modal', function (event) {
    // Si estamos editando, solo marcar que no se debe resetear
    if (datosProyectoEdicion) {
        console.log('⚠️ Modal abierto para edición, omitiendo reset automático');
        // NO prevenir el evento, solo marcar que estamos editando
        this.dataset.editing = 'true';
    } else {
        // Si es creación (no hay datos de edición), aseguramos que no queden botones en estado de carga
        console.log('🆕 Modal abierto para crear, restaurando posibles botones en carga');
        try { restaurarTodosLosBotones(); } catch(e) { console.error(e); }
    }
});

// También agregar listener global como backup (por si el onclick no funciona)
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado, agregando listeners de backup...');
    
    // Usar delegación de eventos para los botones de editar
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-editar-proyecto')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.btn-editar-proyecto');
            console.log('🔘 Click detectado en botón editar, ID:', button.getAttribute('data-id'));
            editarProyectoDesdeBotón(button);
        }
    });
});
</script>