@php
    // Inicializar variables si no existen
    $contratos = $contratos ?? collect();
    $contratosPorItem = $contratosPorItem ?? collect();
    $proyectos = $proyectos ?? collect();
    $items = $items ?? collect();
    $itemFiltro = $itemFiltro ?? null;
    $montoTotalAdjudicado = $montoTotalAdjudicado ?? 0;
    $montoTotalDisponible = $montoTotalDisponible ?? 0;
    $saldoDisponible = $saldoDisponible ?? 0;
    $totalContratos = $totalContratos ?? 0;
@endphp

<div class="container-fluid">
    <!-- Título -->
    <h4 class="mb-4">
        <i class="bi bi-file-earmark-text"></i> Gestión de Contratos
    </h4>

    <!-- Saldo Global Contratos -->
    <div class="card shadow mb-4">
        <div class="card-body bg-light">
            <h5 class="mb-3 fw-bold">Saldo Global Contratos</h5>
            <div class="row align-items-baseline">
                <div class="col-auto">
                    <h2 class="text-success mb-1 fw-bold" style="font-size: 2.5rem;">$ {{ number_format($saldoDisponible, 0, ',', '.') }}</h2>
                </div>
                <div class="col-auto ms-4">
                    <div class="text-muted" style="font-size: 0.9rem;">
                        <strong>Total disponible:</strong> $ {{ number_format($montoTotalDisponible ?? 0, 0, ',', '.') }} | 
                        <strong>Adjudicado:</strong> $ {{ number_format($montoTotalAdjudicado, 0, ',', '.') }} | 
                        <strong>Contratos:</strong> {{ $totalContratos }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Botón Agregar -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div class="d-flex gap-3">
            <div style="width: 200px;">
                <label for="filtroAnioContratos" class="form-label fw-bold mb-2">
                    <i class="bi bi-calendar"></i> Año
                </label>
                <select class="form-select" id="filtroAnioContratos" onchange="filtrarPorAnioContratos(this.value)">
                    <option value="{{ date('Y') }}" {{ ($anioFiltro ?? date('Y')) == date('Y') ? 'selected' : '' }}>
                        {{ date('Y') }} (Actual)
                    </option>
                    <option value="todos" {{ ($anioFiltro ?? '') == 'todos' ? 'selected' : '' }}>Todos los años</option>
                    @foreach($añosDisponibles ?? [] as $año)
                        @if($año != date('Y'))
                            <option value="{{ $año }}" {{ ($anioFiltro ?? '') == $año ? 'selected' : '' }}>{{ $año }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div style="width: 250px;">
                <label for="filtroItemContratos" class="form-label fw-bold mb-2">Filtrar por Item</label>
                <select class="form-select" id="filtroItemContratos" onchange="filtrarPorItemContratos(this.value)">
                    <option value="">Todos los items</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}" {{ $itemFiltro == $item->id ? 'selected' : '' }}>
                        {{ $item->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary" onclick="filtrarPorAnioContratos('{{ date('Y') }}')" title="Volver a año actual">
                    <i class="bi bi-arrow-clockwise"></i> Año Actual
                </button>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalContrato">
                <i class="bi bi-plus-circle"></i> AGREGAR CONTRATO
            </button>
        </div>
    </div>

    <!-- Listado agrupado por Item -->
    <div class="card shadow">
        <div class="card-body">
            @php
                $hayContratos = false;
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
                    $lista = isset($contratosPorItem) ? ($contratosPorItem[$itemLoop->id] ?? collect()) : collect();
                @endphp
                @if($lista->count() > 0)
                    @php $hayContratos = true; @endphp
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemLoop->nombre }}</h6>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-primary">
                                        <tr>
                                            <th class="ps-3">Nombre</th>
                                            <th>Proyecto</th>
                                            <th># Contrato</th>
                                            <th>Proveedor</th>
                                            <th>Estado</th>
                                            <th class="text-end">Monto</th>
                                            <th class="text-end pe-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lista as $contrato)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-medium">{{ $contrato->nombre_contrato }}</span>
                                                    @if(($contrato->cantidad_precios ?? 0) > 0)
                                                    <span class="badge bg-success ms-2" title="{{ $contrato->cantidad_precios }} precios unitarios cargados">
                                                        <i class="bi bi-check-circle"></i> {{ $contrato->cantidad_precios }}
                                                    </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $contrato->proyecto->nombre ?? '-' }}</td>
                                            <td>{{ $contrato->numero_contrato ?? '-' }}</td>
                                            <td>{{ $contrato->proveedor ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $estadoNormalizado = trim($contrato->estado ?? '');
                                                    // Normalizar estados con problemas de codificación
                                                    if (stripos($estadoNormalizado, 'ejecuci') !== false) {
                                                        $estadoNormalizado = 'Ejecución';
                                                    }
                                                    
                                                    $badgeClass = 'warning';
                                                    if ($estadoNormalizado === 'Terminado') {
                                                        $badgeClass = 'secondary';
                                                    } elseif ($estadoNormalizado === 'Ejecución') {
                                                        $badgeClass = 'success';
                                                    } elseif ($estadoNormalizado === 'Adjudicación') {
                                                        $badgeClass = 'info';
                                                    } elseif ($estadoNormalizado === 'Licitación') {
                                                        $badgeClass = 'warning';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $badgeClass }}">{{ $estadoNormalizado }}</span>
                                            </td>
                                            <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end pe-3">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editarContrato({{ $contrato->id }})"><i class="bi bi-pencil"></i></button>
                                                <form class="d-inline" method="POST" action="{{ route('contratos.destroy', $contrato->id) }}" onsubmit="return confirm('¿Eliminar contrato?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-secondary">
                                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                                            <td class="text-end fw-bold">$ {{ number_format($lista->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
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

            @if(!$hayContratos && empty($itemFiltro))
                <div class="text-center text-muted py-5">
                    No hay contratos registrados. Crea uno nuevo para comenzar.
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
            </div>
        </div>
        @php $listaSub31 = isset($contratosPorItem) ? ($contratosPorItem[$itemSub31->id] ?? collect()) : collect(); @endphp
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Proyecto</th>
                            <th># Contrato</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listaSub31 as $contrato)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <span class="fw-medium">{{ $contrato->nombre_contrato }}</span>
                                    @if(($contrato->cantidad_precios ?? 0) > 0)
                                    <span class="badge bg-success ms-2" title="{{ $contrato->cantidad_precios }} precios unitarios cargados">
                                        <i class="bi bi-check-circle"></i> {{ $contrato->cantidad_precios }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $contrato->proyecto->nombre ?? '-' }}</td>
                            <td>{{ $contrato->numero_contrato ?? '-' }}</td>
                            <td>{{ $contrato->proveedor ?? '-' }}</td>
                            <td>
                                @php
                                    $estadoNormalizado = trim($contrato->estado ?? '');
                                    // Normalizar estados con problemas de codificación
                                    if (stripos($estadoNormalizado, 'ejecuci') !== false) {
                                        $estadoNormalizado = 'Ejecución';
                                    }
                                    
                                    $badgeClass = 'warning';
                                    if ($estadoNormalizado === 'Terminado') {
                                        $badgeClass = 'secondary';
                                    } elseif ($estadoNormalizado === 'Ejecución') {
                                        $badgeClass = 'success';
                                    } elseif ($estadoNormalizado === 'Adjudicación') {
                                        $badgeClass = 'info';
                                    } elseif ($estadoNormalizado === 'Licitación') {
                                        $badgeClass = 'warning';
                                    }
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $estadoNormalizado }}</span>
                            </td>
                            <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarContrato({{ $contrato->id }})"><i class="bi bi-pencil"></i></button>
                                <form class="d-inline" method="POST" action="{{ route('contratos.destroy', $contrato->id) }}" onsubmit="return confirm('¿Eliminar contrato?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                            <td class="text-end fw-bold">$ {{ number_format($listaSub31->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($debeMostrarEmerg)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemEmerg->nombre }}</h6>
            </div>
        </div>
        @php $listaEmerg = isset($contratosPorItem) ? ($contratosPorItem[$itemEmerg->id] ?? collect()) : collect(); @endphp
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Proyecto</th>
                            <th># Contrato</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listaEmerg as $contrato)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <span class="fw-medium">{{ $contrato->nombre_contrato }}</span>
                                    @if(($contrato->cantidad_precios ?? 0) > 0)
                                    <span class="badge bg-success ms-2" title="{{ $contrato->cantidad_precios }} precios unitarios cargados">
                                        <i class="bi bi-check-circle"></i> {{ $contrato->cantidad_precios }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $contrato->proyecto->nombre ?? '-' }}</td>
                            <td>{{ $contrato->numero_contrato ?? '-' }}</td>
                            <td>{{ $contrato->proveedor ?? '-' }}</td>
                            <td>
                                @php
                                    $estadoNormalizado = trim($contrato->estado ?? '');
                                    // Normalizar estados con problemas de codificación
                                    if (stripos($estadoNormalizado, 'ejecuci') !== false) {
                                        $estadoNormalizado = 'Ejecución';
                                    }
                                    
                                    $badgeClass = 'warning';
                                    if ($estadoNormalizado === 'Terminado') {
                                        $badgeClass = 'secondary';
                                    } elseif ($estadoNormalizado === 'Ejecución') {
                                        $badgeClass = 'success';
                                    } elseif ($estadoNormalizado === 'Adjudicación') {
                                        $badgeClass = 'info';
                                    } elseif ($estadoNormalizado === 'Licitación') {
                                        $badgeClass = 'warning';
                                    }
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $estadoNormalizado }}</span>
                            </td>
                            <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarContrato({{ $contrato->id }})"><i class="bi bi-pencil"></i></button>
                                <form class="d-inline" method="POST" action="{{ route('contratos.destroy', $contrato->id) }}" onsubmit="return confirm('¿Eliminar contrato?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                            <td class="text-end fw-bold">$ {{ number_format($listaEmerg->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($debeMostrarCont)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemCont->nombre }}</h6>
            </div>
        </div>
        @php $listaCont = isset($contratosPorItem) ? ($contratosPorItem[$itemCont->id] ?? collect()) : collect(); @endphp
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Proyecto</th>
                            <th># Contrato</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($listaCont as $contrato)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <span class="fw-medium">{{ $contrato->nombre_contrato }}</span>
                                    @if(($contrato->cantidad_precios ?? 0) > 0)
                                    <span class="badge bg-success ms-2" title="{{ $contrato->cantidad_precios }} precios unitarios cargados">
                                        <i class="bi bi-check-circle"></i> {{ $contrato->cantidad_precios }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $contrato->proyecto->nombre ?? '-' }}</td>
                            <td>{{ $contrato->numero_contrato ?? '-' }}</td>
                            <td>{{ $contrato->proveedor ?? '-' }}</td>
                            <td>
                                @php
                                    $estadoNormalizado = trim($contrato->estado ?? '');
                                    // Normalizar estados con problemas de codificación
                                    if (stripos($estadoNormalizado, 'ejecuci') !== false) {
                                        $estadoNormalizado = 'Ejecución';
                                    }
                                    
                                    $badgeClass = 'warning';
                                    if ($estadoNormalizado === 'Terminado') {
                                        $badgeClass = 'secondary';
                                    } elseif ($estadoNormalizado === 'Ejecución') {
                                        $badgeClass = 'success';
                                    } elseif ($estadoNormalizado === 'Adjudicación') {
                                        $badgeClass = 'info';
                                    } elseif ($estadoNormalizado === 'Licitación') {
                                        $badgeClass = 'warning';
                                    }
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $estadoNormalizado }}</span>
                            </td>
                            <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarContrato({{ $contrato->id }})"><i class="bi bi-pencil"></i></button>
                                <form class="d-inline" method="POST" action="{{ route('contratos.destroy', $contrato->id) }}" onsubmit="return confirm('¿Eliminar contrato?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                            <td class="text-end fw-bold">$ {{ number_format($listaCont->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal Crear/Editar Contrato -->
