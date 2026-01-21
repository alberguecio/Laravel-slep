@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-calculator"></i> Saldos
        </h4>
        <div class="d-flex gap-2 align-items-end">
            <div style="width: 200px;">
                <label for="filtroAnioSaldos" class="form-label fw-bold mb-2">
                    <i class="bi bi-calendar"></i> Año
                </label>
                <select class="form-select" id="filtroAnioSaldos" onchange="filtrarPorAnioSaldos(this.value)">
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
            <button type="button" class="btn btn-outline-secondary" onclick="filtrarPorAnioSaldos('{{ date('Y') }}')" title="Volver a año actual">
                <i class="bi bi-arrow-clockwise"></i> Año Actual
            </button>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <!-- Cuadro Mantenimiento (ancho completo, 5 campos) - FIJO -->
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-tools"></i> Mantenimiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Presupuesto</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosMantencion['presupuesto'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Contratado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosMantencion['contratado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Comprometido</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosMantencion['comprometido'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Ejecutado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosMantencion['ejecutado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Facturado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosMantencion['facturado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Saldo Disponible</div>
                                <div class="h5 mb-0 fw-bold text-{{ ($datosMantencion['saldo'] ?? 0) >= 0 ? 'success' : 'danger' }}">$ {{ number_format($datosMantencion['saldo'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subtítulo 31 (si existe) -->
        @if(isset($datosSubtitulo31) && $datosSubtitulo31)
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-file-earmark-text"></i> {{ $datosSubtitulo31['item']->nombre }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Presupuesto</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosSubtitulo31['presupuesto'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Ejecutado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosSubtitulo31['ejecutado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Saldo Disponible</div>
                                <div class="h5 mb-0 fw-bold text-{{ ($datosSubtitulo31['saldo'] ?? 0) >= 0 ? 'success' : 'danger' }}">$ {{ number_format($datosSubtitulo31['saldo'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Emergencia (si existe) -->
        @if(isset($datosEmergencia) && $datosEmergencia)
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-file-earmark-text"></i> {{ $datosEmergencia['item']->nombre }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Presupuesto</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosEmergencia['presupuesto'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Ejecutado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosEmergencia['ejecutado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Saldo Disponible</div>
                                <div class="h5 mb-0 fw-bold text-{{ ($datosEmergencia['saldo'] ?? 0) >= 0 ? 'success' : 'danger' }}">$ {{ number_format($datosEmergencia['saldo'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Contingencia (si existe) -->
        @if(isset($datosContingencia) && $datosContingencia)
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-file-earmark-text"></i> {{ $datosContingencia['item']->nombre }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Presupuesto</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosContingencia['presupuesto'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Ejecutado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosContingencia['ejecutado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Saldo Disponible</div>
                                <div class="h5 mb-0 fw-bold text-{{ ($datosContingencia['saldo'] ?? 0) >= 0 ? 'success' : 'danger' }}">$ {{ number_format($datosContingencia['saldo'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Cuadros dinámicos para cada item (excepto Mantenimiento y especiales) -->
        @foreach($datosItemsDinamicos ?? [] as $datosItem)
        @php
            $item = $datosItem['item'];
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            $esSubtitulo31 = (strpos($nombreNormalizado, 'subtitulo') !== false && strpos($nombreNormalizado, '31') !== false);
            $esEmergencia = (strpos($nombreNormalizado, 'emergencia') !== false);
            
            // Determinar color del header
            if ($esSubtitulo31) {
                $headerClass = 'bg-info';
            } elseif ($esEmergencia) {
                $headerClass = 'bg-danger';
            } else {
                $headerClass = 'bg-secondary';
            }
        @endphp
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header {{ $headerClass }} text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-file-earmark-text"></i> {{ $item->nombre }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Presupuesto</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosItem['presupuesto'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Ejecutado</div>
                                <div class="h5 mb-0 fw-bold">$ {{ number_format($datosItem['ejecutado'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Saldo Disponible</div>
                                <div class="h5 mb-0 fw-bold text-{{ ($datosItem['saldo'] ?? 0) >= 0 ? 'success' : 'danger' }}">$ {{ number_format($datosItem['saldo'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Buscadores -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-search"></i> Búsqueda por Comuna o Establecimiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label for="buscador-comuna" class="form-label">Comuna</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscador-comuna" 
                                   placeholder="Buscar comuna..."
                                   autocomplete="off">
                            <input type="hidden" id="comuna-id" value="">
                            <div id="comuna-dropdown" class="dropdown-menu w-100" style="display: none; max-height: 300px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000;"></div>
                        </div>
                        <div class="col-md-6 position-relative">
                            <label for="buscador-establecimiento" class="form-label">Establecimiento</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscador-establecimiento" 
                                   placeholder="Buscar establecimiento..."
                                   autocomplete="off">
                            <input type="hidden" id="establecimiento-id" value="">
                            <div id="establecimiento-dropdown" class="dropdown-menu w-100" style="display: none; max-height: 300px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000;"></div>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" id="btn-buscar">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-limpiar">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados de Búsqueda -->
    <div class="row mb-4" id="resultados-busqueda" style="display: none;">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 fw-bold" id="titulo-resultados">
                        <i class="bi bi-list-ul"></i> Resultados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Comuna</th>
                                    <th class="text-end">Subv. Mantenimiento</th>
                                    <th class="text-end">Aporte</th>
                                    <th class="text-end">Comprometido</th>
                                    <th class="text-end">Ejecutado</th>
                                    <th class="text-end">Saldo</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-resultados">
                                <!-- Los resultados se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de Contratos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    @php
                        $hayContratos = false;
                        $todosLosItems = $itemsMantencion ?? collect();
                    @endphp

                    @forelse($itemsMantencion ?? [] as $itemLoop)
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lista as $contrato)
                                                <tr style="cursor: pointer;" onclick="mostrarDetalleContrato({{ $contrato->id }})" data-contrato-id="{{ $contrato->id }}">
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
                                                            $badgeClass = 'warning';
                                                            if ($contrato->estado === 'Terminado') {
                                                                $badgeClass = 'secondary';
                                                            } elseif ($contrato->estado === 'Ejecución') {
                                                                $badgeClass = 'success';
                                                            } elseif ($contrato->estado === 'Adjudicación') {
                                                                $badgeClass = 'info';
                                                            } elseif ($contrato->estado === 'Licitación') {
                                                                $badgeClass = 'warning';
                                                            }
                                                        @endphp
                                                        <span class="badge bg-{{ $badgeClass }}">{{ $contrato->estado }}</span>
                                                    </td>
                                                    <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-secondary">
                                                    <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                                                    <td class="text-end fw-bold">$ {{ number_format($lista->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                    @endforelse

                    @if(isset($itemSubtitulo31) && $itemSubtitulo31)
                        @php $listaSub31 = isset($contratosPorItem) ? ($contratosPorItem[$itemSubtitulo31->id] ?? collect()) : collect(); @endphp
                        @if($listaSub31->count() > 0)
                            @php $hayContratos = true; @endphp
                            <div class="card shadow-sm mb-3" style="margin-top: 1rem;">
                                <div class="card-header bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemSubtitulo31->nombre }}</h6>
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($listaSub31 as $contrato)
                                                <tr style="cursor: pointer;" onclick="mostrarDetalleContrato({{ $contrato->id }})" data-contrato-id="{{ $contrato->id }}">
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
                                                            $badgeClass = 'warning';
                                                            if ($contrato->estado === 'Terminado') {
                                                                $badgeClass = 'secondary';
                                                            } elseif ($contrato->estado === 'Ejecución') {
                                                                $badgeClass = 'success';
                                                            } elseif ($contrato->estado === 'Adjudicación') {
                                                                $badgeClass = 'info';
                                                            } elseif ($contrato->estado === 'Licitación') {
                                                                $badgeClass = 'warning';
                                                            }
                                                        @endphp
                                                        <span class="badge bg-{{ $badgeClass }}">{{ $contrato->estado }}</span>
                                                    </td>
                                                    <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-secondary">
                                                    <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                                                    <td class="text-end fw-bold">$ {{ number_format($listaSub31->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if(isset($itemEmergencia) && $itemEmergencia)
                        @php $listaEmerg = isset($contratosPorItem) ? ($contratosPorItem[$itemEmergencia->id] ?? collect()) : collect(); @endphp
                        @if($listaEmerg->count() > 0)
                            @php $hayContratos = true; @endphp
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemEmergencia->nombre }}</h6>
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($listaEmerg as $contrato)
                                                <tr style="cursor: pointer;" onclick="mostrarDetalleContrato({{ $contrato->id }})" data-contrato-id="{{ $contrato->id }}">
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
                                                            $badgeClass = 'warning';
                                                            if ($contrato->estado === 'Terminado') {
                                                                $badgeClass = 'secondary';
                                                            } elseif ($contrato->estado === 'Ejecución') {
                                                                $badgeClass = 'success';
                                                            } elseif ($contrato->estado === 'Adjudicación') {
                                                                $badgeClass = 'info';
                                                            } elseif ($contrato->estado === 'Licitación') {
                                                                $badgeClass = 'warning';
                                                            }
                                                        @endphp
                                                        <span class="badge bg-{{ $badgeClass }}">{{ $contrato->estado }}</span>
                                                    </td>
                                                    <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-secondary">
                                                    <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                                                    <td class="text-end fw-bold">$ {{ number_format($listaEmerg->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if(isset($itemContingencia) && $itemContingencia)
                        @php $listaCont = isset($contratosPorItem) ? ($contratosPorItem[$itemContingencia->id] ?? collect()) : collect(); @endphp
                        @if($listaCont->count() > 0)
                            @php $hayContratos = true; @endphp
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-uppercase text-muted fw-bold">{{ $itemContingencia->nombre }}</h6>
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($listaCont as $contrato)
                                                <tr style="cursor: pointer;" onclick="mostrarDetalleContrato({{ $contrato->id }})" data-contrato-id="{{ $contrato->id }}">
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
                                                            $badgeClass = 'warning';
                                                            if ($contrato->estado === 'Terminado') {
                                                                $badgeClass = 'secondary';
                                                            } elseif ($contrato->estado === 'Ejecución') {
                                                                $badgeClass = 'success';
                                                            } elseif ($contrato->estado === 'Adjudicación') {
                                                                $badgeClass = 'info';
                                                            } elseif ($contrato->estado === 'Licitación') {
                                                                $badgeClass = 'warning';
                                                            }
                                                        @endphp
                                                        <span class="badge bg-{{ $badgeClass }}">{{ $contrato->estado }}</span>
                                                    </td>
                                                    <td class="text-end">$ {{ number_format($contrato->monto_real ?? 0, 0, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-secondary">
                                                    <td colspan="4" class="ps-3 fw-bold text-end">Total:</td>
                                                    <td class="text-end fw-bold">$ {{ number_format($listaCont->sum('monto_real') ?? 0, 0, ',', '.') }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if(!$hayContratos)
                        <div class="text-center text-muted py-5">
                            No hay contratos registrados.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Contrato -->
<div class="modal fade" id="modalDetalleContrato" tabindex="-1" aria-labelledby="modalDetalleContratoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleContratoLabel">
                    <i class="bi bi-file-earmark-text"></i> Detalle del Contrato
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Información del Contrato -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Información del Contrato</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3" id="info-contrato">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Saldos del Contrato -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Saldos del Contrato</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3" id="saldos-contrato">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Órdenes de Compra -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Órdenes de Compra</h6>
                    </div>
                    <div class="card-body">
                        <div id="ordenes-compra-lista">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- OTs sin OC -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Órdenes de Trabajo sin OC</h6>
                    </div>
                    <div class="card-body">
                        <div id="ots-sin-oc-lista">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Sección de Presupuesto OT (se mostrará dinámicamente) -->
                <div class="card mt-3" id="seccion-presupuesto-ot" style="display: none;">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Presupuesto de Orden de Trabajo</h6>
                        <button type="button" class="btn btn-sm btn-light" onclick="cerrarPresupuestoOT()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="presupuesto-ot-contenido">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscadorComuna = document.getElementById('buscador-comuna');
    const buscadorEstablecimiento = document.getElementById('buscador-establecimiento');
    const comunaId = document.getElementById('comuna-id');
    const establecimientoId = document.getElementById('establecimiento-id');
    const comunaDropdown = document.getElementById('comuna-dropdown');
    const establecimientoDropdown = document.getElementById('establecimiento-dropdown');
    const btnBuscar = document.getElementById('btn-buscar');
    const btnLimpiar = document.getElementById('btn-limpiar');
    const resultadosBusqueda = document.getElementById('resultados-busqueda');
    const tablaResultados = document.getElementById('tabla-resultados');
    const tituloResultados = document.getElementById('titulo-resultados');
    
    let timeoutComuna = null;
    let timeoutEstablecimiento = null;
    
    // Buscar comunas
    buscadorComuna.addEventListener('input', function() {
        const termino = this.value.trim();
        
        clearTimeout(timeoutComuna);
        
        if (termino.length < 2) {
            comunaDropdown.style.display = 'none';
            comunaId.value = '';
            return;
        }
        
        timeoutComuna = setTimeout(function() {
            fetch('/saldos/buscar-comunas?q=' + encodeURIComponent(termino))
                .then(response => response.json())
                .then(data => {
                    mostrarComunas(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 300);
    });
    
    function mostrarComunas(comunas) {
        comunaDropdown.innerHTML = '';
        
        if (comunas.length === 0) {
            comunaDropdown.innerHTML = '<div class="dropdown-item text-muted">No se encontraron comunas</div>';
            comunaDropdown.style.display = 'block';
            return;
        }
        
        comunas.forEach(function(comuna) {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'dropdown-item';
            item.textContent = comuna.nombre;
            item.addEventListener('click', function(e) {
                e.preventDefault();
                buscadorComuna.value = comuna.nombre;
                comunaId.value = comuna.id;
                comunaDropdown.style.display = 'none';
                // Limpiar establecimiento cuando se selecciona comuna
                buscadorEstablecimiento.value = '';
                establecimientoId.value = '';
            });
            comunaDropdown.appendChild(item);
        });
        
        comunaDropdown.style.display = 'block';
    }
    
    // Buscar establecimientos
    buscadorEstablecimiento.addEventListener('input', function() {
        const termino = this.value.trim();
        const comunaIdValue = comunaId.value;
        
        clearTimeout(timeoutEstablecimiento);
        
        if (termino.length < 2) {
            establecimientoDropdown.style.display = 'none';
            establecimientoId.value = '';
            return;
        }
        
        timeoutEstablecimiento = setTimeout(function() {
            let url = '/saldos/buscar-establecimientos?q=' + encodeURIComponent(termino);
            if (comunaIdValue) {
                url += '&comuna_id=' + comunaIdValue;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    mostrarEstablecimientos(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 300);
    });
    
    function mostrarEstablecimientos(establecimientos) {
        establecimientoDropdown.innerHTML = '';
        
        if (establecimientos.length === 0) {
            establecimientoDropdown.innerHTML = '<div class="dropdown-item text-muted">No se encontraron establecimientos</div>';
            establecimientoDropdown.style.display = 'block';
            return;
        }
        
        establecimientos.forEach(function(est) {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'dropdown-item';
            item.innerHTML = '<strong>' + est.nombre + '</strong>' + (est.comuna ? ' - ' + est.comuna : '');
            item.addEventListener('click', function(e) {
                e.preventDefault();
                buscadorEstablecimiento.value = est.nombre;
                establecimientoId.value = est.id;
                establecimientoDropdown.style.display = 'none';
                // Limpiar comuna cuando se selecciona establecimiento
                buscadorComuna.value = '';
                comunaId.value = '';
            });
            establecimientoDropdown.appendChild(item);
        });
        
        establecimientoDropdown.style.display = 'block';
    }
    
    // Ocultar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!buscadorComuna.contains(e.target) && !comunaDropdown.contains(e.target)) {
            comunaDropdown.style.display = 'none';
        }
        if (!buscadorEstablecimiento.contains(e.target) && !establecimientoDropdown.contains(e.target)) {
            establecimientoDropdown.style.display = 'none';
        }
    });
    
    // Buscar
    btnBuscar.addEventListener('click', function() {
        const comunaIdValue = comunaId.value;
        const establecimientoIdValue = establecimientoId.value;
        
        if (!comunaIdValue && !establecimientoIdValue) {
            alert('Por favor, seleccione una comuna o un establecimiento');
            return;
        }
        
        let url = '/saldos/obtener-detalle?';
        if (establecimientoIdValue) {
            url += 'establecimiento_id=' + establecimientoIdValue;
        } else if (comunaIdValue) {
            url += 'comuna_id=' + comunaIdValue;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                mostrarResultados(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al obtener los datos');
            });
    });
    
function mostrarResultados(data) {
    let titulo = '';
    if (data.tipo === 'establecimiento') {
        titulo = 'Establecimiento: ' + data.establecimiento.nombre;
    } else if (data.tipo === 'comuna') {
        titulo = 'Comuna: ' + data.comuna.nombre + ' (' + data.datos.length + ' establecimientos)';
    }
    
    tituloResultados.innerHTML = '<i class="bi bi-list-ul"></i> ' + titulo;
    
    tablaResultados.innerHTML = '';
    
    data.datos.forEach(function(dato) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${dato.establecimiento_nombre}</td>
            <td>${dato.comuna_nombre || '-'}</td>
            <td class="text-end">$${formatearNumero(dato.subvencion_mantenimiento)}</td>
            <td class="text-end">$${formatearNumero(dato.aporte)}</td>
            <td class="text-end">$${formatearNumero(dato.comprometido)}</td>
            <td class="text-end">$${formatearNumero(dato.ejecutado)}</td>
            <td class="text-end ${dato.saldo >= 0 ? 'text-success' : 'text-danger'}">$${formatearNumero(dato.saldo)}</td>
        `;
        tablaResultados.appendChild(row);
    });
    
    resultadosBusqueda.style.display = 'block';
    resultadosBusqueda.scrollIntoView({ behavior: 'smooth' });
}

    // Limpiar
    btnLimpiar.addEventListener('click', function() {
        buscadorComuna.value = '';
        buscadorEstablecimiento.value = '';
        comunaId.value = '';
        establecimientoId.value = '';
        comunaDropdown.style.display = 'none';
        establecimientoDropdown.style.display = 'none';
        resultadosBusqueda.style.display = 'none';
    });
});

// Función global para formatear números
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numero || 0);
}

// Función para limpiar intervalos del semáforo
function limpiarIntervalosSemaforo() {
    if (window.semaforoIntervals) {
        Object.values(window.semaforoIntervals).forEach(interval => clearInterval(interval));
        window.semaforoIntervals = {};
    }
    if (window.semaforoIntervalsSegundos) {
        Object.values(window.semaforoIntervalsSegundos).forEach(interval => clearInterval(interval));
        window.semaforoIntervalsSegundos = {};
    }
}

// Función para mostrar detalle del contrato
function mostrarDetalleContrato(contratoId) {
    // Cerrar sección de presupuesto si está abierta
    cerrarPresupuestoOT();
    
    // Limpiar intervalos anteriores
    limpiarIntervalosSemaforo();
    
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleContrato'));
    
    // Limpiar intervalos cuando se cierre el modal
    const modalElement = document.getElementById('modalDetalleContrato');
    modalElement.addEventListener('hidden.bs.modal', function limpiarAlCerrar() {
        limpiarIntervalosSemaforo();
        modalElement.removeEventListener('hidden.bs.modal', limpiarAlCerrar);
    }, { once: true });
    
    // Mostrar loading
    document.getElementById('info-contrato').innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    document.getElementById('saldos-contrato').innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    document.getElementById('ordenes-compra-lista').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    document.getElementById('ots-sin-oc-lista').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    
    modal.show();
    
    fetch('/saldos/contrato/' + contratoId + '/detalle')
        .then(response => response.json())
        .then(data => {
            mostrarInfoContrato(data.contrato);
            mostrarSaldosContrato(data.saldos);
            mostrarOrdenesCompra(data.ordenes_compra);
            mostrarOTsSinOC(data.ots_sin_oc);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el detalle del contrato');
        });
}

function mostrarInfoContrato(contrato) {
    const html = `
        <div class="col-md-6">
            <strong>Nombre:</strong> ${contrato.nombre_contrato || '-'}
        </div>
        <div class="col-md-6">
            <strong>Número:</strong> ${contrato.numero_contrato || '-'}
        </div>
        <div class="col-md-6">
            <strong>Proyecto:</strong> ${contrato.proyecto || '-'}
        </div>
        <div class="col-md-6">
            <strong>Item:</strong> ${contrato.item || '-'}
        </div>
        <div class="col-md-6">
            <strong>ID Licitación:</strong> ${contrato.id_licitacion || '-'}
        </div>
        <div class="col-md-6">
            <strong>Monto:</strong> $${formatearNumero(contrato.monto_real || 0)}
        </div>
        <div class="col-md-6">
            <strong>Estado:</strong> <span class="badge bg-info">${contrato.estado || '-'}</span>
        </div>
        <div class="col-md-6">
            <strong>Proveedor:</strong> ${contrato.proveedor || '-'}
        </div>
        <div class="col-md-6">
            <strong>Fecha Inicio:</strong> ${contrato.fecha_inicio || '-'}
        </div>
        <div class="col-md-6">
            <strong>Fecha Fin:</strong> ${contrato.fecha_fin || '-'}
        </div>
        <div class="col-md-6">
            <strong>Duración:</strong> ${contrato.duracion_dias || '-'} días
        </div>
        <div class="col-md-6">
            <strong>Orden Compra:</strong> ${contrato.orden_compra || '-'}
        </div>
        <div class="col-md-6">
            <strong>Fecha OC:</strong> ${contrato.fecha_oc || '-'}
        </div>
        ${contrato.observaciones ? `
        <div class="col-12">
            <strong>Observaciones:</strong> ${contrato.observaciones}
        </div>
        ` : ''}
        ${contrato.fecha_fin && contrato.fecha_fin !== '-' ? `
        <div class="col-12 mt-3">
            <strong>Plazo de Término:</strong>
            <div class="mt-2" id="semaforo-plazo-${contrato.id}">
                <!-- Se llenará dinámicamente con el semáforo -->
            </div>
        </div>
        ` : ''}
        <div class="col-12 mt-3">
            <strong>Adjuntos:</strong>
            <div class="d-flex gap-2 mt-2">
                ${contrato.archivo_contrato ? `
                <a href="/contratos/${contrato.id}/descargar-adjunto/contrato" 
                   class="btn btn-outline-primary btn-sm" 
                   title="Ver Contrato"
                   target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> Contrato
                </a>
                ` : '<span class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-file-earmark"></i> Contrato</span>'}
                
                ${contrato.archivo_bases ? `
                <a href="/contratos/${contrato.id}/descargar-adjunto/bases" 
                   class="btn btn-outline-primary btn-sm" 
                   title="Ver Bases"
                   target="_blank">
                    <i class="bi bi-file-earmark-text"></i> Bases
                </a>
                ` : '<span class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-file-earmark"></i> Bases</span>'}
                
                ${contrato.archivo_oferta ? `
                <a href="/contratos/${contrato.id}/descargar-adjunto/oferta" 
                   class="btn btn-outline-primary btn-sm" 
                   title="Ver Oferta Económica"
                   target="_blank">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Oferta Económica
                </a>
                ` : '<span class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-file-earmark"></i> Oferta Económica</span>'}
            </div>
        </div>
    `;
    document.getElementById('info-contrato').innerHTML = html;
    
    // Mostrar semáforo si hay fecha de término
    if (contrato.fecha_fin && contrato.fecha_fin !== '-') {
        mostrarSemaforoPlazo(contrato.id, contrato.fecha_fin);
    }
}

function mostrarSemaforoPlazo(contratoId, fechaFin) {
    // Convertir fecha de formato dd/mm/yyyy a Date
    const partesFecha = fechaFin.split('/');
    if (partesFecha.length !== 3) return;
    
    const fechaFinObj = new Date(parseInt(partesFecha[2]), parseInt(partesFecha[1]) - 1, parseInt(partesFecha[0]));
    const ahora = new Date();
    
    // Calcular diferencia en milisegundos
    const diferencia = fechaFinObj - ahora;
    const diasRestantes = Math.ceil(diferencia / (1000 * 60 * 60 * 24));
    
    // Determinar color del semáforo
    let colorSemaforo = 'success'; // Verde
    let textoEstado = 'En plazo';
    let icono = 'bi-check-circle';
    
    if (diasRestantes < 0) {
        colorSemaforo = 'danger'; // Rojo
        textoEstado = 'Vencido';
        icono = 'bi-x-circle';
    } else if (diasRestantes <= 30) {
        colorSemaforo = 'warning'; // Amarillo
        textoEstado = 'Por vencer';
        icono = 'bi-exclamation-triangle';
    }
    
    // Formatear cuenta regresiva
    let cuentaRegresiva = '';
    if (diasRestantes < 0) {
        const diasVencidos = Math.abs(diasRestantes);
        cuentaRegresiva = `${diasVencidos} día${diasVencidos !== 1 ? 's' : ''} vencido${diasVencidos !== 1 ? 's' : ''}`;
    } else if (diasRestantes === 0) {
        cuentaRegresiva = 'Vence hoy';
    } else if (diasRestantes === 1) {
        cuentaRegresiva = 'Vence mañana';
    } else {
        cuentaRegresiva = `${diasRestantes} día${diasRestantes !== 1 ? 's' : ''} restante${diasRestantes !== 1 ? 's' : ''}`;
    }
    
    // Determinar color Bootstrap y valores RGB para el semáforo
    let colorBootstrap = 'success';
    let shadowColor = 'rgba(25, 135, 84, 0.6)'; // Verde por defecto
    if (colorSemaforo === 'danger') {
        colorBootstrap = 'danger';
        shadowColor = 'rgba(220, 53, 69, 0.6)'; // Rojo
    } else if (colorSemaforo === 'warning') {
        colorBootstrap = 'warning';
        shadowColor = 'rgba(255, 193, 7, 0.6)'; // Amarillo
    }
    
    const htmlSemaforo = `
        <div class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
            <div class="semaforo-container position-relative">
                <div class="semaforo-luz semaforo-${colorSemaforo}" 
                     style="width: 50px; height: 50px; border-radius: 50%; 
                            background-color: var(--bs-${colorBootstrap}); 
                            box-shadow: 0 0 20px ${shadowColor}, inset 0 0 10px rgba(255,255,255,0.3); 
                            display: flex; align-items: center; justify-content: center;
                            border: 3px solid rgba(255,255,255,0.5);">
                    <i class="bi ${icono} text-white fs-5"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold text-${colorBootstrap} mb-1">${textoEstado}</div>
                <div class="text-${colorBootstrap} fw-semibold" id="cuenta-regresiva-${contratoId}" style="font-size: 1.1rem;">${cuentaRegresiva}</div>
                <div class="text-muted small mt-1">Fecha término: ${fechaFin}</div>
            </div>
        </div>
    `;
    
    const contenedorSemaforo = document.getElementById(`semaforo-plazo-${contratoId}`);
    if (contenedorSemaforo) {
        contenedorSemaforo.innerHTML = htmlSemaforo;
        
        // Actualizar cuenta regresiva cada minuto
        if (window.semaforoIntervals) {
            window.semaforoIntervals[contratoId] = setInterval(() => {
                actualizarCuentaRegresiva(contratoId, fechaFin);
            }, 60000); // Actualizar cada minuto
        } else {
            window.semaforoIntervals = {};
            window.semaforoIntervals[contratoId] = setInterval(() => {
                actualizarCuentaRegresiva(contratoId, fechaFin);
            }, 60000);
        }
        
        // Actualizar inmediatamente cada segundo para mostrar segundos
        actualizarCuentaRegresiva(contratoId, fechaFin);
        if (window.semaforoIntervalsSegundos) {
            clearInterval(window.semaforoIntervalsSegundos[contratoId]);
        } else {
            window.semaforoIntervalsSegundos = {};
        }
        window.semaforoIntervalsSegundos[contratoId] = setInterval(() => {
            actualizarCuentaRegresiva(contratoId, fechaFin);
        }, 1000); // Actualizar cada segundo
    }
}

function actualizarCuentaRegresiva(contratoId, fechaFin) {
    const partesFecha = fechaFin.split('/');
    if (partesFecha.length !== 3) return;
    
    const fechaFinObj = new Date(parseInt(partesFecha[2]), parseInt(partesFecha[1]) - 1, parseInt(partesFecha[0]));
    fechaFinObj.setHours(23, 59, 59, 999); // Fin del día
    const ahora = new Date();
    
    const diferencia = fechaFinObj - ahora;
    const diasRestantes = Math.floor(diferencia / (1000 * 60 * 60 * 24));
    const horasRestantes = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutosRestantes = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
    const segundosRestantes = Math.floor((diferencia % (1000 * 60)) / 1000);
    
    const cuentaRegresivaEl = document.getElementById(`cuenta-regresiva-${contratoId}`);
    if (!cuentaRegresivaEl) return;
    
    let cuentaRegresiva = '';
    if (diasRestantes < 0) {
        const diasVencidos = Math.abs(diasRestantes);
        cuentaRegresiva = `${diasVencidos} día${diasVencidos !== 1 ? 's' : ''} vencido${diasVencidos !== 1 ? 's' : ''}`;
    } else if (diasRestantes === 0 && horasRestantes === 0 && minutosRestantes === 0) {
        cuentaRegresiva = `${segundosRestantes} segundo${segundosRestantes !== 1 ? 's' : ''} restante${segundosRestantes !== 1 ? 's' : ''}`;
    } else if (diasRestantes === 0 && horasRestantes === 0) {
        cuentaRegresiva = `${minutosRestantes} minuto${minutosRestantes !== 1 ? 's' : ''} ${segundosRestantes} segundo${segundosRestantes !== 1 ? 's' : ''}`;
    } else if (diasRestantes === 0) {
        cuentaRegresiva = `${horasRestantes} hora${horasRestantes !== 1 ? 's' : ''} ${minutosRestantes} minuto${minutosRestantes !== 1 ? 's' : ''}`;
    } else if (diasRestantes === 1) {
        cuentaRegresiva = `1 día, ${horasRestantes} hora${horasRestantes !== 1 ? 's' : ''}`;
    } else {
        cuentaRegresiva = `${diasRestantes} día${diasRestantes !== 1 ? 's' : ''} restante${diasRestantes !== 1 ? 's' : ''}`;
    }
    
    cuentaRegresivaEl.textContent = cuentaRegresiva;
}

function mostrarSaldosContrato(saldos) {
    if (!saldos) {
        document.getElementById('saldos-contrato').innerHTML = '<div class="col-12 text-center text-muted py-3">No hay información de saldos disponible.</div>';
        return;
    }
    
    const html = `
        <div class="col-md-3">
            <div class="border rounded p-3 text-center" style="background-color: #fff3cd;">
                <div class="text-muted small mb-1">Comprometido</div>
                <div class="h5 mb-0 fw-bold text-warning">$${formatearNumero(saldos.comprometido || 0)}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3 text-center" style="background-color: #d1ecf1;">
                <div class="text-muted small mb-1">Ejecutado</div>
                <div class="h5 mb-0 fw-bold text-info">$${formatearNumero(saldos.ejecutado || 0)}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3 text-center" style="background-color: #d4edda;">
                <div class="text-muted small mb-1">Facturado</div>
                <div class="h5 mb-0 fw-bold text-success">$${formatearNumero(saldos.facturado || 0)}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3 text-center" style="background-color: ${(saldos.saldo || 0) >= 0 ? '#d4edda' : '#f8d7da'};">
                <div class="text-muted small mb-1">Saldo</div>
                <div class="h5 mb-0 fw-bold ${(saldos.saldo || 0) >= 0 ? 'text-success' : 'text-danger'}">$${formatearNumero(saldos.saldo || 0)}</div>
            </div>
        </div>
        <div class="col-12 mt-3">
            <div class="border rounded p-2" style="background-color: #f8f9fa;">
                <div class="mb-1">
                    <small class="text-muted">
                        <strong>Monto del Contrato:</strong> $${formatearNumero(saldos.monto_contrato || 0)}
                    </small>
                </div>
                <div>
                    <small class="text-muted">
                        <strong>% Avance Financiero:</strong> ${(() => {
                            const montoTotal = parseFloat(saldos.monto_contrato || 0);
                            const avance = parseFloat(saldos.comprometido || 0) + parseFloat(saldos.ejecutado || 0);
                            if (montoTotal > 0) {
                                const porcentaje = (avance / montoTotal) * 100;
                                return porcentaje.toFixed(2) + '%';
                            }
                            return '0.00%';
                        })()}
                    </small>
                </div>
            </div>
        </div>
    `;
    document.getElementById('saldos-contrato').innerHTML = html;
}

function mostrarOrdenesCompra(ordenesCompra) {
    if (!ordenesCompra || ordenesCompra.length === 0) {
        document.getElementById('ordenes-compra-lista').innerHTML = '<p class="text-muted text-center py-3">No hay órdenes de compra asociadas.</p>';
        return;
    }
    
    let html = '';
    ordenesCompra.forEach(function(oc, index) {
        const ocId = 'oc-' + oc.id;
        html += `
            <div class="accordion-item mb-2">
                <h2 class="accordion-header" id="heading-${ocId}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${ocId}" aria-expanded="false">
                        <div class="d-flex justify-content-between w-100 me-3">
                            <span><strong>OC #${oc.numero || '-'}</strong> - ${oc.fecha || '-'}</span>
                            <span class="badge bg-${oc.estado === 'Pagado' ? 'success' : oc.estado === 'Pendiente' ? 'warning' : 'secondary'} ms-2">${oc.estado || '-'}</span>
                        </div>
                    </button>
                </h2>
                <div id="collapse-${ocId}" class="accordion-collapse collapse" aria-labelledby="heading-${ocId}">
                    <div class="accordion-body">
                        <div class="mb-3">
                            <strong>Monto Total:</strong> $${formatearNumero(oc.monto_total || 0)}<br>
                            ${oc.descripcion ? `<strong>Descripción:</strong> ${oc.descripcion}<br>` : ''}
                        </div>
                        ${oc.ordenes_trabajo && oc.ordenes_trabajo.length > 0 ? `
                        <h6 class="mb-2">Órdenes de Trabajo (${oc.ordenes_trabajo.length}):</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th># OT</th>
                                        <th>Fecha</th>
                                        <th>Establecimiento</th>
                                        <th>Comuna</th>
                                        <th>Medida</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${oc.ordenes_trabajo.map(function(ot) {
                                        return `
                                            <tr style="cursor: pointer;" onclick="mostrarPresupuestoOT(${ot.id})" ${ot.tiene_presupuesto ? 'title="Click para ver presupuesto"' : 'title="No tiene presupuesto"'} class="${ot.tiene_presupuesto ? 'table-hover' : ''}">
                                                <td>
                                                    ${ot.numero_ot || '-'}
                                                    ${ot.tiene_presupuesto ? '<i class="bi bi-file-earmark-spreadsheet text-primary ms-1" title="Tiene presupuesto"></i>' : ''}
                                                </td>
                                                <td>${ot.fecha_ot || '-'}</td>
                                                <td>${ot.establecimiento || '-'}</td>
                                                <td>${ot.comuna || '-'}</td>
                                                <td>${ot.medida || '-'}</td>
                                                <td class="text-end">$${formatearNumero(ot.monto || 0)}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary">
                                        <td colspan="5" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">$${formatearNumero(oc.ordenes_trabajo.reduce((sum, ot) => sum + (parseFloat(ot.monto) || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        ` : '<p class="text-muted">No hay órdenes de trabajo asociadas.</p>'}
                    </div>
                </div>
            </div>
        `;
    });
    
    document.getElementById('ordenes-compra-lista').innerHTML = '<div class="accordion" id="accordionOC">' + html + '</div>';
}

function mostrarOTsSinOC(otsSinOC) {
    if (!otsSinOC || otsSinOC.length === 0) {
        document.getElementById('ots-sin-oc-lista').innerHTML = '<p class="text-muted text-center py-3">No hay órdenes de trabajo sin OC.</p>';
        return;
    }
    
    const html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th># OT</th>
                        <th>Fecha</th>
                        <th>Establecimiento</th>
                        <th>Comuna</th>
                        <th>Medida</th>
                        <th class="text-end">Monto</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    ${otsSinOC.map(function(ot) {
                        return `
                            <tr style="cursor: pointer;" onclick="mostrarPresupuestoOT(${ot.id})" ${ot.tiene_presupuesto ? 'title="Click para ver presupuesto"' : 'title="No tiene presupuesto"'} class="${ot.tiene_presupuesto ? 'table-hover' : ''}">
                                <td>
                                    ${ot.numero_ot || '-'}
                                    ${ot.tiene_presupuesto ? '<i class="bi bi-file-earmark-spreadsheet text-primary ms-1" title="Tiene presupuesto"></i>' : ''}
                                </td>
                                <td>${ot.fecha_ot || '-'}</td>
                                <td>${ot.establecimiento || '-'}</td>
                                <td>${ot.comuna || '-'}</td>
                                <td>${ot.medida || '-'}</td>
                                <td class="text-end">$${formatearNumero(ot.monto || 0)}</td>
                                <td>${ot.observacion || '-'}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <td colspan="5" class="text-end fw-bold">Total:</td>
                        <td class="text-end fw-bold">$${formatearNumero(otsSinOC.reduce((sum, ot) => sum + (parseFloat(ot.monto) || 0), 0))}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    document.getElementById('ots-sin-oc-lista').innerHTML = html;
}

// Función para mostrar presupuesto de OT
function mostrarPresupuestoOT(otId) {
    // Mostrar la sección de presupuesto dentro del modal del contrato
    const seccionPresupuesto = document.getElementById('seccion-presupuesto-ot');
    seccionPresupuesto.style.display = 'block';
    
    // Mostrar loading
    document.getElementById('presupuesto-ot-contenido').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando presupuesto...</p></div>';
    
    // Scroll a la sección de presupuesto
    seccionPresupuesto.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    fetch('/ordenes-trabajo/' + otId + '/presupuesto', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarPresupuestoContenido(data);
        } else {
            document.getElementById('presupuesto-ot-contenido').innerHTML = 
                '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'No se pudo cargar el presupuesto') + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('presupuesto-ot-contenido').innerHTML = 
            '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Error al cargar el presupuesto</div>';
    });
}

// Función para cerrar la sección de presupuesto
function cerrarPresupuestoOT() {
    document.getElementById('seccion-presupuesto-ot').style.display = 'none';
    document.getElementById('presupuesto-ot-contenido').innerHTML = '';
}

function mostrarPresupuestoContenido(data) {
    const orden = data.orden;
    const presupuesto = data.presupuesto;
    const items = data.items || [];
    
    let html = '';
    
    // Información de la OT
    html += `
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Información de la Orden de Trabajo</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6"><strong># OT:</strong> ${orden.numero_ot || '-'}</div>
                    <div class="col-md-6"><strong>Fecha:</strong> ${orden.fecha_ot || '-'}</div>
                    ${orden.establecimiento ? `
                    <div class="col-md-6"><strong>Establecimiento:</strong> ${orden.establecimiento.nombre || '-'}</div>
                    <div class="col-md-6"><strong>RBD:</strong> ${orden.establecimiento.rbd || '-'}</div>
                    ` : ''}
                    ${orden.comuna ? `<div class="col-md-6"><strong>Comuna:</strong> ${orden.comuna.nombre || '-'}</div>` : ''}
                    ${orden.contrato ? `
                    <div class="col-md-6"><strong>Contrato:</strong> ${orden.contrato.nombre_contrato || '-'}</div>
                    <div class="col-md-6"><strong>Proveedor:</strong> ${orden.contrato.proveedor || '-'}</div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    if (!presupuesto || items.length === 0) {
        html += '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Esta orden de trabajo no tiene presupuesto registrado.</div>';
    } else {
        // Información del presupuesto
        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">Información del Presupuesto</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Fecha:</strong> ${presupuesto.fecha || '-'}</div>
                        ${presupuesto.usuario ? `
                        <div class="col-md-6"><strong>Usuario:</strong> ${presupuesto.usuario.nombre || '-'}</div>
                        ${presupuesto.usuario.cargo ? `<div class="col-md-6"><strong>Cargo:</strong> ${presupuesto.usuario.cargo || '-'}</div>` : ''}
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Tabla de items
        html += `
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">Partidas del Presupuesto</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Item</th>
                                    <th>Partida</th>
                                    <th>N° Partida</th>
                                    <th>Unidad</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(function(item) {
                                    return `
                                        <tr>
                                            <td>${item.item || '-'}</td>
                                            <td>${item.partida || '-'}</td>
                                            <td>${item.numero_partida || '-'}</td>
                                            <td>${item.unidad || '-'}</td>
                                            <td class="text-end">${formatearNumero(item.cantidad || 0)}</td>
                                            <td class="text-end">$${formatearNumero(item.precio || 0)}</td>
                                            <td class="text-end fw-bold">$${formatearNumero(item.total || 0)}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <td colspan="6" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">$${formatearNumero(items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0))}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('presupuesto-ot-contenido').innerHTML = html;
}

function filtrarPorAnioSaldos(anio) {
    const url = new URL(window.location.href);
    if (anio === 'todos') {
        url.searchParams.set('anio', 'todos');
    } else if (anio) {
        // Siempre establecer el parámetro anio, incluso si es el año actual
        url.searchParams.set('anio', anio);
    } else {
        url.searchParams.delete('anio');
    }
    window.location.href = url.toString();
}
</script>
@endsection
