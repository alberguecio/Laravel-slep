@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-file-earmark-bar-graph"></i> Reportes y Visualizaciones
        </h4>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-4" id="reportesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="metricas-tab" data-bs-toggle="tab" data-bs-target="#metricas" type="button" role="tab">
                <i class="bi bi-speedometer2"></i> Métricas Principales
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="graficos-tab" data-bs-toggle="tab" data-bs-target="#graficos" type="button" role="tab">
                <i class="bi bi-bar-chart"></i> Gráficos y Visualizaciones
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tendencias-tab" data-bs-toggle="tab" data-bs-target="#tendencias" type="button" role="tab">
                <i class="bi bi-graph-up-arrow"></i> Tendencias y Proyección
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="alertas-tab" data-bs-toggle="tab" data-bs-target="#alertas" type="button" role="tab">
                <i class="bi bi-bell"></i> Alertas y Notificaciones
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="filtros-tab" data-bs-toggle="tab" data-bs-target="#filtros" type="button" role="tab">
                <i class="bi bi-funnel"></i> Filtros
            </button>
        </li>
    </ul>

    <div class="tab-content" id="reportesTabContent">
        <!-- Pestaña 1: Métricas Principales -->
        <div class="tab-pane fade show active" id="metricas" role="tabpanel">
            <div class="row g-3">
                <!-- Requerimientos -->
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Requerimientos</h6>
                                    <h3 class="mb-0">{{ $metricas['requerimientos']['total'] }}</h3>
                                </div>
                                <div class="text-primary" style="font-size: 2.5rem;">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Pendientes</small>
                                    <strong class="text-warning">{{ $metricas['requerimientos']['pendientes'] }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">En Proceso</small>
                                    <strong class="text-info">{{ $metricas['requerimientos']['en_proceso'] }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Resueltos</small>
                                    <strong class="text-success">{{ $metricas['requerimientos']['resueltos'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Órdenes de Trabajo -->
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Órdenes de Trabajo</h6>
                                    <h3 class="mb-0">{{ $metricas['ordenes_trabajo']['total'] }}</h3>
                                </div>
                                <div class="text-info" style="font-size: 2.5rem;">
                                    <i class="bi bi-tools"></i>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted d-block">Sin OC</small>
                                    <strong class="text-warning">{{ $metricas['ordenes_trabajo']['sin_oc'] }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Con OC</small>
                                    <strong class="text-success">{{ $metricas['ordenes_trabajo']['con_oc'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Órdenes de Compra -->
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Órdenes de Compra</h6>
                                    <h3 class="mb-0">{{ $metricas['ordenes_compra']['total'] }}</h3>
                                </div>
                                <div class="text-success" style="font-size: 2.5rem;">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted d-block">Pagadas</small>
                                    <strong class="text-success">{{ $metricas['ordenes_compra']['pagadas'] }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Pendientes</small>
                                    <strong class="text-warning">{{ $metricas['ordenes_compra']['pendientes'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Presupuesto -->
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Presupuesto</h6>
                                    <h5 class="mb-0">${{ number_format($metricas['presupuesto']['total'], 0, ',', '.') }}</h5>
                                </div>
                                <div class="text-primary" style="font-size: 2.5rem;">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Comprometido:</span>
                                    <strong>${{ number_format($metricas['presupuesto']['comprometido'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Ejecutado:</span>
                                    <strong>${{ number_format($metricas['presupuesto']['ejecutado'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Facturado:</span>
                                    <strong>${{ number_format($metricas['presupuesto']['facturado'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Saldo:</span>
                                    <strong class="{{ $metricas['presupuesto']['saldo_disponible'] < 0 ? 'text-danger' : 'text-success' }}">
                                        ${{ number_format($metricas['presupuesto']['saldo_disponible'], 0, ',', '.') }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablas de Datos dentro de Métricas Principales -->
            <!-- Top 5 - Arriba -->
            <div class="row g-4 mt-2">
                <!-- Top 5 Comunas -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Comunas con Más Requerimientos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Comuna</th>
                                            <th class="text-end">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tablasDatos['top_comunas'] as $index => $comuna)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $comuna->nombre }}</td>
                                            <td class="text-end"><span class="badge bg-primary">{{ $comuna->requerimientos_count }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Contratos Más Activos -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> Top 5 Contratos Más Activos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Contrato</th>
                                            <th class="text-end">OTs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tablasDatos['contratos_activos'] as $index => $contrato)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ Str::limit($contrato->nombre_contrato, 40) }}</td>
                                            <td class="text-end"><span class="badge bg-info">{{ $contrato->ordenes_trabajo_count }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Establecimientos con Más OTs -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-building"></i> Top 5 Establecimientos con Más OTs</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Establecimiento</th>
                                            <th class="text-end">OTs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tablasDatos['top_establecimientos'] as $index => $establecimiento)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ Str::limit($establecimiento->nombre, 40) }}</td>
                                            <td class="text-end"><span class="badge bg-success">{{ $establecimiento->ordenes_trabajo_count }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas y Urgentes - Abajo -->
            <div class="row g-4 mt-2">
                <!-- Emergencias Pendientes -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Requerimientos de Emergencia Pendientes</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="sticky-top bg-white">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Comuna</th>
                                            <th>Establecimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tablasDatos['emergencias_pendientes'] as $requerimiento)
                                        <tr class="table-danger">
                                            <td>{{ $requerimiento->fecha_ingreso ? $requerimiento->fecha_ingreso->format('d/m/Y') : '-' }}</td>
                                            <td>{{ $requerimiento->comuna ? $requerimiento->comuna->nombre : '-' }}</td>
                                            <td>{{ $requerimiento->establecimiento ? $requerimiento->establecimiento->nombre : '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No hay emergencias pendientes</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OTs Comprometidas -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bi bi-clock-history"></i> OTs Comprometidas (Sin OC) - Más Antiguas</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="sticky-top bg-white">
                                        <tr>
                                            <th>Fecha OT</th>
                                            <th>Comuna</th>
                                            <th>Establecimiento</th>
                                            <th class="text-end">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tablasDatos['ots_comprometidas'] as $ot)
                                        <tr>
                                            <td>{{ $ot->fecha_ot ? $ot->fecha_ot->format('d/m/Y') : '-' }}</td>
                                            <td>{{ $ot->comuna ? $ot->comuna->nombre : '-' }}</td>
                                            <td>{{ $ot->establecimiento ? $ot->establecimiento->nombre : '-' }}</td>
                                            <td class="text-end">${{ number_format($ot->monto, 0, ',', '.') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No hay OTs comprometidas</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Gráficos y Visualizaciones -->
        <div class="tab-pane fade" id="graficos" role="tabpanel">
            <div class="row g-4">
                <!-- Gráfico: Requerimientos por Estado -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Requerimientos por Estado</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 250px;">
                                <canvas id="chartRequerimientosEstado"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico: Presupuesto por Fuente -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-pie-chart-fill"></i> Presupuesto por Fuente de Financiamiento</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 250px;">
                                <canvas id="chartPresupuestoFuente"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico: Gasto por Comuna/Establecimiento -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> <span id="tituloGraficoGasto">Gasto por Comuna</span></h6>
                        </div>
                        <div class="card-body">
                            <!-- Filtro por Comuna -->
                            <div class="mb-3">
                                <label for="filtroComunaGasto" class="form-label small">Filtrar por Comuna:</label>
                                <select class="form-select form-select-sm" id="filtroComunaGasto">
                                    <option value="">Todas las comunas</option>
                                    @foreach($comunas as $comuna)
                                        <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="height: 400px;">
                                <canvas id="chartGastoEstablecimiento"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico: Tendencias Mensuales -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-graph-up"></i> Tendencias Mensuales (Últimos 6 Meses)</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 400px;">
                                <canvas id="chartTendenciasMensuales"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 3: Tendencias y Proyección -->
        <div class="tab-pane fade" id="tendencias" role="tabpanel">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Tendencias de Gasto y Proyección Anual</h6>
                        </div>
                        <div class="card-body">
                            @if($tendenciasProyeccion['primera_ot'])
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i> Primera OT registrada: {{ $tendenciasProyeccion['primera_ot'] }}
                                    </small>
                                </div>
                                <div style="height: 400px;">
                                    <canvas id="chartTendenciasProyeccion"></canvas>
                                </div>
                                
                                <!-- Análisis de Proyección -->
                                <div class="mt-4 p-3 rounded" style="background-color: #f8f9fa;">
                                    <h6 class="mb-3"><i class="bi bi-calculator"></i> Análisis de Proyección</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Gasto Acumulado</small>
                                                <strong class="text-primary">${{ number_format($tendenciasProyeccion['gasto_acumulado'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Presupuesto Total</small>
                                                <strong class="text-success">${{ number_format($tendenciasProyeccion['presupuesto_total'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Promedio Mensual Histórico</small>
                                                <strong class="text-info">${{ number_format($tendenciasProyeccion['promedio_mensual'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Proyección Anual</small>
                                                <strong class="text-primary">${{ number_format($tendenciasProyeccion['proyeccion_anual'], 0, ',', '.') }}</strong>
                                                <small class="text-muted d-block" style="font-size: 0.75em;">
                                                    (Promedio × 12)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Gasto Necesario Mensual</small>
                                                <strong class="text-warning">${{ number_format($tendenciasProyeccion['gasto_necesario_mensual'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Presupuesto Restante</small>
                                                <strong class="text-danger">${{ number_format($tendenciasProyeccion['presupuesto_restante'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center p-2 bg-white rounded">
                                                <small class="text-muted d-block">Meses Restantes</small>
                                                <strong class="text-secondary">{{ $tendenciasProyeccion['meses_restantes'] }} meses</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row">
                                        <div class="col-12">
                                            @if($tendenciasProyeccion['diferencia_mensual'] > 0)
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>Atención - Ritmo de gasto insuficiente:</strong> 
                                                    Para alcanzar el presupuesto total de <strong>${{ number_format($tendenciasProyeccion['presupuesto_total'], 0, ',', '.') }}</strong> a fin de año, 
                                                    se necesita gastar <strong>${{ number_format($tendenciasProyeccion['gasto_necesario_mensual'], 0, ',', '.') }}</strong> mensualmente en los próximos 
                                                    <strong>{{ $tendenciasProyeccion['meses_restantes'] }} meses</strong>. 
                                                    El promedio histórico es de <strong>${{ number_format($tendenciasProyeccion['promedio_mensual'], 0, ',', '.') }}</strong>, 
                                                    por lo que se requiere aumentar el ritmo de gasto en <strong>${{ number_format($tendenciasProyeccion['diferencia_mensual'], 0, ',', '.') }}</strong> mensuales.
                                                </div>
                                            @elseif($tendenciasProyeccion['diferencia_mensual'] < 0)
                                                <div class="alert alert-success">
                                                    <i class="bi bi-check-circle-fill"></i> <strong>Ritmo de gasto adecuado:</strong> 
                                                    El promedio histórico de gasto (${{ number_format($tendenciasProyeccion['promedio_mensual'], 0, ',', '.') }}) 
                                                    es superior al necesario (${{ number_format($tendenciasProyeccion['gasto_necesario_mensual'], 0, ',', '.') }}) 
                                                    para alcanzar el presupuesto total de <strong>${{ number_format($tendenciasProyeccion['presupuesto_total'], 0, ',', '.') }}</strong> a fin de año. 
                                                    Manteniendo este ritmo, se logrará gastar todo el presupuesto.
                                                </div>
                                            @else
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle-fill"></i> <strong>Ritmo de gasto ajustado:</strong> 
                                                    El promedio histórico de gasto (${{ number_format($tendenciasProyeccion['promedio_mensual'], 0, ',', '.') }}) 
                                                    coincide exactamente con el gasto necesario mensual para alcanzar el presupuesto total a fin de año.
                                                </div>
                                            @endif
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> Meses con datos: {{ $tendenciasProyeccion['meses_transcurridos'] }} | 
                                                    Porcentaje del presupuesto utilizado hasta ahora: <strong>{{ number_format($tendenciasProyeccion['porcentaje_utilizado'], 1) }}%</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> No hay datos de Órdenes de Trabajo para realizar la proyección.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 4: Alertas y Notificaciones -->
        <div class="tab-pane fade" id="alertas" role="tabpanel">
            <div class="row g-3">
                @forelse($alertas as $alerta)
                <div class="col-12">
                    <div class="alert alert-{{ $alerta['tipo'] }} alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-{{ $alerta['icono'] }} me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>{{ $alerta['titulo'] }}</strong>
                            <div>{{ $alerta['mensaje'] }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>¡Todo en orden!</strong> No hay alertas pendientes en este momento.
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Pestaña 5: Filtros -->
        <div class="tab-pane fade" id="filtros" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-funnel-fill"></i> Filtros de Reportes</h5>
                </div>
                <div class="card-body">
                    <form id="formFiltrosReportes" method="GET" action="{{ route('reportes.index') }}" onsubmit="aplicarFiltrosYVolverAMetricas(event)">
                        <div class="row g-3">
                            <!-- Filtro por Año -->
                            <div class="col-md-6 col-lg-3">
                                <label for="filtro_anio" class="form-label fw-bold">
                                    <i class="bi bi-calendar"></i> Año
                                </label>
                                <select class="form-select" id="filtro_anio" name="anio">
                                    <option value="">Todos los años</option>
                                    @foreach($añosDisponibles as $año)
                                        <option value="{{ $año }}" {{ request('anio') == $año ? 'selected' : '' }}>
                                            {{ $año }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Comuna -->
                            <div class="col-md-6 col-lg-3">
                                <label for="filtro_comuna" class="form-label fw-bold">
                                    <i class="bi bi-geo-alt"></i> Comuna
                                </label>
                                <select class="form-select" id="filtro_comuna" name="comuna_id">
                                    <option value="">Todas las comunas</option>
                                    @foreach($comunas as $comuna)
                                        <option value="{{ $comuna->id }}" {{ request('comuna_id') == $comuna->id ? 'selected' : '' }}>
                                            {{ $comuna->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Establecimiento -->
                            <div class="col-md-6 col-lg-3">
                                <label for="filtro_establecimiento" class="form-label fw-bold">
                                    <i class="bi bi-building"></i> Establecimiento
                                </label>
                                <select class="form-select" id="filtro_establecimiento" name="establecimiento_id">
                                    <option value="">Todos los establecimientos</option>
                                    @foreach($establecimientos as $establecimiento)
                                        <option value="{{ $establecimiento->id }}" 
                                            data-comuna-id="{{ $establecimiento->comuna_id }}"
                                            {{ request('establecimiento_id') == $establecimiento->id ? 'selected' : '' }}>
                                            {{ $establecimiento->nombre }}
                                            @if($establecimiento->rbd)
                                                (RBD: {{ $establecimiento->rbd }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Se filtra automáticamente según la comuna seleccionada</small>
                            </div>

                            <!-- Filtro por Contrato -->
                            <div class="col-md-6 col-lg-3">
                                <label for="filtro_contrato" class="form-label fw-bold">
                                    <i class="bi bi-file-earmark-text"></i> Contrato
                                </label>
                                <select class="form-select" id="filtro_contrato" name="contrato_id">
                                    <option value="">Todos los contratos</option>
                                    @foreach($contratos as $contrato)
                                        <option value="{{ $contrato->id }}" {{ request('contrato_id') == $contrato->id ? 'selected' : '' }}>
                                            {{ $contrato->nombre_contrato }}
                                            @if($contrato->numero_contrato)
                                                - {{ $contrato->numero_contrato }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtro por Item -->
                            <div class="col-md-6 col-lg-3">
                                <label for="filtro_item" class="form-label fw-bold">
                                    <i class="bi bi-tag"></i> Item
                                </label>
                                <select class="form-select" id="filtro_item" name="item_id">
                                    <option value="">Todos los items</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Aplicar Filtros
                                    </button>
                                    <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary" onclick="limpiarFiltrosYVolverAMetricas(event)">
                                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                                    </a>
                                    <button type="button" class="btn btn-outline-info" onclick="exportarFiltros()">
                                        <i class="bi bi-download"></i> Exportar Resultados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Resumen de filtros activos -->
                    @if(request()->hasAny(['anio', 'comuna_id', 'establecimiento_id', 'contrato_id', 'item_id']))
                    <div class="alert alert-info mt-4">
                        <h6 class="mb-2"><i class="bi bi-info-circle"></i> Filtros Activos:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @if(request('anio'))
                                <span class="badge bg-primary">Año: {{ request('anio') }}</span>
                            @endif
                            @if(request('comuna_id'))
                                @php
                                    $comunaSeleccionada = $comunas->firstWhere('id', request('comuna_id'));
                                @endphp
                                @if($comunaSeleccionada)
                                    <span class="badge bg-primary">Comuna: {{ $comunaSeleccionada->nombre }}</span>
                                @endif
                            @endif
                            @if(request('establecimiento_id'))
                                @php
                                    $establecimientoSeleccionado = $establecimientos->firstWhere('id', request('establecimiento_id'));
                                @endphp
                                @if($establecimientoSeleccionado)
                                    <span class="badge bg-primary">Establecimiento: {{ $establecimientoSeleccionado->nombre }}</span>
                                @endif
                            @endif
                            @if(request('contrato_id'))
                                @php
                                    $contratoSeleccionado = $contratos->firstWhere('id', request('contrato_id'));
                                @endphp
                                @if($contratoSeleccionado)
                                    <span class="badge bg-primary">Contrato: {{ $contratoSeleccionado->nombre_contrato }}</span>
                                @endif
                            @endif
                            @if(request('item_id'))
                                @php
                                    $itemSeleccionado = $items->firstWhere('id', request('item_id'));
                                @endphp
                                @if($itemSeleccionado)
                                    <span class="badge bg-primary">Item: {{ $itemSeleccionado->nombre }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <!-- Resultados Filtrados -->
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Resultados con Filtros Aplicados</h5>
                        </div>
                        <div class="card-body">
                            <!-- Métricas Principales Filtradas -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Requerimientos</h6>
                                            <h3 class="mb-0 text-primary">{{ number_format($metricas['requerimientos']['total'] ?? 0) }}</h3>
                                            <small class="text-muted">
                                                Pendientes: {{ $metricas['requerimientos']['pendientes'] ?? 0 }} | 
                                                En Proceso: {{ $metricas['requerimientos']['en_proceso'] ?? 0 }} | 
                                                Resueltos: {{ $metricas['requerimientos']['resueltos'] ?? 0 }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Órdenes de Trabajo</h6>
                                            <h3 class="mb-0 text-warning">{{ number_format($metricas['ordenes_trabajo']['total'] ?? 0) }}</h3>
                                            <small class="text-muted">
                                                Sin OC: {{ $metricas['ordenes_trabajo']['sin_oc'] ?? 0 }} | 
                                                Con OC: {{ $metricas['ordenes_trabajo']['con_oc'] ?? 0 }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Órdenes de Compra</h6>
                                            <h3 class="mb-0 text-info">{{ number_format($metricas['ordenes_compra']['total'] ?? 0) }}</h3>
                                            <small class="text-muted">
                                                Pagadas: {{ $metricas['ordenes_compra']['pagadas'] ?? 0 }} | 
                                                Pendientes: {{ $metricas['ordenes_compra']['pendientes'] ?? 0 }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-2">Total Filtrado</h6>
                                            <h3 class="mb-0 text-success">${{ number_format(($metricas['presupuesto']['comprometido'] ?? 0) + ($metricas['presupuesto']['ejecutado'] ?? 0), 0, ',', '.') }}</h3>
                                            <small class="text-muted">
                                                Comprometido: ${{ number_format($metricas['presupuesto']['comprometido'] ?? 0, 0, ',', '.') }} | 
                                                Ejecutado: ${{ number_format($metricas['presupuesto']['ejecutado'] ?? 0, 0, ',', '.') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Listado de OCs Filtradas -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">
                                                <i class="bi bi-file-earmark-text"></i> Órdenes de Compra ({{ $ocsFiltradas->count() }})
                                            </h6>
                                        </div>
                                        <div class="card-body p-0">
                                            @forelse($ocsFiltradas as $oc)
                                                <div class="border-bottom oc-item" data-oc-id="{{ $oc->id }}">
                                                    <div class="p-3 oc-header" style="cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor=''" onclick="toggleOTs({{ $oc->id }})">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <i class="bi bi-chevron-right oc-chevron" id="chevron-{{ $oc->id }}"></i>
                                                                    <strong class="text-primary">OC #{{ $oc->numero }}</strong>
                                                                    @if($oc->contrato)
                                                                        <span class="badge bg-info">{{ $oc->contrato->nombre_contrato }}</span>
                                                                    @endif
                                                                    @if($oc->oferente)
                                                                        <span class="text-muted">- {{ $oc->oferente->nombre ?? 'N/A' }}</span>
                                                                    @endif
                                                                </div>
                                                                <div class="mt-2 ms-4">
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-calendar"></i> Fecha: {{ $oc->fecha ? $oc->fecha->format('d/m/Y') : 'N/A' }} | 
                                                                        <i class="bi bi-cash-coin"></i> Monto OC: ${{ number_format($oc->monto_total ?? 0, 0, ',', '.') }} | 
                                                                        <i class="bi bi-file-check"></i> Estado: <span class="badge bg-{{ $oc->estado == 'Pagado' ? 'success' : 'warning' }}">{{ $oc->estado ?? 'Pendiente' }}</span>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <div class="text-end">
                                                                <span class="badge bg-secondary">
                                                                    {{ $oc->total_ots_filtradas ?? 0 }} OT(s) | 
                                                                    ${{ number_format($oc->monto_total_ots_filtradas ?? 0, 0, ',', '.') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="ots-container" id="ots-{{ $oc->id }}" style="display: none;">
                                                        <div class="p-3 bg-light">
                                                            <h6 class="mb-3">
                                                                <i class="bi bi-list-ul"></i> Órdenes de Trabajo ({{ $oc->total_ots_filtradas ?? 0 }})
                                                            </h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-hover table-bordered">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>N° OT</th>
                                                                            <th>Fecha OT</th>
                                                                            <th>Comuna</th>
                                                                            <th>Establecimiento</th>
                                                                            <th>Contrato</th>
                                                                            <th class="text-end">Monto</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse($oc->ots_filtradas ?? [] as $ot)
                                                                            <tr>
                                                                                <td><strong>{{ $ot->numero_ot ?? 'N/A' }}</strong></td>
                                                                                <td>{{ $ot->fecha_ot ? $ot->fecha_ot->format('d/m/Y') : 'N/A' }}</td>
                                                                                <td>{{ $ot->comuna->nombre ?? 'N/A' }}</td>
                                                                                <td>{{ $ot->establecimiento->nombre ?? 'N/A' }}</td>
                                                                                <td>{{ $ot->contrato->nombre_contrato ?? 'N/A' }}</td>
                                                                                <td class="text-end">${{ number_format($ot->monto ?? 0, 0, ',', '.') }}</td>
                                                                            </tr>
                                                                        @empty
                                                                            <tr>
                                                                                <td colspan="6" class="text-center text-muted">No hay OTs que cumplan los filtros</td>
                                                                            </tr>
                                                                        @endforelse
                                                                    </tbody>
                                                                    @if($oc->ots_filtradas && $oc->ots_filtradas->count() > 0)
                                                                        <tfoot class="table-secondary">
                                                                            <tr>
                                                                                <td colspan="5" class="text-end fw-bold">Total:</td>
                                                                                <td class="text-end fw-bold">${{ number_format($oc->monto_total_ots_filtradas ?? 0, 0, ',', '.') }}</td>
                                                                            </tr>
                                                                        </tfoot>
                                                                    @endif
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="p-4 text-center text-muted">
                                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                                    <p class="mt-2">No hay Órdenes de Compra que cumplan los filtros seleccionados</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para gráficos
    const requerimientosPorEstado = @json($datosGraficos['requerimientos_por_estado']);
    const presupuestoPorFuente = @json($datosGraficos['presupuesto_por_fuente']);
    const tendenciasMensuales = @json($datosGraficos['tendencias_mensuales']);
    const gastoPorComuna = @json($gastoPorComuna);
    const tendenciasProyeccion = @json($tendenciasProyeccion);
    
    let chartGastoEstablecimiento = null;
    let chartTendenciasProyeccion = null;
    
    // Gráfico: Requerimientos por Estado (Doughnut)
    const ctxRequerimientos = document.getElementById('chartRequerimientosEstado');
    if (ctxRequerimientos) {
        new Chart(ctxRequerimientos, {
            type: 'doughnut',
            data: {
                labels: Object.keys(requerimientosPorEstado),
                datasets: [{
                    data: Object.values(requerimientosPorEstado),
                    backgroundColor: ['#ffc107', '#0dcaf0', '#198754']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico: Presupuesto por Fuente (Pie)
    const ctxPresupuesto = document.getElementById('chartPresupuestoFuente');
    if (ctxPresupuesto) {
        new Chart(ctxPresupuesto, {
            type: 'pie',
            data: {
                labels: presupuestoPorFuente.map(f => f.nombre),
                datasets: [{
                    data: presupuestoPorFuente.map(f => f.monto),
                    backgroundColor: [
                        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0',
                        '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + context.parsed.toLocaleString('es-CL');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico: Tendencias Mensuales (Line)
    const ctxTendencias = document.getElementById('chartTendenciasMensuales');
    if (ctxTendencias) {
        new Chart(ctxTendencias, {
            type: 'line',
            data: {
                labels: tendenciasMensuales.meses,
                datasets: [
                    {
                        label: 'Requerimientos',
                        data: tendenciasMensuales.requerimientos,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Órdenes de Trabajo',
                        data: tendenciasMensuales.ots,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Órdenes de Compra',
                        data: tendenciasMensuales.ocs,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Gráfico: Gasto por Comuna/Establecimiento (Bar)
    const ctxGastoEstablecimiento = document.getElementById('chartGastoEstablecimiento');
    if (ctxGastoEstablecimiento) {
        function actualizarGraficoGasto(datos, tipo) {
            // Mostrar todos los datos (sin límite)
            const labels = datos.map(e => e.nombre.length > 20 ? e.nombre.substring(0, 20) + '...' : e.nombre);
            const gastos = datos.map(e => e.gasto);
            
            // Alternar colores: verde oscuro y verde claro
            const backgroundColor = datos.map((e, index) => {
                return index % 2 === 0 ? '#198754' : '#20c997'; // Verde oscuro y verde claro alternados
            });
            
            if (chartGastoEstablecimiento) {
                chartGastoEstablecimiento.destroy();
            }
            
            // Actualizar título
            const tituloGrafico = document.getElementById('tituloGraficoGasto');
            if (tituloGrafico) {
                tituloGrafico.textContent = tipo === 'establecimientos' ? 'Gasto por Establecimiento' : 'Gasto por Comuna';
            }
            
            chartGastoEstablecimiento = new Chart(ctxGastoEstablecimiento, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Gasto Total',
                        data: gastos,
                        backgroundColor: backgroundColor,
                        barThickness: 15,
                        maxBarThickness: 18,
                        categoryPercentage: 0.6,
                        barPercentage: 0.8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Gasto: $' + context.parsed.x.toLocaleString('es-CL');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-CL');
                                },
                                maxTicksLimit: 8
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Inicializar gráfico con comunas
        actualizarGraficoGasto(gastoPorComuna, 'comunas');
        
        // Filtro por comuna
        const filtroComunaGasto = document.getElementById('filtroComunaGasto');
        if (filtroComunaGasto) {
            filtroComunaGasto.addEventListener('change', function() {
                const comunaId = this.value;
                let url = '/reportes/gasto-establecimientos';
                if (comunaId) {
                    url += '?comuna_id=' + comunaId;
                }
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            actualizarGraficoGasto(data.datos, data.tipo);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        }
    }
    
    // Gráfico: Tendencias y Proyección
    const ctxTendenciasProyeccion = document.getElementById('chartTendenciasProyeccion');
    if (ctxTendenciasProyeccion && tendenciasProyeccion.primera_ot) {
        const meses = tendenciasProyeccion.meses_datos.map(m => m.mes);
        const gastosReales = tendenciasProyeccion.meses_datos.map(m => m.gasto);
        const promedioMensual = tendenciasProyeccion.promedio_mensual;
        const gastoNecesarioMensual = tendenciasProyeccion.gasto_necesario_mensual;
        
        // Crear array de proyección
        const mesesCompletos = [];
        const gastosRealesCompletos = [];
        const proyeccionLineal = []; // Línea azul recta de proyección desde la primera OT
        const proyeccionNecesaria = [];
        
        const mesActual = new Date().getMonth() + 1;
        const anoActual = new Date().getFullYear();
        
        // Calcular el gasto acumulado mes a mes (desde la primera OT)
        const gastosAcumulados = [];
        let acumulado = 0;
        for (let i = 0; i < gastosReales.length; i++) {
            acumulado += gastosReales[i] || 0;
            gastosAcumulados.push(acumulado);
        }
        
        // Obtener el número del último mes con datos reales
        let ultimoMesConDato = 0;
        if (tendenciasProyeccion.meses_datos && tendenciasProyeccion.meses_datos.length > 0) {
            ultimoMesConDato = tendenciasProyeccion.meses_datos[tendenciasProyeccion.meses_datos.length - 1].mes_numero;
        } else {
            ultimoMesConDato = mesActual;
        }
        
        // Meses con datos reales (desde la primera OT hasta el último mes con datos)
        for (let i = 0; i < meses.length; i++) {
            mesesCompletos.push(meses[i]);
            gastosRealesCompletos.push(gastosReales[i]);
            
            // La proyección lineal empieza desde enero (mes 1 del año)
            // Proyección = promedio_mensual × número_de_mes_del_año
            // El mes_numero ya indica el mes del año (1=enero, 2=febrero, etc.)
            const mesNumero = tendenciasProyeccion.meses_datos[i].mes_numero;
            const proyeccionAcumulada = promedioMensual * mesNumero;
            proyeccionLineal.push(proyeccionAcumulada);
            
            proyeccionNecesaria.push(null); // No mostrar proyección necesaria en meses pasados
        }
        
        // Meses proyectados (desde el mes siguiente al último con datos hasta diciembre)
        const mesSiguiente = ultimoMesConDato + 1;
        
        for (let i = mesSiguiente; i <= 12; i++) {
            const fecha = new Date(anoActual, i - 1, 1);
            mesesCompletos.push(fecha.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' }));
            gastosRealesCompletos.push(null); // No hay dato real
            
            // Proyección lineal: promedio mensual × número de mes del año
            // Enero (i=1): promedio × 1
            // Febrero (i=2): promedio × 2
            // Diciembre (i=12): promedio × 12
            const proyeccionAcumulada = promedioMensual * i;
            proyeccionLineal.push(proyeccionAcumulada);
            
            proyeccionNecesaria.push(gastoNecesarioMensual); // Gasto necesario para alcanzar presupuesto
        }
        
        chartTendenciasProyeccion = new Chart(ctxTendenciasProyeccion, {
            type: 'line',
            data: {
                labels: mesesCompletos,
                datasets: [
                    {
                        label: 'Gasto Real Mensual',
                        data: gastosRealesCompletos,
                        borderColor: '#6c757d', // Cambiado a gris
                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        spanGaps: false
                    },
                    {
                        label: 'Proyección Anual (Basada en promedio mensual)',
                        data: proyeccionLineal,
                        borderColor: '#0d6efd',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0, // Línea completamente recta
                        fill: false,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderDash: [0], // Línea sólida
                        spanGaps: false
                    },
                    {
                        label: 'Presupuesto Total',
                        data: mesesCompletos.map(() => tendenciasProyeccion.presupuesto_total),
                        borderColor: '#dc3545', // Rojo para el presupuesto
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0, // Línea recta horizontal
                        fill: false,
                        pointRadius: 0,
                        borderDash: [10, 5], // Línea punteada
                        spanGaps: false
                    },
                    {
                        label: 'Gasto Necesario Mensual (Para alcanzar presupuesto)',
                        data: proyeccionNecesaria,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderDash: [10, 5],
                        tension: 0,
                        pointRadius: 4,
                        pointStyle: 'circle',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.parsed.y === null) return null;
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CL');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-CL');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Filtro de establecimientos según comuna seleccionada
    const filtroComuna = document.getElementById('filtro_comuna');
    const filtroEstablecimiento = document.getElementById('filtro_establecimiento');
    
    if (filtroComuna && filtroEstablecimiento) {
        // Guardar todas las opciones de establecimientos
        const todasLasOpciones = Array.from(filtroEstablecimiento.options);
        
        filtroComuna.addEventListener('change', function() {
            const comunaId = this.value;
            
            // Limpiar opciones actuales (excepto la primera "Todos")
            filtroEstablecimiento.innerHTML = '<option value="">Todos los establecimientos</option>';
            
            if (comunaId) {
                // Filtrar y agregar solo establecimientos de la comuna seleccionada
                todasLasOpciones.forEach(option => {
                    if (option.value && option.dataset.comunaId == comunaId) {
                        filtroEstablecimiento.appendChild(option.cloneNode(true));
                    }
                });
            } else {
                // Si no hay comuna seleccionada, mostrar todos
                todasLasOpciones.forEach(option => {
                    if (option.value) {
                        filtroEstablecimiento.appendChild(option.cloneNode(true));
                    }
                });
            }
        });
        
        // Aplicar filtro inicial si hay una comuna seleccionada
        if (filtroComuna.value) {
            filtroComuna.dispatchEvent(new Event('change'));
        }
    }
    
    // Función para exportar resultados filtrados (placeholder)
    window.exportarFiltros = function() {
        alert('Función de exportación en desarrollo. Los filtros se aplicarán a los reportes cuando se implemente la funcionalidad completa.');
    }
    
    // Función para expandir/colapsar OTs de una OC
    window.toggleOTs = function(ocId) {
        const container = document.getElementById('ots-' + ocId);
        const chevron = document.getElementById('chevron-' + ocId);
        
        if (!container || !chevron) return;
        
        const isHidden = container.style.display === 'none' || !container.style.display;
        
        if (isHidden) {
            container.style.display = 'block';
            chevron.classList.remove('bi-chevron-right');
            chevron.classList.add('bi-chevron-down');
        } else {
            container.style.display = 'none';
            chevron.classList.remove('bi-chevron-down');
            chevron.classList.add('bi-chevron-right');
        }
    }
    
    
    // Función para aplicar filtros y quedarse en la pestaña de filtros
    window.aplicarFiltrosYVolverAMetricas = function(event) {
        event.preventDefault();
        const form = document.getElementById('formFiltrosReportes');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Agregar todos los parámetros del formulario
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }
        
        // Agregar parámetro para activar pestaña de filtros
        params.append('tab', 'filtros');
        
        // Redirigir con los parámetros
        window.location.href = form.action + '?' + params.toString();
    }
    
    // Función para limpiar filtros y quedarse en la pestaña de filtros
    window.limpiarFiltrosYVolverAMetricas = function(event) {
        event.preventDefault();
        window.location.href = '{{ route('reportes.index') }}?tab=filtros';
    }
    
    // Activar pestaña correspondiente si viene el parámetro tab
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const targetTab = document.getElementById(tabParam + '-tab');
        const targetPane = document.getElementById(tabParam);
        if (targetTab && targetPane) {
            // Remover active de todas las pestañas
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Activar pestaña objetivo
            targetTab.classList.add('active');
            targetPane.classList.add('show', 'active');
            
            // Remover el parámetro tab de la URL sin recargar
            urlParams.delete('tab');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    }
});
</script>
@endsection