<div class="modal fade" id="modalContrato" tabindex="-1" aria-labelledby="modalContratoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalContratoLabel">Agregar Contrato</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formContrato" method="POST" action="{{ route('contratos.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="methodFieldContrato" value="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Proyecto *</label>
                                <select class="form-select" name="proyecto_id" id="proyecto_id" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($proyectos as $p)
                                    @php $rest = (float)($p->monto_restante ?? ($p->monto_asignado - ($p->monto_usado ?? 0))); @endphp
                                    @if($rest > 0)
                                        <option value="{{ $p->id }}" data-item="{{ $p->item->nombre ?? '' }}" data-saldo="{{ (int) $rest }}">{{ $p->nombre }} (saldo: $ {{ number_format($rest, 0, ',', '.') }})</option>
                                    @endif
                                    @endforeach
                                </select>
                                <input type="hidden" id="proyecto_actual_contrato" value="">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Nombre del Contrato *</label>
                                <input type="text" class="form-control" name="nombre_contrato" id="nombre_contrato" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">ID LICITACIÓN</label>
                                <input type="text" class="form-control" name="id_licitacion" id="id_licitacion">
                                <small class="text-muted">Campo informativo (opcional)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">N° Formulario</label>
                                <input type="text" class="form-control" name="numero_formulario" id="numero_formulario">
                                <small class="text-muted">Campo informativo (opcional)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">N° Orden de Compra</label>
                                <input type="text" class="form-control" name="orden_compra" id="orden_compra">
                                <small class="text-muted">Campo informativo (opcional) - Número de OC emitida por Mercado Público</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Fecha Orden de Compra</label>
                                <input type="date" class="form-control" name="fecha_oc" id="fecha_oc">
                                <small class="text-muted">Campo informativo (opcional)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">N° Contrato *</label>
                                <input type="text" class="form-control" name="numero_contrato" id="numero_contrato" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Monto adjudicado *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control text-end" name="monto_real" id="monto_real" min="0" step="0.01" required>
                                </div>
                                <small class="text-muted" id="saldo_proyecto_hint"></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Estado *</label>
                                <select class="form-select" name="estado" id="estado_contrato" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Licitación">Licitación</option>
                                    <option value="Adjudicación">Adjudicación</option>
                                    <option value="Ejecución">Ejecución</option>
                                    <option value="Terminado">Terminado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Duración (días) *</label>
                                <input type="number" class="form-control" name="duracion_dias" id="duracion_dias" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Fecha Final</label>
                                <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" readonly>
                                <small class="text-muted">Se calcula automáticamente según fecha inicial y duración</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-2">
                                <label class="form-label">Proveedor *</label>
                                <select class="form-select" name="proveedor" id="proveedor" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach(($proveedores ?? collect()) as $prov)
                                    <option value="{{ $prov->nombre }}">{{ $prov->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-auto">
                            <label class="btn btn-outline-secondary btn-sm mb-0">
                                SUBIR CONTRATO <input type="file" name="archivo_contrato" class="d-none">
                            </label>
                        </div>
                        <div class="col-auto">
                            <label class="btn btn-outline-secondary btn-sm mb-0">
                                SUBIR BASES <input type="file" name="archivo_bases" class="d-none">
                            </label>
                        </div>
                        <div class="col-auto">
                            <label class="btn btn-outline-secondary btn-sm mb-0">
                                SUBIR OFERTA ECONÓMICA <input type="file" name="archivo_oferta" class="d-none">
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-info-circle me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Precios Unitarios:</strong> Puedes cargar un archivo Excel/CSV con el listado de precios unitarios.
                            </div>
                            <label class="btn btn-sm btn-primary mb-0">
                                <i class="bi bi-file-earmark-excel"></i> CARGAR PRECIOS UNITARIOS
                                <input type="file" name="archivo_precios_unitarios" id="archivo_precios_unitarios" class="d-none" accept=".xlsx,.xls,.csv">
                            </label>
                        </div>
                        <div id="mensajeArchivoCargado" class="alert alert-success d-none mt-2 mb-0">
                            <i class="bi bi-check-circle"></i> <strong>Archivo seleccionado:</strong> <span id="nombreArchivo"></span>
                        </div>
                        <div id="infoPreciosExistentes" class="alert alert-info d-none mt-2 mb-0">
                            <i class="bi bi-info-circle"></i> <strong>Precios unitarios existentes:</strong> <span id="cantidadPreciosExistentes">0</span>
                        </div>
                        <div class="mt-2">
                            <strong class="d-block mb-2">Formato del archivo (Excel o CSV):</strong>
                            <div class="bg-light p-2 rounded border">
                                <small class="text-muted d-block mb-1"><strong>Columnas requeridas:</strong></small>
                                <table class="table table-sm table-bordered mb-2" style="font-size: 0.85rem;">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Número Partida <small class="text-muted">(opcional)</small></th>
                                            <th>Título <small class="text-muted">(opcional)</small></th>
                                            <th>Partida</th>
                                            <th>Unidad</th>
                                            <th>Precio Unitario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>Obras Civiles</td>
                                            <td>Reparación de muros</td>
                                            <td>m²</td>
                                            <td>15000</td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>Obras Civiles</td>
                                            <td>Pintura interior</td>
                                            <td>m²</td>
                                            <td>$8500</td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>Instalaciones</td>
                                            <td>Instalación eléctrica</td>
                                            <td>m</td>
                                            <td>$2.500</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <small class="text-muted d-block">
                                    <strong>Notas:</strong>
                                    <ul class="mb-0 mt-1" style="padding-left: 1.2rem;">
                                        <li>La primera fila debe contener los <strong>encabezados</strong> (Número Partida, Título, Partida, Unidad, Precio Unitario)</li>
                                        <li><strong>Título</strong> es opcional y permite agrupar partidas por categorías (ej: "Obras Civiles", "Instalaciones", etc.)</li>
                                        <li>Si una fila tiene <strong>Título</strong> pero no <strong>Partida</strong>, se usará como título de grupo para las siguientes filas</li>
                                        <li>El <strong>Precio Unitario</strong> puede ir con o sin signo peso ($). Ejemplos: <code>15000</code>, <code>$15000</code>, <code>$15.000</code> - Todos funcionan</li>
                                        <li>Si usas puntos (.) como separadores de miles, se eliminarán automáticamente</li>
                                        <li><strong>Número Partida</strong> es opcional pero recomendado</li>
                                        <li>Formatos aceptados: <strong>.xlsx, .xls, .csv</strong></li>
                                        <li>Si usas Excel, puedes guardarlo como CSV para mejor compatibilidad</li>
                                    </ul>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" id="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Función para filtrar por item
function filtrarPorAnioContratos(anio) {
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
    const itemId = document.getElementById('filtroItemContratos')?.value;
    if (itemId) {
        url.searchParams.set('item_id', itemId);
    }
    window.location.href = url.toString();
}

function filtrarPorItemContratos(itemId) {
    const url = new URL(window.location.href);
    // Mantener filtro de año si existe
    const anio = document.getElementById('filtroAnioContratos')?.value;
    if (anio === 'todos') {
        url.searchParams.set('anio', 'todos');
    } else if (anio) {
        // Siempre establecer el parámetro anio, incluso si es el año actual
        url.searchParams.set('anio', anio);
    }
    if (itemId) {
        url.searchParams.set('item_id', itemId);
    } else {
        url.searchParams.delete('item_id');
    }
    window.location.href = url.toString();
}

// Prefill nombre de contrato a partir del Item del proyecto seleccionado
document.getElementById('proyecto_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const itemName = selected ? (selected.getAttribute('data-item') || '').trim() : '';
    const nombreInput = document.getElementById('nombre_contrato');
    const saldoHint = document.getElementById('saldo_proyecto_hint');
    const saldo = selected ? (selected.getAttribute('data-saldo') || '') : '';
    if (!nombreInput) return;
    if (itemName && (!nombreInput.value || nombreInput.dataset.autofill === '1')) {
        nombreInput.value = itemName + ' - ';
        nombreInput.dataset.autofill = '1';
    }
    if (saldoHint) {
        if (saldo) {
            const n = Number(saldo);
            saldoHint.textContent = 'Saldo disponible del proyecto: $ ' + (n.toLocaleString('es-CL'));
        } else {
            saldoHint.textContent = '';
        }
    }
});

// Si el usuario escribe manualmente, desactivar autofill
document.getElementById('nombre_contrato').addEventListener('input', function() {
    this.dataset.autofill = '0';
});

// Función para calcular fecha final automáticamente
function calcularFechaFinal() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const duracionDias = parseInt(document.getElementById('duracion_dias').value) || 0;
    const fechaFinInput = document.getElementById('fecha_fin');
    
    if (fechaInicio && duracionDias > 0) {
        const fechaInicioObj = new Date(fechaInicio);
        fechaInicioObj.setDate(fechaInicioObj.getDate() + duracionDias);
        const fechaFin = fechaInicioObj.toISOString().split('T')[0];
        fechaFinInput.value = fechaFin;
    } else {
        fechaFinInput.value = '';
    }
}

// Event listeners para calcular fecha final cuando cambien fecha inicial o duración
document.getElementById('fecha_inicio').addEventListener('change', calcularFechaFinal);
document.getElementById('duracion_dias').addEventListener('input', calcularFechaFinal);

// Mostrar mensaje cuando se seleccione un archivo de precios unitarios
document.getElementById('archivo_precios_unitarios').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('nombreArchivo').textContent = file.name;
        document.getElementById('mensajeArchivoCargado').classList.remove('d-none');
    } else {
        document.getElementById('mensajeArchivoCargado').classList.add('d-none');
    }
});

function editarContrato(id) {
    fetch('/configuracion/contratos/' + id, { headers: { 'Accept': 'application/json' }})
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('No se pudo cargar'); return; }
            const c = data.contrato;
            
            // Mostrar información de precios unitarios existentes
            const cantidadPrecios = data.cantidad_precios || 0;
            if (cantidadPrecios > 0) {
                document.getElementById('cantidadPreciosExistentes').textContent = cantidadPrecios;
                document.getElementById('infoPreciosExistentes').classList.remove('d-none');
            } else {
                document.getElementById('infoPreciosExistentes').classList.add('d-none');
            }
            
            // Guardar el proyecto actual del contrato
            document.getElementById('proyecto_actual_contrato').value = c.proyecto_id || '';
            
            // Cargar proyectos disponibles (incluyendo el actual si no está disponible)
            fetch('/configuracion/contratos/proyectos-disponibles?contrato_id=' + id, { 
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(proyectosData => {
                if (proyectosData.success) {
                    const select = document.getElementById('proyecto_id');
                    select.innerHTML = '<option value="">Seleccionar...</option>';
                    
                    proyectosData.proyectos.forEach(function(p) {
                        const option = document.createElement('option');
                        option.value = p.id;
                        option.textContent = p.nombre + ' (saldo: $ ' + p.saldo.toLocaleString('es-CL') + ')';
                        option.setAttribute('data-item', p.item || '');
                        option.setAttribute('data-saldo', p.saldo);
                        select.appendChild(option);
                    });
                    
                    // Seleccionar el proyecto actual del contrato
                    document.getElementById('proyecto_id').value = c.proyecto_id || '';
                }
                
                document.getElementById('modalContratoLabel').textContent = 'Editar Contrato';
                const form = document.getElementById('formContrato');
                form.action = '/configuracion/contratos/' + c.id;
                document.getElementById('methodFieldContrato').value = 'PUT';
                document.getElementById('nombre_contrato').value = c.nombre_contrato || '';
                document.getElementById('nombre_contrato').dataset.autofill = '0';
                
                // actualizar saldo hint según proyecto seleccionado
                (function(){
                    const sel = document.getElementById('proyecto_id');
                    const opt = sel.options[sel.selectedIndex];
                    const saldoHint = document.getElementById('saldo_proyecto_hint');
                    if (opt && saldoHint) {
                        const saldo = Number(opt.getAttribute('data-saldo') || 0);
                        saldoHint.textContent = saldo ? ('Saldo disponible del proyecto: $ ' + saldo.toLocaleString('es-CL')) : '';
                    }
                })();
                
                document.getElementById('numero_contrato').value = c.numero_contrato || '';
                document.getElementById('id_licitacion').value = c.id_licitacion || '';
                document.getElementById('orden_compra').value = c.orden_compra || '';
                document.getElementById('fecha_oc').value = c.fecha_oc || '';
                document.getElementById('proveedor').value = c.proveedor || '';
                document.getElementById('estado_contrato').value = c.estado || '';
                document.getElementById('monto_real').value = c.monto_real || 0;
                // Cargar duracion_dias (puede ser null, 0 o un número)
                const duracionDiasValue = (c.duracion_dias !== null && c.duracion_dias !== undefined) ? c.duracion_dias : '';
                document.getElementById('duracion_dias').value = duracionDiasValue;
                console.log('Duración días cargada:', duracionDiasValue, 'desde:', c.duracion_dias);
                document.getElementById('fecha_inicio').value = c.fecha_inicio || '';
                // Calcular fecha final automáticamente si hay fecha inicial y duración
                calcularFechaFinal();
                document.getElementById('observaciones').value = c.observaciones || '';
                new bootstrap.Modal(document.getElementById('modalContrato')).show();
            })
            .catch(err => {
                console.error('Error cargando proyectos:', err);
                alert('Error: ' + err.message);
            });
        })
        .catch(err => alert('Error: ' + err.message));
}

document.getElementById('modalContrato').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('formContrato');
    form.reset();
    form.action = '{{ route('contratos.store') }}';
    document.getElementById('methodFieldContrato').value = 'POST';
    document.getElementById('modalContratoLabel').textContent = 'Agregar Contrato';
    const nombreInput = document.getElementById('nombre_contrato');
    if (nombreInput) nombreInput.dataset.autofill = '1';
    const saldoHint = document.getElementById('saldo_proyecto_hint');
    if (saldoHint) saldoHint.textContent = '';
    document.getElementById('proyecto_actual_contrato').value = '';
    document.getElementById('mensajeArchivoCargado').classList.add('d-none');
    document.getElementById('infoPreciosExistentes').classList.add('d-none');
    document.getElementById('archivo_precios_unitarios').value = '';
    
    // Recargar proyectos disponibles para crear nuevo contrato
    const select = document.getElementById('proyecto_id');
    select.innerHTML = '<option value="">Seleccionar...</option>';
    @foreach($proyectos as $p)
    @php $rest = (float)($p->monto_restante ?? ($p->monto_asignado - ($p->monto_usado ?? 0))); @endphp
    @if($rest > 0)
        select.innerHTML += '<option value="{{ $p->id }}" data-item="{{ $p->item->nombre ?? '' }}" data-saldo="{{ (int) $rest }}">{{ $p->nombre }} (saldo: $ {{ number_format($rest, 0, ',', '.') }})</option>';
    @endif
    @endforeach
});
</script>
@endpush
