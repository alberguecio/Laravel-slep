@extends('layouts.app')

@section('content')
<style>
    /* Asegurar que el dropdown de búsqueda se muestre correctamente en el modal masivo */
    #modalOrdenMasiva .modal-body {
        overflow: visible !important;
    }
    #modalOrdenMasiva .table-responsive {
        overflow: visible !important;
    }
    #modalOrdenMasiva td {
        overflow: visible !important;
    }
    #modalOrdenMasiva .establecimiento-search-container {
        position: relative !important;
        overflow: visible !important;
    }
    #modalOrdenMasiva [id^="establecimiento-dropdown-"] {
        z-index: 99999 !important;
        position: absolute !important;
    }
    
    /* Ocultar spinners (flechas) de campos numéricos */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>
    <!-- Título y Botón Masivo -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-tools"></i> Gestión de Órdenes de Trabajo
            </h4>
        </div>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalOrdenMasiva">
            <i class="bi bi-plus-circle"></i> INGRESO MASIVO DE OTs
        </button>
    </div>

    <!-- Formulario Principal de OT con Precios Unitarios -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Ingreso de Orden de Trabajo (Precios Unitarios)</h5>
        </div>
        <div class="card-body">
            <form id="formOrdenTrabajo" action="{{ route('ordenes-trabajo.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">N° OT</label>
                        <input type="text" class="form-control" name="numero_ot" id="numero_ot" value="{{ $numeroOt ?? '' }}" readonly>
                        <small class="text-muted">Se genera automáticamente</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha *</label>
                        <input type="date" class="form-control" name="fecha_ot" id="fecha_ot" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Contrato *</label>
                        <select class="form-select" name="contrato_id" id="contrato_id" required>
                            <option value="">Haz clic para seleccionar un contrato</option>
                            @foreach($contratos as $contrato)
                            <option value="{{ $contrato->id }}" 
                                data-tiene-precios="{{ $contrato->tiene_precios ? '1' : '0' }}"
                                data-cantidad-precios="{{ $contrato->cantidad_precios ?? 0 }}"
                                data-saldo="{{ $contrato->monto_restante }}"
                                @if(isset($requerimientoContratoId) && $requerimientoContratoId == $contrato->id) selected @endif>
                                {{ $contrato->nombre_contrato }} - Saldo: $ {{ number_format($contrato->monto_restante, 0, ',', '.') }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="saldo_contrato_hint"></small>
                        <div id="infoPreciosContrato" class="mt-2" style="display: none;"></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Comuna *</label>
                        <select class="form-select" name="comuna_id" id="comuna_id" required onchange="filtrarEstablecimientosPorComuna(this.value)">
                            <option value="">Seleccionar...</option>
                            @foreach($comunas as $comuna)
                            <option value="{{ $comuna->id }}" @if(isset($requerimientoComunaId) && $requerimientoComunaId == $comuna->id) selected @endif>{{ $comuna->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Establecimiento *</label>
                        <select class="form-select" name="establecimiento_id" id="establecimiento_id" required onchange="actualizarRBDDesdeSelect()">
                            <option value="">Seleccionar...</option>
                            @foreach($establecimientos as $establecimiento)
                            <option value="{{ $establecimiento->id }}" 
                                data-comuna="{{ $establecimiento->comuna_id ?? '' }}"
                                data-rbd="{{ $establecimiento->rbd ?? '' }}"
                                data-nombre="{!! htmlspecialchars($establecimiento->nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) !!}"
                                @if(isset($requerimientoEstablecimientoId) && $requerimientoEstablecimientoId == $establecimiento->id) selected @endif>
                                {{ $establecimiento->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">RBD</label>
                        <input type="text" class="form-control" name="rbd" id="rbd" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Estado *</label>
                        <select class="form-select" name="estado" id="estado" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Enviado" selected>Enviado</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo *</label>
                        <select class="form-select" name="tipo" id="tipo" required>
                            <option value="Normal" selected>Normal</option>
                            <option value="Emergencia">Emergencia</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Medida</label>
                        <input type="text" class="form-control" name="medida" id="medida" maxlength="100">
                        <small class="text-muted">Opcional</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monto Total</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="monto_display" readonly>
                        </div>
                        <small class="text-muted">El monto se calcula automáticamente al agregar partidas al presupuesto</small>
                    </div>
                </div>

                <!-- Sección de Presupuesto -->
                <div id="seccionPresupuesto">
                    <div id="bannerPresupuesto" class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <strong><i class="bi bi-clipboard-check"></i> Crear Presupuesto</strong>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="mostrarModalPreciosUnitarios()" title="Ver listado completo de precios unitarios">
                                        <i class="bi bi-list-ul text-info" style="font-size: 1.2rem;"></i>
                                    </button>
                                    <div id="infoOtPresupuesto" class="small mt-1 ms-3">
                                        N° OT: <span id="numero_ot_display">{{ $numeroOt ?? '' }}</span> • Fecha: <span id="fecha_ot_display">{{ date('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <div id="infoPreciosCargados" class="d-none align-items-center">
                                    <span class="badge bg-success me-2" id="contadorPrecios">
                                        <i class="bi bi-check-circle"></i> <span id="cantidadPrecios">0</span> precios unitarios cargados
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-light" onclick="mostrarListaPreciosCompleta()" title="Ver lista completa de precios unitarios">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="agregarFilaPresupuesto()">
                            <i class="bi bi-plus-circle"></i> Agregar Partida
                        </button>
                    </div>
                    <div id="alertaSinPrecios" class="alert alert-warning d-none">
                        <i class="bi bi-exclamation-triangle"></i> Este contrato no tiene precios unitarios cargados. Por favor, carga los precios unitarios en el módulo de Contratos.
                    </div>
                    <!-- Datalist global para todas las partidas -->
                    <datalist id="datalistPartidas"></datalist>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 25%;">Partida</th>
                                    <th style="width: 10%;">Unidad</th>
                                    <th style="width: 15%;">Cantidad</th>
                                    <th style="width: 15%;">Precio Unitario</th>
                                    <th style="width: 15%;">Total</th>
                                    <th style="width: 5%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPresupuesto">
                                <!-- Las filas se agregan dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Sección de Totales -->
                    <div class="row mt-4">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong>Total Neto:</strong>
                                        <span id="total_neto_display">$ 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <label class="form-label mb-0 small">% IVA:</label>
                                            <span class="badge bg-secondary ms-2">19%</span>
                                            <input type="hidden" 
                                                   id="porcentaje_iva" 
                                                   name="porcentaje_iva"
                                                   value="19">
                                        </div>
                                        <span id="iva_display">$ 0</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong class="fs-5">TOTAL:</strong>
                                        <strong class="fs-5 text-primary" id="total_con_iva_display">$ 0</strong>
                                    </div>
                                    <!-- Campo oculto para enviar el monto total con IVA -->
                                    <input type="hidden" id="monto_total_con_iva" name="monto_total_con_iva">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> GUARDAR ORDEN DE TRABAJO Y PRESUPUESTO
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Orden Masiva -->
    <div class="modal fade" id="modalOrdenMasiva" tabindex="-1" aria-labelledby="modalOrdenMasivaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalOrdenMasivaLabel">
                        <i class="bi bi-plus-circle"></i> Ingreso Masivo de Órdenes de Trabajo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formOrdenMasiva" action="{{ route('ordenes-trabajo.store-masiva') }}" method="POST">
                    @csrf
                    <div class="modal-body" style="overflow: visible !important; position: relative;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contrato *</label>
                                <select class="form-select" name="contrato_id_masiva" id="contrato_id_masiva" required>
                                    <option value="">Seleccionar contrato...</option>
                                    @foreach($contratos as $contrato)
                                    <option value="{{ $contrato->id }}" data-saldo="{{ $contrato->monto_restante }}">
                                        {{ $contrato->nombre_contrato }} - Saldo: $ {{ number_format($contrato->monto_restante, 0, ',', '.') }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tipo *</label>
                                <select class="form-select" name="tipo_masiva" id="tipo_masiva" required>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="Emergencia">Emergencia</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Estado *</label>
                                <select class="form-select" name="estado_masiva" id="estado_masiva" required>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Enviado" selected>Enviado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fecha *</label>
                                <input type="date" class="form-control" name="fecha_masiva" id="fecha_masiva" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Monto Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="monto_total_masiva" value="0" readonly style="font-weight: bold; font-size: 1.1em;">
                                </div>
                                <small class="text-muted" id="saldo_contrato_masiva_hint"></small>
                                <div id="alerta_saldo_masiva" class="alert alert-danger mt-2 mb-0" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="mb-3" style="position: relative; overflow: visible;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">Establecimientos y Montos</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarFilaMasiva()">
                                    <i class="bi bi-plus-circle"></i> Agregar Establecimiento
                                </button>
                            </div>
                            <div class="table-responsive" style="overflow: visible !important; position: relative;">
                                <table class="table table-bordered table-sm" style="position: relative;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%;">Establecimiento</th>
                                            <th style="width: 25%;">Monto</th>
                                            <th style="width: 5%;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyMasiva" style="position: relative;">
                                        <!-- Las filas se agregan dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                        <button type="submit" class="btn btn-primary">GUARDAR ÓRDENES</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Precios Unitarios -->
    <div class="modal fade" id="modalPreciosUnitarios" tabindex="-1" aria-labelledby="modalPreciosUnitariosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalPreciosUnitariosLabel">
                        <i class="bi bi-list-ul"></i> Listado de Precios Unitarios
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Campo de búsqueda -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="buscarPreciosUnitarios" 
                                   placeholder="Buscar por número de partida, partida, unidad o precio..."
                                   onkeyup="filtrarPreciosUnitarios(this.value)">
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover table-sm table-striped">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>N° Partida</th>
                                    <th>Partida</th>
                                    <th>Unidad</th>
                                    <th class="text-end">Precio Unitario</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPreciosUnitarios">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CERRAR</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Órdenes Registradas -->
    <div class="card shadow" id="listadoOT">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Órdenes de Trabajo Registradas</h5>
                <form method="GET" action="{{ route('ordenes-trabajo.index') }}#listadoOT" class="d-flex" style="max-width: 400px;" id="formBusquedaOT">
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="busqueda" 
                           id="busqueda_ot"
                           value="{{ $busqueda ?? '' }}" 
                           placeholder="Buscar OT, establecimiento, comuna, contrato...">
                    <button type="submit" class="btn btn-sm btn-light ms-2">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(!empty($busqueda))
                    <a href="{{ route('ordenes-trabajo.index') }}#listadoOT" class="btn btn-sm btn-outline-light ms-2">
                        <i class="bi bi-x-circle"></i>
                    </a>
                    @endif
                </form>
            </div>
        </div>
        <div class="card-body">
            @if(!empty($busqueda))
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i> Mostrando resultados de búsqueda para: <strong>"{{ $busqueda }}"</strong>
                @if($ordenes->count() > 0)
                    ({{ $ordenes->count() }} resultado(s))
                @endif
            </div>
            @else
            <div class="text-muted mb-3">
                <i class="bi bi-info-circle"></i> Mostrando las últimas 15 órdenes de trabajo creadas
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N° OT</th>
                            <th>Fecha OT</th>
                            <th>Contrato</th>
                            <th>Establecimiento</th>
                            <th>Comuna</th>
                            <th class="text-end">Monto</th>
                            <th>Estado</th>
                            <th>Tipo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes as $orden)
                        <tr class="{{ $orden->tipo === 'Emergencia' ? 'table-warning' : '' }} cursor-pointer" 
                            style="cursor: pointer;" 
                            onclick="mostrarPresupuestoOt({{ $orden->id }})"
                            data-ot-id="{{ $orden->id }}">
                            <td>{{ $orden->numero_ot }}</td>
                            <td>{{ $orden->fecha_ot ? $orden->fecha_ot->format('d/m/Y') : '-' }}</td>
                            <td>{{ $orden->contrato ? $orden->contrato->nombre_contrato : '-' }}</td>
                            <td>{{ $orden->establecimiento ? $orden->establecimiento->nombre : '-' }}</td>
                            <td>
                                @if($orden->comuna)
                                    {{ $orden->comuna->nombre }}
                                @elseif($orden->establecimiento && $orden->establecimiento->comuna)
                                    {{ $orden->establecimiento->comuna->nombre }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">$ {{ number_format($orden->monto, 0, ',', '.') }}</td>
                            <td>
                                @if($orden->estado === 'Pendiente')
                                    <span class="badge bg-warning">Pendiente</span>
                                @elseif($orden->estado === 'Enviado')
                                    <span class="badge bg-info">Enviado</span>
                                @else
                                    <span class="badge bg-secondary">{{ $orden->estado }}</span>
                                @endif
                            </td>
                            <td>
                                @if($orden->tipo === 'Emergencia')
                                    <span class="badge bg-danger">Emergencia</span>
                                @else
                                    <span class="badge bg-primary">Normal</span>
                                @endif
                            </td>
                            <td class="text-end" onclick="event.stopPropagation();">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editarOrden({{ $orden->id }})" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarOrden({{ $orden->id }})" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                No hay órdenes de trabajo registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Presupuesto OT -->
    <div class="modal fade" id="modalPresupuestoOt" tabindex="-1" aria-labelledby="modalPresupuestoOtLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalPresupuestoOtLabel">
                        <i class="bi bi-file-earmark-text"></i> Presupuesto de Orden de Trabajo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Información del contrato y OT (arriba) -->
                    <div class="mb-4">
                        <div class="row small">
                            <div class="col-md-6">
                                <div class="mb-2" style="font-size: 1.1em; line-height: 1.5;"><strong>Contrato:</strong> <span id="presupuesto_contrato">-</span></div>
                                <div class="mb-2" style="line-height: 1.5;"><strong>Establecimiento:</strong> <span id="presupuesto_establecimiento">-</span></div>
                                <div class="mb-2" style="line-height: 1.5;"><strong>Comuna:</strong> <span id="presupuesto_comuna">-</span></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="mb-2" style="line-height: 1.5;"><strong>N° OT:</strong> <span id="presupuesto_numero_ot">-</span></div>
                                <div class="mb-2" style="line-height: 1.5;"><strong>Fecha:</strong> <span id="presupuesto_fecha_ot">-</span></div>
                                <div class="mb-2" style="line-height: 1.5;"><strong>Usuario:</strong> <span id="presupuesto_usuario_actual">@php
                                    $userSession = session('user');
                                    if (!$userSession && session('token')) {
                                        try {
                                            $token = session('token');
                                            $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                                            $userId = $payload->get('sub');
                                            $usuario = \App\Models\Usuario::find($userId);
                                            if ($usuario) {
                                                $userSession = [
                                                    'nombre' => $usuario->nombre,
                                                    'email' => $usuario->email
                                                ];
                                            }
                                        } catch (\Exception $e) {
                                            // Silenciar errores
                                        }
                                    }
                                    echo $userSession && is_array($userSession) ? ($userSession['email'] ?? '-') : (is_object($userSession) && isset($userSession->email) ? $userSession->email : '-');
                                @endphp</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensaje cuando no hay presupuesto (OT masiva) -->
                    <div id="mensaje_sin_presupuesto" class="alert alert-info text-center py-5" style="display: none;">
                        <i class="bi bi-info-circle" style="font-size: 3em; color: #0dcaf0;"></i>
                        <h5 class="mt-3 mb-2">No hay presupuesto disponible</h5>
                        <p class="mb-0">Orden de trabajo masiva</p>
                    </div>
                    
                    <!-- Sección de presupuesto (se oculta si no hay presupuesto) -->
                    <div id="seccion_presupuesto_modal">
                    <!-- Título del presupuesto -->
                    <div class="mb-3">
                        <h5 class="mb-1" id="presupuesto_titulo">Detalle del presupuesto (<span id="presupuesto_cantidad_items">0</span> ítems)</h5>
                    </div>
                    
                    <!-- Tabla de presupuesto -->
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 8%;">Ítem</th>
                                    <th style="width: 40%;">Partida</th>
                                    <th class="text-center" style="width: 10%;">Unidad</th>
                                    <th class="text-end" style="width: 12%;">Cantidad</th>
                                    <th class="text-end" style="width: 15%;">Precio Unitario</th>
                                    <th class="text-end" style="width: 15%;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPresupuestoOt">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Sección de Totales -->
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <div class="text-end">
                                <div class="mb-2">
                                    <span class="me-3"><strong>Valor Neto:</strong></span>
                                    <span id="presupuesto_total_neto">$ 0</span>
                                </div>
                                <div class="mb-2">
                                    <span class="me-3"><strong>IVA (19%):</strong></span>
                                    <span id="presupuesto_iva">$ 0</span>
                                </div>
                                <div class="mb-0 pt-2 border-top">
                                    <span class="me-3"><strong>Total IVA incluido:</strong></span>
                                    <strong class="text-primary" id="presupuesto_total_con_iva">$ 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advertencia -->
                    <div class="alert alert-warning mt-4 mb-3 no-imprimir">
                        <strong>ADVERTENCIA:</strong> Todo daño durante la ejecución será responsabilidad del contratista. Se debe mantener bitácora de trabajos. Elementos de reemplazo deben ser de igual o superior calidad.
                    </div>
                    </div>
                    <!-- Fin sección de presupuesto -->
                    
                    <!-- Firmas (solo visibles al imprimir) -->
                    <div class="row mt-4 solo-imprimir" style="display: none;">
                        <div class="col-6">
                            <div class="pt-3 text-center">
                                <div class="mb-2"><strong id="presupuesto_firma_usuario_nombre">@php
                                    $userSession = session('user');
                                    if (!$userSession && session('token')) {
                                        try {
                                            $token = session('token');
                                            $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                                            $userId = $payload->get('sub');
                                            $usuario = \App\Models\Usuario::find($userId);
                                            if ($usuario) {
                                                $userSession = [
                                                    'nombre' => $usuario->nombre,
                                                    'email' => $usuario->email
                                                ];
                                            }
                                        } catch (\Exception $e) {
                                            // Silenciar errores
                                        }
                                    }
                                    echo $userSession && is_array($userSession) ? strtoupper($userSession['nombre'] ?? 'USUARIO') : (is_object($userSession) && isset($userSession->nombre) ? strtoupper($userSession->nombre) : 'USUARIO');
                                @endphp</strong></div>
                                <div class="mb-2 text-muted" id="presupuesto_firma_usuario_cargo" style="line-height: 1.2; font-size: 0.9em;">
                                    PROFESIONAL DE EJECUCIÓN DE<br>
                                    PROYECTOS DE INFRAESTRUCTURA
                                </div>
                                <div class="mt-4">
                                    <div style="border-top: 1px solid #000; margin: 0 auto; padding-top: 5px; display: inline-block; min-width: 250px;">
                                        <div>Firma:</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="pt-3 text-center">
                                <div class="mb-2"><strong>JEFE DE UNIDAD</strong></div>
                                <div class="mb-2" style="visibility: hidden; line-height: 1.2; font-size: 0.9em;">Espacio<br>Espacio</div>
                                <div class="mt-4">
                                    <div style="border-top: 1px solid #000; margin: 0 auto; padding-top: 5px; display: inline-block; min-width: 250px;">
                                        <div>Firma:</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0);" id="btnActaRecepcionConforme" class="btn btn-success" target="_blank">
                            <i class="bi bi-file-earmark-check"></i> Acta Recepción Conforme
                        </a>
                    </div>
                    <button type="button" class="btn btn-primary btn-imprimir" onclick="imprimirPresupuestoOt()">
                        <i class="bi bi-printer"></i> IMPRIMIR
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CERRAR</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estilos para impresión -->
    <style media="print">
        @page {
            margin: 0.5cm;
            size: A4;
        }
        
        @media print {
            /* Eliminar encabezados y pies de página del navegador */
            @page {
                margin: 0.5cm;
                size: A4;
            }
            
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
            }
            
            body * {
                visibility: hidden;
            }
            
            #modalPresupuestoOt, #modalPresupuestoOt * {
                visibility: visible;
            }
            
            #modalPresupuestoOt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: auto;
                max-height: 100vh;
                margin: 0;
                padding: 10px;
                border: none;
                box-shadow: none;
                background: white;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            
            .modal-dialog {
                max-width: 100%;
                margin: 0;
                width: 100%;
                height: auto;
            }
            
            .modal-content {
                border: none;
                box-shadow: none;
                background: white;
                height: auto;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            
            .modal-header {
                display: none !important;
            }
            
            .modal-body {
                padding: 10px;
                padding-top: 10px;
                background: white;
                height: auto;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            
            .modal-footer {
                display: none;
            }
            
            .btn-close {
                display: none;
            }
            
            .table {
                border-collapse: collapse;
                width: 100%;
                margin-top: 10px;
                page-break-inside: avoid;
                border: none !important;
            }
            
            /* Eliminar todos los bordes de la tabla */
            .table, .table th, .table td {
                border: none !important;
            }
            
            .table th, .table td {
                padding: 6px;
                text-align: left;
                font-size: 0.85em;
            }
            
            .table th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
            
            /* Mantener el sombreado alternado en impresión */
            .table-striped > tbody > tr:nth-of-type(odd) > td,
            .table-striped > tbody > tr:nth-of-type(odd) > th {
                background-color: rgba(0, 0, 0, 0.05) !important;
            }
            
            .table-striped > tbody > tr:nth-of-type(even) > td,
            .table-striped > tbody > tr:nth-of-type(even) > th {
                background-color: transparent !important;
            }
            
            .text-end {
                text-align: right !important;
            }
            
            /* Alinear información de OT a la derecha en impresión */
            .modal-body .row > .col-md-6:last-child {
                text-align: right !important;
            }
            
            .modal-body .row > .col-md-6:last-child > div {
                text-align: right !important;
            }
            
            /* Asegurar alineación vertical de las filas en impresión */
            .modal-body .row > .col-md-6 > div {
                line-height: 1.5 !important;
                margin-bottom: 0.5rem !important;
            }
            
            /* Contrato más grande también en impresión */
            .modal-body .row > .col-md-6:first-child > div:first-child {
                font-size: 1.1em !important;
                line-height: 1.5 !important;
            }
            
            /* Mostrar firmas solo al imprimir */
            .solo-imprimir {
                display: flex !important;
                page-break-inside: avoid;
                page-break-after: avoid;
                margin-top: 10px;
            }
            
            .solo-imprimir .col-6 {
                display: inline-block;
                width: 50%;
                vertical-align: top;
            }
            
            /* Evitar saltos de página no deseados */
            .row {
                page-break-inside: avoid;
            }
            
            h3 {
                page-break-after: avoid;
                margin: 5px 0;
            }
            
            .alert {
                page-break-inside: avoid;
                margin: 10px 0;
                padding: 8px;
            }
            
            .card {
                page-break-inside: avoid;
            }
            
            /* Ocultar advertencia al imprimir (opcional, descomentar si quieres) */
            /* .no-imprimir {
                display: none !important;
            } */
        }
    </style>
@endsection

@push('scripts')
<script>
// Variables globales
let preciosUnitarios = [];
let itemCounter = 1;
let presupuestoItems = [];
let establecimientosOriginales = [];

// VERIFICACIÓN INMEDIATA - Ver si los elementos existen (solo si el DOM está listo)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 VERIFICACIÓN INMEDIATA:');
        console.log('  comuna_id:', document.getElementById('comuna_id') ? 'EXISTE' : 'NO EXISTE');
        console.log('  establecimiento_id:', document.getElementById('establecimiento_id') ? 'EXISTE' : 'NO EXISTE');
        console.log('  rbd:', document.getElementById('rbd') ? 'EXISTE' : 'NO EXISTE');
    });
} else {
    console.log('🔍 VERIFICACIÓN INMEDIATA:');
    console.log('  comuna_id:', document.getElementById('comuna_id') ? 'EXISTE' : 'NO EXISTE');
    console.log('  establecimiento_id:', document.getElementById('establecimiento_id') ? 'EXISTE' : 'NO EXISTE');
    console.log('  rbd:', document.getElementById('rbd') ? 'EXISTE' : 'NO EXISTE');
}

// FUNCIONES GLOBALES - Deben estar disponibles inmediatamente para onchange
window.filtrarEstablecimientosPorComuna = function(comunaId) {
    console.log('🔍 Filtrando establecimientos por comuna:', comunaId);
    
    const establecimientoSelect = document.getElementById('establecimiento_id');
    if (!establecimientoSelect) {
        console.error('❌ No se encontró el select de establecimientos');
        return;
    }
    
    const comunaIdStr = String(comunaId || '').trim();
    
    // Guardar el valor actual si existe
    const valorActual = establecimientoSelect.value;
    
    // Limpiar el select y el RBD
    establecimientoSelect.innerHTML = '<option value="">Seleccionar...</option>';
    const rbdInput = document.getElementById('rbd');
    if (rbdInput) {
        rbdInput.value = '';
    }
    
    // Si no hay comuna seleccionada, mostrar todos
    if (!comunaIdStr || comunaIdStr === '' || comunaIdStr === '0') {
        console.log('📋 Mostrando todos los establecimientos (sin filtro)');
        establecimientosOriginales.forEach(function(est) {
            const option = document.createElement('option');
            option.value = est.value;
            // Usar textContent para preservar tildes y caracteres especiales (UTF-8)
            if (est.text) {
                option.textContent = est.text;
            }
            option.setAttribute('data-comuna', est.comuna);
            option.setAttribute('data-rbd', est.rbd);
            establecimientoSelect.appendChild(option);
        });
    } else {
        // Filtrar solo los de la comuna seleccionada
        console.log('🔎 Filtrando establecimientos de comuna:', comunaIdStr);
        let contador = 0;
        establecimientosOriginales.forEach(function(est) {
            // Comparar como strings para asegurar coincidencia
            const estComuna = String(est.comuna || '').trim();
            if (estComuna === comunaIdStr) {
                const option = document.createElement('option');
                option.value = est.value;
                // Usar textContent para preservar tildes y caracteres especiales (UTF-8)
                if (est.text) {
                    option.textContent = est.text;
                }
                option.setAttribute('data-comuna', est.comuna);
                option.setAttribute('data-rbd', est.rbd);
                establecimientoSelect.appendChild(option);
                contador++;
            }
        });
        console.log('✅ Establecimientos encontrados para la comuna:', contador);
    }
    
    // Si había un valor seleccionado, verificar si aún está disponible
    if (valorActual) {
        const opcionDisponible = Array.from(establecimientoSelect.options).find(function(opt) {
            return opt.value === valorActual;
        });
        
        if (opcionDisponible) {
            // Verificar que el establecimiento pertenezca a la comuna seleccionada
            const comunaEstablecimiento = opcionDisponible.getAttribute('data-comuna') || '';
            if (comunaIdStr && comunaEstablecimiento !== comunaIdStr) {
                // No pertenece a la comuna, limpiar
                console.log('⚠️ Establecimiento anterior no pertenece a la nueva comuna, limpiando');
                establecimientoSelect.value = '';
                if (rbdInput) {
                    rbdInput.value = '';
                }
            } else {
                // Sí pertenece, mantenerlo y actualizar RBD
                establecimientoSelect.value = valorActual;
                actualizarRBDDesdeSelect();
            }
        } else {
            // No está disponible, limpiar
            establecimientoSelect.value = '';
            if (rbdInput) {
                rbdInput.value = '';
            }
        }
    }
};

window.actualizarRBDDesdeSelect = function() {
    console.log('🔄 Actualizando RBD desde select...');
    
    const establecimientoSelect = document.getElementById('establecimiento_id');
    const rbdInput = document.getElementById('rbd');
    
    if (!establecimientoSelect || !rbdInput) {
        console.error('❌ No se encontraron los elementos necesarios');
        return;
    }
    
    const selectedIndex = establecimientoSelect.selectedIndex;
    const selected = establecimientoSelect.options[selectedIndex];
    
    console.log('📝 Establecimiento seleccionado:', selected ? selected.textContent : 'ninguno');
    
    if (selected && selected.value && selected.value !== '') {
        const rbd = selected.getAttribute('data-rbd') || '';
        const comunaEstablecimiento = selected.getAttribute('data-comuna') || '';
        const comunaSelect = document.getElementById('comuna_id');
        const comunaSeleccionada = comunaSelect ? String(comunaSelect.value).trim() : '';
        
        console.log('   RBD del establecimiento:', rbd);
        console.log('   Comuna del establecimiento:', comunaEstablecimiento);
        console.log('   Comuna seleccionada en el formulario:', comunaSeleccionada);
        
        // Validar que el establecimiento pertenezca a la comuna seleccionada
        if (comunaSeleccionada && comunaEstablecimiento && comunaEstablecimiento !== comunaSeleccionada) {
            console.warn('⚠️ El establecimiento no pertenece a la comuna seleccionada');
            alert('El establecimiento seleccionado no pertenece a la comuna elegida. Por favor, seleccione primero la comuna y luego el establecimiento correspondiente.');
            establecimientoSelect.value = '';
            rbdInput.value = '';
            return;
        }
        
        // Actualizar RBD
        if (rbd && rbd !== '' && rbd !== 'null' && rbd !== 'undefined') {
            rbdInput.value = rbd;
            console.log('✅ RBD actualizado a:', rbd);
        } else {
            rbdInput.value = '';
            console.log('⚠️ RBD vacío o no disponible');
        }
    } else {
        rbdInput.value = '';
        console.log('⚠️ No hay establecimiento seleccionado');
    }
};

// FUNCIÓN: Cargar mapeo de comunas
function cargarMapaComunas() {
    const comunaSelect = document.getElementById('comuna_id');
    if (!comunaSelect) {
        console.error('ERROR: No se encontró el select de comunas');
        return false;
    }
    
    comunasMap = {};
    const todasLasComunas = comunaSelect.querySelectorAll('option');
    todasLasComunas.forEach(function(opt) {
        if (opt.value && opt.value !== '') {
            comunasMap[opt.value] = opt.textContent.trim();
        }
    });
    
    console.log('✅ Mapa de comunas cargado:', Object.keys(comunasMap).length, 'comunas');
    return true;
}

// FUNCIÓN: Cargar establecimientos originales
function cargarEstablecimientosOriginales() {
    const establecimientoSelect = document.getElementById('establecimiento_id');
    if (!establecimientoSelect) {
        console.error('ERROR: No se encontró el select de establecimientos');
        return false;
    }
    
    // Limpiar array anterior
    establecimientosOriginales = [];
    
    // Guardar todas las opciones originales con sus datos
    const todasLasOpciones = establecimientoSelect.querySelectorAll('option');
    console.log('📋 Total opciones encontradas:', todasLasOpciones.length);
    
    todasLasOpciones.forEach(function(opt, index) {
        if (opt.value && opt.value !== '') {
            const comunaValue = opt.getAttribute('data-comuna');
            const rbdValue = opt.getAttribute('data-rbd');
            
            // Debug de los primeros 5 (sin mostrar HTML completo para evitar errores)
            if (index < 5) {
                console.log('  Opción ' + index + ':', {
                    value: opt.value,
                    text: opt.textContent.trim(),
                    comuna: comunaValue,
                    rbd: rbdValue
                });
            }
            
            // Obtener el texto del atributo data-nombre primero (más confiable para UTF-8)
            // Si no existe, usar textContent que preserva UTF-8 correctamente
            let textoEstablecimiento = '';
            const nombreAttribute = opt.getAttribute('data-nombre');
            if (nombreAttribute) {
                // Decodificar entidades HTML si existen
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = nombreAttribute;
                textoEstablecimiento = tempDiv.textContent || tempDiv.innerText || nombreAttribute;
            } else {
                // Fallback a textContent que preserva UTF-8
                textoEstablecimiento = opt.textContent || opt.innerText || opt.text || '';
            }
            textoEstablecimiento = String(textoEstablecimiento).trim();
            
            establecimientosOriginales.push({
                value: opt.value,
                text: textoEstablecimiento,
                comuna: comunaValue ? String(comunaValue).trim() : '',
                rbd: rbdValue ? String(rbdValue).trim() : ''
            });
        }
    });
    
    console.log('✅ Establecimientos guardados:', establecimientosOriginales.length);
    const conComuna = establecimientosOriginales.filter(function(e) { return e.comuna && e.comuna !== ''; }).length;
    console.log('📊 Establecimientos con comuna:', conComuna);
    
    return true;
}

// CONFIGURACIÓN DE EVENTOS
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOMContentLoaded - Iniciando configuración...');
    
    // PASO 0: Cargar mapeo de comunas PRIMERO
    if (!cargarMapaComunas()) {
        console.error('❌ Error al cargar mapa de comunas');
    }
    
    // PASO 1: Cargar establecimientos originales
    if (!cargarEstablecimientosOriginales()) {
        console.error('❌ Error al cargar establecimientos originales');
        return;
    }
    
    // PASO 2: Inicializar fecha y actualizar banner
    const fechaOtInput = document.getElementById('fecha_ot');
    if (fechaOtInput && !fechaOtInput.value) {
        fechaOtInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Actualizar fecha en el banner cuando cambia
    if (fechaOtInput) {
        fechaOtInput.addEventListener('change', function() {
            mostrarBannerPresupuesto();
        });
    }
    
    // Inicializar banner con valores actuales
    mostrarBannerPresupuesto();
    
    // PASO 3: Registrar listener para cambio de comuna
    const comunaSelect = document.getElementById('comuna_id');
    if (comunaSelect) {
        console.log('✅ Listener de comuna registrado');
        
        // Función para manejar cambio de comuna
        function manejarCambioComuna() {
            const comunaId = this.value;
            console.log('🔥🔥🔥 Cambio de comuna detectado:', comunaId);
            console.log('   Tipo de dato:', typeof comunaId);
            filtrarEstablecimientosPorComuna(comunaId);
        }
        
        // Múltiples formas de registrar el evento
        comunaSelect.addEventListener('change', manejarCambioComuna);
        comunaSelect.onchange = manejarCambioComuna;
        
        // Test directo
        console.log('🧪 Test: Disparando evento change manualmente...');
        setTimeout(function() {
            const evento = new Event('change', { bubbles: true });
            comunaSelect.dispatchEvent(evento);
            console.log('   Evento disparado');
        }, 100);
        
        // Si hay una comuna preseleccionada, filtrar inmediatamente
        if (comunaSelect.value) {
            console.log('ℹ️ Hay comuna preseleccionada, filtrando...');
            filtrarEstablecimientosPorComuna(comunaSelect.value);
        }
    } else {
        console.error('❌ No se encontró el select de comuna');
    }
    
    // PASO 4: Registrar listener para cambio de establecimiento
    const establecimientoSelect = document.getElementById('establecimiento_id');
    if (establecimientoSelect) {
        console.log('✅ Listener de establecimiento registrado');
        
        // Función para manejar cambio de establecimiento
        function manejarCambioEstablecimiento() {
            const establecimientoId = this.value;
            console.log('🔥🔥🔥 Cambio de establecimiento detectado:', establecimientoId);
            actualizarRBDDesdeSelect();
        }
        
        // Múltiples formas de registrar el evento
        establecimientoSelect.addEventListener('change', manejarCambioEstablecimiento);
        establecimientoSelect.onchange = manejarCambioEstablecimiento;
        
        // También usar onclick como respaldo adicional
        establecimientoSelect.addEventListener('click', function() {
            console.log('🖱️ Click en select de establecimiento');
        });
    } else {
        console.error('❌ No se encontró el select de establecimiento');
    }
    
    console.log('✅✅✅ Configuración completada ✅✅✅');
    
    // Listener para cambio de contrato
    const contratoSelect = document.getElementById('contrato_id');
    if (contratoSelect) {
        contratoSelect.addEventListener('change', function() {
            const contratoId = this.value;
            const selected = this.options[this.selectedIndex];
            const tienePrecios = selected ? selected.getAttribute('data-tiene-precios') : '0';
            const cantidadPrecios = selected ? parseInt(selected.getAttribute('data-cantidad-precios') || '0') : 0;
            const saldo = selected ? parseFloat(selected.getAttribute('data-saldo')) : 0;
            // Botón eliminado - ya no se muestra a la derecha del campo contrato
            // const btnVerPrecios = document.getElementById('btnVerPreciosUnitarios');
            const infoPrecios = document.getElementById('infoPreciosContrato');
            
            // Actualizar saldo disponible
            const saldoHint = document.getElementById('saldo_contrato_hint');
            if (saldoHint && saldo > 0) {
                saldoHint.textContent = 'Saldo disponible: $ ' + saldo.toLocaleString('es-CL');
                saldoHint.className = 'text-muted';
            } else {
                saldoHint.textContent = '';
            }
            
            // Validar saldo disponible si ya hay un monto calculado
            const montoTotalConIva = document.getElementById('monto_total_con_iva');
            if (montoTotalConIva && montoTotalConIva.value) {
                const montoOT = parseFloat(montoTotalConIva.value || '0');
                validarSaldoDisponible(montoOT);
            }
            
            // Mostrar/ocultar información de precios unitarios
            if (tienePrecios === '1' && cantidadPrecios > 0) {
                // Botón eliminado - ya no se muestra
                // if (btnVerPrecios) {
                //     btnVerPrecios.style.display = 'block';
                //     btnVerPrecios.setAttribute('data-contrato-id', contratoId);
                // }
                if (infoPrecios) {
                    infoPrecios.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> ' + cantidadPrecios + ' precios unitarios cargados</span>';
                    infoPrecios.style.display = 'block';
                }
            } else {
                // Botón eliminado - ya no se muestra
                // if (btnVerPrecios) {
                //     btnVerPrecios.style.display = 'none';
                // }
                if (infoPrecios) {
                    infoPrecios.innerHTML = '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> No hay precios unitarios cargados</span>';
                    infoPrecios.style.display = 'block';
                }
            }
            
            // Actualizar información del OT en el banner
            mostrarBannerPresupuesto();
            
            // Si no hay precios unitarios, mostrar alerta
            if (tienePrecios === '0') {
                document.getElementById('alertaSinPrecios').classList.remove('d-none');
                preciosUnitarios = []; // Limpiar precios
                return;
            }
            
            document.getElementById('alertaSinPrecios').classList.add('d-none');
            
            // Cargar precios unitarios del contrato
            if (contratoId) {
                fetch('/contratos/' + contratoId + '/precios-unitarios', { 
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Eliminar duplicados y asegurar que cada precio sea único
                        const preciosUnicos = [];
                        const preciosVistos = new Set();
                        
                        (data.precios || []).forEach(function(precio) {
                            // Debug: verificar estructura del precio
                            if (preciosUnicos.length < 3) {
                                console.log('🔍 Precio recibido:', {
                                    numero_partida: precio.numero_partida,
                                    partida: precio.partida,
                                    unidad: precio.unidad,
                                    precio_unitario: precio.precio_unitario,
                                    keys: Object.keys(precio)
                                });
                            }
                            
                            // Crear una clave única normalizada (minúsculas, sin espacios extra) basada en numero_partida y partida
                            const numNorm = (precio.numero_partida || '').toString().toLowerCase().trim();
                            const partNorm = (precio.partida || '').toLowerCase().trim();
                            const clave = numNorm + '|' + partNorm;
                            if (!preciosVistos.has(clave) && partNorm) {
                                preciosVistos.add(clave);
                                preciosUnicos.push(precio);
                            }
                        });
                        
                        preciosUnitarios = preciosUnicos;
                        console.log('✅ Precios unitarios cargados:', preciosUnitarios.length, 'items únicos');
                        
                        // Debug: mostrar algunos precios para verificar formato
                        if (preciosUnitarios.length > 0) {
                            console.log('📋 Ejemplo de precios (primeros 5):', preciosUnitarios.slice(0, 5).map(function(p) {
                                return {
                                    num: p.numero_partida,
                                    partida: p.partida,
                                    formato: (p.numero_partida ? p.numero_partida + ' - ' : '') + p.partida
                                };
                            }));
                            
                            // Contar cuántos tienen numero_partida
                            const conNumero = preciosUnitarios.filter(function(p) {
                                return p.numero_partida && String(p.numero_partida).trim() !== '';
                            }).length;
                            console.log('📊 Precios con número de partida:', conNumero, 'de', preciosUnitarios.length);
                        }
                        mostrarBannerPresupuesto();
                        // Actualizar todos los datalists existentes
                        actualizarTodosLosDatalists();
                    }
                })
                .catch(function(err) { console.error('Error cargando precios:', err); });
            }
        });
    }
    
    // Función para mostrar modal de precios unitarios
    // Variable global para almacenar todos los precios (sin filtrar)
    let todosLosPreciosUnitarios = [];
    
    window.mostrarModalPreciosUnitarios = function() {
        // Obtener el contrato_id del campo select del formulario
        const contratoSelect = document.getElementById('contrato_id');
        const contratoId = contratoSelect ? contratoSelect.value : null;
        
        if (!contratoId || contratoId === '') {
            alert('Por favor, selecciona un contrato primero');
            return;
        }
        
        fetch('/contratos/' + contratoId + '/precios-unitarios', { 
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.precios) {
                // Guardar todos los precios en la variable global
                todosLosPreciosUnitarios = data.precios;
                
                const modal = new bootstrap.Modal(document.getElementById('modalPreciosUnitarios'));
                const tbody = document.getElementById('tbodyPreciosUnitarios');
                const buscarInput = document.getElementById('buscarPreciosUnitarios');
                
                // Limpiar campo de búsqueda
                if (buscarInput) {
                    buscarInput.value = '';
                }
                
                // Renderizar todos los precios (sin filtro)
                renderizarPreciosUnitarios(todosLosPreciosUnitarios);
                
                modal.show();
            } else {
                alert('No se pudieron cargar los precios unitarios');
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            alert('Error al cargar los precios unitarios');
        });
    };
    
    // Función para renderizar los precios unitarios
    function renderizarPreciosUnitarios(precios) {
        const tbody = document.getElementById('tbodyPreciosUnitarios');
        tbody.innerHTML = '';
        
        if (precios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No se encontraron precios unitarios</td></tr>';
            return;
        }
        
        precios.forEach(function(precio) {
            const row = document.createElement('tr');
            
            // Usar el indicador del servidor si está disponible, o detectar localmente
            const esTitulo = precio.es_titulo !== undefined ? precio.es_titulo : 
                            (!precio.unidad || precio.unidad === '-' || !precio.precio_unitario || precio.precio_unitario === 0 || 
                            /^[\d\.]+\s*-?\s*$/.test(precio.partida.trim()));
            
            // Mostrar numero_partida solo si existe y no es vacío
            const numeroPartidaDisplay = (precio.numero_partida && String(precio.numero_partida).trim() !== '') ? precio.numero_partida : '-';
            
            // Si es título, aplicar negrita y fondo gris
            if (esTitulo) {
                row.className = 'table-active fw-bold'; // Fondo gris y negrita para títulos
                row.innerHTML = '<td class="fw-bold">' + numeroPartidaDisplay + '</td>' +
                               '<td class="fw-bold" colspan="3">' + (precio.partida || '-') + '</td>';
            } else {
                row.innerHTML = '<td>' + numeroPartidaDisplay + '</td>' +
                               '<td>' + (precio.partida || '-') + '</td>' +
                               '<td>' + (precio.unidad || '-') + '</td>' +
                               '<td class="text-end">$ ' + (parseFloat(precio.precio_unitario || 0)).toLocaleString('es-CL') + '</td>';
            }
            tbody.appendChild(row);
        });
    }
    
    // Función para filtrar precios unitarios
    window.filtrarPreciosUnitarios = function(terminoBusqueda) {
        const termino = terminoBusqueda.toLowerCase().trim();
        
        if (!termino || termino === '') {
            // Si no hay término, mostrar todos
            renderizarPreciosUnitarios(todosLosPreciosUnitarios);
            return;
        }
        
        // Filtrar precios que coincidan con el término de búsqueda
        const preciosFiltrados = todosLosPreciosUnitarios.filter(function(precio) {
            const numeroPartida = (precio.numero_partida && String(precio.numero_partida).trim() !== '') ? String(precio.numero_partida).toLowerCase() : '';
            const partida = (precio.partida || '').toLowerCase();
            const unidad = (precio.unidad || '').toLowerCase();
            const precioStr = (precio.precio_unitario || 0).toString().toLowerCase();
            
            // Buscar en número de partida, partida, unidad o precio
            return numeroPartida.includes(termino) || 
                   partida.includes(termino) || 
                   unidad.includes(termino) || 
                   precioStr.includes(termino);
        });
        
        // Renderizar los precios filtrados
        renderizarPreciosUnitarios(preciosFiltrados);
    };
});

function mostrarBannerPresupuesto() {
    const numeroOt = document.getElementById('numero_ot').value;
    const fechaOt = document.getElementById('fecha_ot').value;
    const fechaFormateada = fechaOt ? new Date(fechaOt + 'T00:00:00').toLocaleDateString('es-CL') : '';
    
    // Actualizar los elementos de display
    const numeroOtDisplay = document.getElementById('numero_ot_display');
    const fechaOtDisplay = document.getElementById('fecha_ot_display');
    
    if (numeroOtDisplay) {
        numeroOtDisplay.textContent = numeroOt || '{{ $numeroOt ?? "" }}';
    }
    if (fechaOtDisplay) {
        fechaOtDisplay.textContent = fechaFormateada || '{{ date("d/m/Y") }}';
    }
    
    // El banner ya está visible, no necesitamos cambiar display
}

// Agregar fila al presupuesto
function agregarFilaPresupuesto() {
    const tbody = document.getElementById('tbodyPresupuesto');
    const row = document.createElement('tr');
    
    // Calcular el índice basado en las filas existentes, no en itemCounter
    const rows = document.querySelectorAll('#tbodyPresupuesto tr');
    const currentIndex = rows.length;
    
    // Inicializar el item en el array
    presupuestoItems[currentIndex] = {
        item: currentIndex + 1,
        partida: '',
        numero_partida: null,
        unidad: '',
        cantidad: 0,
        precio: 0,
        total: 0
    };
    
    // Actualizar itemCounter para que sea consistente
    itemCounter = currentIndex + 2;
    
    // Usar el datalist global
    const datalistId = 'datalistPartidas';
    
    row.innerHTML = `
        <td>${currentIndex + 1}</td>
        <td>
            <input type="text" 
                   class="form-control partida-input" 
                   list="${datalistId}"
                   placeholder="Escribir o seleccionar partida...">
        </td>
        <td>
            <input type="text" class="form-control unidad-input" placeholder="m², m³, etc.">
        </td>
        <td>
            <input type="number" class="form-control cantidad-input" step="0.00000001" min="0" placeholder="0">
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control precio-input text-end" step="0.01" min="0" placeholder="0">
            </div>
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" class="form-control total-display text-end" readonly>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    
    // Agregar event listeners después de agregar la fila al DOM
    const partidaInput = row.querySelector('.partida-input');
    const unidadInput = row.querySelector('.unidad-input');
    const cantidadInput = row.querySelector('.cantidad-input');
    const precioInput = row.querySelector('.precio-input');
    const deleteBtn = row.querySelector('.btn-danger');
    
    if (partidaInput) {
        partidaInput.addEventListener('change', function() {
            autocompletarPartidaDesdeInput(this);
        });
        partidaInput.addEventListener('input', function() {
            autocompletarPartidaDesdeInput(this);
        });
    }
    if (unidadInput) {
        unidadInput.addEventListener('change', function() {
            calcularTotal(currentIndex);
        });
    }
    if (cantidadInput) {
        cantidadInput.addEventListener('change', function() {
            calcularTotal(currentIndex);
        });
        cantidadInput.addEventListener('input', function() {
            calcularTotal(currentIndex);
        });
    }
    if (precioInput) {
        precioInput.addEventListener('change', function() {
            calcularTotal(currentIndex);
        });
        precioInput.addEventListener('input', function() {
            calcularTotal(currentIndex);
        });
    }
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            eliminarFilaPresupuesto(currentIndex);
        });
    }
    
    // Asegurar que el datalist global esté actualizado
    if (preciosUnitarios && preciosUnitarios.length > 0) {
        actualizarTodosLosDatalists();
    }
    
    itemCounter++;
    actualizarMontoTotal();
}

// Nueva función que recibe el input directamente
function autocompletarPartidaDesdeInput(inputElement) {
    if (!inputElement) return;
    
    // Obtener el valor actual del input (puede ser del datalist o texto escrito)
    const partidaNombre = inputElement.value.trim();
    if (!partidaNombre) return;
    
    // Encontrar la fila directamente desde el elemento
    const row = inputElement.closest('tr');
    if (!row) return;
    
    // Encontrar el índice real de la fila en el DOM
    const rows = Array.from(document.querySelectorAll('#tbodyPresupuesto tr'));
    const index = rows.indexOf(row);
    
    if (index < 0) {
        console.warn('⚠️ No se pudo encontrar el índice de la fila');
        return;
    }
    
    // Llamar a la función principal con el índice correcto y la fila
    autocompletarPartida(index, partidaNombre, row, inputElement);
}

function autocompletarPartida(index, partidaNombre, rowElement, partidaInputElement) {
    // Usar la fila proporcionada o encontrarla por índice
    let row = rowElement || document.querySelectorAll('#tbodyPresupuesto tr')[index];
    
    if (!row || !partidaNombre) {
        console.warn('⚠️ autocompletarPartida: fila o partidaNombre inválidos', { row, partidaNombre, index });
        return;
    }
    
    // Usar el input proporcionado o encontrarlo en la fila
    const partidaInput = partidaInputElement || row.querySelector('.partida-input');
    
    // Buscar si la partida existe en los precios unitarios
    // El formato puede ser "numero - partida" o solo "partida"
    let precio = null;
    if (partidaNombre.includes(' - ')) {
        // Formato con número: buscar por número o por partida completa
        const partes = partidaNombre.split(' - ');
        const numeroPartida = partes[0].trim();
        const partidaText = partes.slice(1).join(' - ').trim();
        
        precio = preciosUnitarios.find(function(p) {
            const partidaCompleta = (p.numero_partida ? String(p.numero_partida).trim() + ' - ' : '') + (p.partida || '');
            return partidaCompleta.toLowerCase() === partidaNombre.toLowerCase() ||
                   (p.numero_partida && String(p.numero_partida).trim() === numeroPartida) ||
                   (p.partida && p.partida.toLowerCase() === partidaText.toLowerCase());
        });
    } else {
        // Solo partida, buscar por nombre
        precio = preciosUnitarios.find(function(p) {
            const partidaCompleta = (p.numero_partida ? String(p.numero_partida).trim() + ' - ' : '') + (p.partida || '');
            return partidaCompleta.toLowerCase() === partidaNombre.toLowerCase() ||
                   (p.partida && p.partida.toLowerCase() === partidaNombre.toLowerCase());
        });
    }
    
    // Si no se encontró el precio, salir
    if (!precio) {
        console.log('ℹ️ No se encontró precio para:', partidaNombre);
        return;
    }
    
    // Obtener los inputs de la fila correcta
    const unidadInput = row.querySelector('.unidad-input');
    const precioInput = row.querySelector('.precio-input');
    
    // SIEMPRE actualizar el campo de partida con el formato completo "numero - partida"
    if (partidaInput && precio.numero_partida) {
        const partidaCompleta = String(precio.numero_partida).trim() + ' - ' + (precio.partida || '');
        partidaInput.value = partidaCompleta;
    } else if (partidaInput && precio.partida) {
        // Si no tiene número pero tiene partida, mantener solo la partida
        partidaInput.value = precio.partida;
    }
    
    // Asegurar que el array presupuestoItems tenga el item en el índice correcto
    if (!presupuestoItems[index]) {
        presupuestoItems[index] = {
            item: index + 1,
            partida: '',
            numero_partida: null,
            unidad: '',
            cantidad: 0,
            precio: 0,
            total: 0
        };
    }
    
    // Actualizar el item con los datos del precio encontrado
    const valorPartida = partidaInput ? partidaInput.value.trim() : partidaNombre;
    
    if (valorPartida.includes(' - ')) {
        const partes = valorPartida.split(' - ');
        presupuestoItems[index].numero_partida = partes[0].trim();
        presupuestoItems[index].partida = partes.slice(1).join(' - ').trim();
    } else {
        presupuestoItems[index].partida = valorPartida;
        presupuestoItems[index].numero_partida = precio.numero_partida || null;
    }
    
    // Actualizar unidad y precio en el item
    presupuestoItems[index].unidad = precio.unidad || '';
    presupuestoItems[index].precio = parseFloat(precio.precio_unitario) || 0;
    
    // SIEMPRE actualizar los campos visuales cuando se encuentra un precio
    if (unidadInput) {
        unidadInput.value = precio.unidad || '';
        // Disparar evento para que se actualice el array si hay listeners
        unidadInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (precioInput) {
        precioInput.value = precio.precio_unitario || '0';
        // Disparar evento para que se actualice y recalcule
        precioInput.dispatchEvent(new Event('input', { bubbles: true }));
        precioInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    // Recalcular total usando el índice correcto
    calcularTotal(index);
}

function calcularTotal(index) {
    const row = document.querySelectorAll('#tbodyPresupuesto tr')[index];
    if (!row || !presupuestoItems[index]) return;
    
    const partidaInput = row.querySelector('.partida-input');
    const unidadInput = row.querySelector('.unidad-input');
    const cantidadInput = row.querySelector('.cantidad-input');
    const precioInput = row.querySelector('.precio-input');
    const totalDisplay = row.querySelector('.total-display');
    
    const partidaTexto = partidaInput ? partidaInput.value.trim() : '';
    const unidad = unidadInput ? unidadInput.value.trim() : '';
    const cantidad = parseFloat(cantidadInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;
    // Calcular total y redondear a 0 decimales (sin decimales)
    const total = Math.round(cantidad * precio);
    
    // Si el campo de partida tiene texto pero no tiene formato "numero - partida", intentar encontrarlo
    if (partidaTexto && !partidaTexto.includes(' - ')) {
        const precioEncontrado = preciosUnitarios.find(function(p) {
            return (p.partida && p.partida.toLowerCase() === partidaTexto.toLowerCase());
        });
        if (precioEncontrado && precioEncontrado.numero_partida && partidaInput) {
            // Actualizar el campo para mostrar el formato completo
            partidaInput.value = precioEncontrado.numero_partida + ' - ' + precioEncontrado.partida;
            presupuestoItems[index].numero_partida = precioEncontrado.numero_partida;
            presupuestoItems[index].partida = precioEncontrado.partida;
        } else {
            // Si no se encuentra, separar si tiene " - " o guardar como está
            if (partidaTexto.includes(' - ')) {
                const partes = partidaTexto.split(' - ');
                presupuestoItems[index].numero_partida = partes[0].trim();
                presupuestoItems[index].partida = partes.slice(1).join(' - ').trim();
            } else {
                presupuestoItems[index].partida = partidaTexto;
                presupuestoItems[index].numero_partida = null;
            }
        }
    } else if (partidaTexto.includes(' - ')) {
        // Si ya tiene el formato, separar número y partida
        const partes = partidaTexto.split(' - ');
        presupuestoItems[index].numero_partida = partes[0].trim();
        presupuestoItems[index].partida = partes.slice(1).join(' - ').trim();
    } else {
        presupuestoItems[index].partida = partidaTexto;
        presupuestoItems[index].numero_partida = null;
    }
    
    // Actualizar el item
    presupuestoItems[index].unidad = unidad;
    presupuestoItems[index].cantidad = cantidad;
    presupuestoItems[index].precio = precio;
    presupuestoItems[index].total = total;
    
    if (totalDisplay) {
        totalDisplay.value = total.toLocaleString('es-CL');
    }
    
    actualizarMontoTotal();
}

function eliminarFilaPresupuesto(index) {
    const row = document.querySelectorAll('#tbodyPresupuesto tr')[index];
    if (row) {
        row.remove();
        delete presupuestoItems[index];
        renumerarItems();
        actualizarMontoTotal();
    }
}

function renumerarItems() {
    const rows = document.querySelectorAll('#tbodyPresupuesto tr');
    rows.forEach(function(row, idx) {
        row.querySelector('td:first-child').textContent = idx + 1;
        const cantidadInput = row.querySelector('.cantidad-input');
        const partidaInput = row.querySelector('.partida-input');
        const unidadInput = row.querySelector('.unidad-input');
        const precioInput = row.querySelector('.precio-input');
        
        if (cantidadInput) {
            cantidadInput.setAttribute('onchange', 'calcularTotal(' + idx + ')');
            cantidadInput.setAttribute('oninput', 'calcularTotal(' + idx + ')');
        }
        if (partidaInput) {
            partidaInput.setAttribute('onchange', 'autocompletarPartidaDesdeInput(this)');
            partidaInput.setAttribute('oninput', 'autocompletarPartidaDesdeInput(this)');
        }
        if (unidadInput) {
            unidadInput.setAttribute('onchange', 'calcularTotal(' + idx + ')');
            unidadInput.setAttribute('oninput', 'calcularTotal(' + idx + ')');
        }
        if (precioInput) {
            precioInput.setAttribute('onchange', 'calcularTotal(' + idx + ')');
            precioInput.setAttribute('oninput', 'calcularTotal(' + idx + ')');
        }
    });
    
    // Renumerar items en el array
    presupuestoItems = presupuestoItems.filter(function(item) { return item !== undefined; });
    presupuestoItems.forEach(function(item, idx) {
        item.item = idx + 1;
    });
}

function actualizarMontoTotal() {
    // Calcular total neto (suma de todos los items)
    const totalNeto = presupuestoItems.reduce(function(sum, item) {
        return sum + (parseFloat(item.total) || 0);
    }, 0);
    
    // Obtener porcentaje de IVA
    const porcentajeIvaInput = document.getElementById('porcentaje_iva');
    const porcentajeIva = parseFloat(porcentajeIvaInput ? porcentajeIvaInput.value : 19) || 0;
    
    // Calcular IVA y redondear a entero
    const iva = Math.round(totalNeto * (porcentajeIva / 100));
    
    // Calcular total con IVA y redondear a entero
    const totalConIva = Math.round(totalNeto + iva);
    
    // Actualizar visualización de totales
    const totalNetoDisplay = document.getElementById('total_neto_display');
    const ivaDisplay = document.getElementById('iva_display');
    const totalConIvaDisplay = document.getElementById('total_con_iva_display');
    const montoDisplay = document.getElementById('monto_display');
    
    if (totalNetoDisplay) {
        totalNetoDisplay.textContent = '$ ' + totalNeto.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    if (ivaDisplay) {
        ivaDisplay.textContent = '$ ' + iva.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    if (totalConIvaDisplay) {
        totalConIvaDisplay.textContent = '$ ' + totalConIva.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    if (montoDisplay) {
        // El monto de la OT es el total con IVA
        montoDisplay.value = totalConIva.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    
    // Actualizar campo oculto con el monto total con IVA (sin formato, solo número)
    const montoTotalConIvaInput = document.getElementById('monto_total_con_iva');
    if (montoTotalConIvaInput) {
        // Asegurar que sea un número sin formato (redondear a entero y convertir a string sin formato)
        const totalConIvaRedondeado = Math.round(totalConIva);
        montoTotalConIvaInput.value = totalConIvaRedondeado.toString();
    }
    
    // Validar saldo disponible del contrato y mostrar alerta visual
    validarSaldoDisponible(totalConIva);
}

// Función para validar saldo disponible y mostrar alerta visual
function validarSaldoDisponible(montoOT) {
    const contratoSelect = document.getElementById('contrato_id');
    if (!contratoSelect || !contratoSelect.value) {
        return;
    }
    
    const selectedOption = contratoSelect.options[contratoSelect.selectedIndex];
    if (!selectedOption) {
        return;
    }
    
    const saldoDisponible = parseFloat(selectedOption.getAttribute('data-saldo') || '0');
    
    // Buscar o crear alerta de saldo
    let alertaSaldo = document.getElementById('alerta_saldo_disponible');
    if (!alertaSaldo) {
        // Crear alerta si no existe
        const seccionPresupuesto = document.getElementById('seccionPresupuesto');
        if (seccionPresupuesto) {
            alertaSaldo = document.createElement('div');
            alertaSaldo.id = 'alerta_saldo_disponible';
            alertaSaldo.className = 'alert alert-danger mt-2 mb-0';
            alertaSaldo.style.display = 'none';
            // Insertar antes de la tabla
            const tableContainer = seccionPresupuesto.querySelector('.table-responsive');
            if (tableContainer) {
                tableContainer.parentNode.insertBefore(alertaSaldo, tableContainer);
            } else {
                seccionPresupuesto.appendChild(alertaSaldo);
            }
        }
    }
    
    if (montoOT > saldoDisponible + 0.01) {
        const diferencia = montoOT - saldoDisponible;
        alertaSaldo.innerHTML = '<i class="bi bi-exclamation-triangle"></i> <strong>ADVERTENCIA:</strong> El monto total ($' + 
            montoOT.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
            ') supera el saldo disponible del contrato ($' + 
            saldoDisponible.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
            ') por $' + diferencia.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
            '. Por favor, ajuste el presupuesto.';
        alertaSaldo.style.display = 'block';
        alertaSaldo.className = 'alert alert-danger mt-2 mb-0';
    } else {
        // Ocultar alerta si está dentro del presupuesto
        alertaSaldo.style.display = 'none';
    }
}

// Función para actualizar todos los datalists existentes con los precios unitarios
function actualizarTodosLosDatalists() {
    // Actualizar el datalist global
    const datalist = document.getElementById('datalistPartidas');
    if (!datalist) {
        console.warn('⚠️ No se encontró el datalist global');
        return;
    }
    
    // Limpiar datalist existente completamente
    datalist.innerHTML = '';
    
    if (!preciosUnitarios || preciosUnitarios.length === 0) {
        console.log('ℹ️ No hay precios unitarios para cargar');
        return;
    }
    
    // Regenerar opciones con formato "numero - partida", eliminando duplicados
    const preciosVistos = new Set();
    const valoresVistos = new Set();
    let opcionesAgregadas = 0;
    
    preciosUnitarios.forEach(function(precio) {
        const numeroPartida = precio.numero_partida ? String(precio.numero_partida).trim() : '';
        const partidaDesc = precio.partida ? String(precio.partida).trim() : '';
        
        if (!partidaDesc) return; // Saltar si no hay descripción
        
        // Crear clave única normalizada (minúsculas) para evitar duplicados
        const claveNormalizada = ((numeroPartida || '').toLowerCase() + '|' + partidaDesc.toLowerCase()).trim();
        if (preciosVistos.has(claveNormalizada)) {
            return; // Saltar duplicados
        }
        preciosVistos.add(claveNormalizada);
        
        // Formato: "numero - partida" si hay número, sino solo partida
        const partidaTexto = numeroPartida ? (numeroPartida + ' - ' + partidaDesc) : partidaDesc;
        
        // Evitar duplicados en el valor del option (comparación case-insensitive)
        const partidaTextoLower = partidaTexto.toLowerCase().trim();
        if (valoresVistos.has(partidaTextoLower)) {
            return; // Ya existe este valor exacto
        }
        valoresVistos.add(partidaTextoLower);
        
        // Crear option: solo value, sin textContent
        const option = document.createElement('option');
        option.value = partidaTexto;
        option.setAttribute('data-unidad', precio.unidad || '');
        option.setAttribute('data-precio', precio.precio_unitario || '0');
        option.setAttribute('data-numero-partida', numeroPartida);
        datalist.appendChild(option);
        opcionesAgregadas++;
    });
    
    console.log('✅ Datalist global actualizado con', opcionesAgregadas, 'opciones únicas (de', preciosUnitarios.length, 'precios totales)');
}

// Funciones para orden masiva
let filaMasivaCounter = 0;
let comunasMap = {}; // Mapeo de ID de comuna a nombre

function agregarFilaMasiva() {
    const tbody = document.getElementById('tbodyMasiva');
    const row = document.createElement('tr');
    const index = filaMasivaCounter++;
    
    // Crear contenedor para el input con búsqueda
    const tdEstablecimiento = document.createElement('td');
    tdEstablecimiento.style.position = 'relative';
    tdEstablecimiento.style.overflow = 'visible';
    const divContainer = document.createElement('div');
    divContainer.className = 'position-relative';
    divContainer.id = 'establecimiento-search-container-' + index;
    divContainer.style.overflow = 'visible';
    
    // Input visible para búsqueda
    const inputEstablecimiento = document.createElement('input');
    inputEstablecimiento.type = 'text';
    inputEstablecimiento.className = 'form-control establecimiento-search-input';
    inputEstablecimiento.placeholder = 'Buscar establecimiento...';
    inputEstablecimiento.autocomplete = 'off';
    inputEstablecimiento.setAttribute('data-index', index);
    inputEstablecimiento.setAttribute('data-establecimiento-id', '');
    
    // Input oculto para enviar el ID del establecimiento
    const inputHidden = document.createElement('input');
    inputHidden.type = 'hidden';
    inputHidden.name = 'establecimiento_id_masiva[]';
    inputHidden.className = 'establecimiento-id-hidden';
    inputHidden.required = true;
    
    // Dropdown para mostrar resultados
    const dropdown = document.createElement('div');
    dropdown.className = 'list-group position-absolute w-100';
    dropdown.id = 'establecimiento-dropdown-' + index;
    dropdown.style.display = 'none';
    dropdown.style.zIndex = '99999';
    dropdown.style.maxHeight = '200px';
    dropdown.style.overflowY = 'auto';
    dropdown.style.backgroundColor = 'white';
    dropdown.style.border = '1px solid #ced4da';
    dropdown.style.borderRadius = '0.375rem';
    dropdown.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
    dropdown.style.top = '100%';
    dropdown.style.left = '0';
    dropdown.style.marginTop = '2px';
    
    divContainer.appendChild(inputEstablecimiento);
    divContainer.appendChild(inputHidden);
    divContainer.appendChild(dropdown);
    tdEstablecimiento.appendChild(divContainer);
    
    // Event listeners para búsqueda
    inputEstablecimiento.addEventListener('input', function() {
        buscarEstablecimientoMasiva(this.value, index);
    });
    
    inputEstablecimiento.addEventListener('focus', function() {
        if (this.value.length > 0) {
            buscarEstablecimientoMasiva(this.value, index);
        }
    });
    
    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!divContainer.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Crear celda de monto
    const tdMonto = document.createElement('td');
    const divInputGroup = document.createElement('div');
    divInputGroup.className = 'input-group';
    const span = document.createElement('span');
    span.className = 'input-group-text';
    span.textContent = '$';
    const inputMonto = document.createElement('input');
    inputMonto.type = 'number';
    inputMonto.className = 'form-control monto-masiva-input';
    inputMonto.name = 'monto_masiva[]';
    inputMonto.step = '0.01';
    inputMonto.min = '0';
    inputMonto.required = true;
    inputMonto.setAttribute('data-index', index);
    
    // Event listener para actualizar monto total cuando cambia
    inputMonto.addEventListener('input', function() {
        actualizarMontoTotalMasiva();
    });
    inputMonto.addEventListener('change', function() {
        actualizarMontoTotalMasiva();
    });
    divInputGroup.appendChild(span);
    divInputGroup.appendChild(inputMonto);
    tdMonto.appendChild(divInputGroup);
    
    // Crear celda de acciones
    const tdAcciones = document.createElement('td');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm btn-danger';
    button.innerHTML = '<i class="bi bi-trash"></i>';
    button.onclick = function() { 
        row.remove();
        actualizarMontoTotalMasiva();
    };
    tdAcciones.appendChild(button);
    
    // Agregar celdas a la fila
    row.appendChild(tdEstablecimiento);
    row.appendChild(tdMonto);
    row.appendChild(tdAcciones);
    
    tbody.appendChild(row);
    
    // Actualizar monto total después de agregar la fila
    setTimeout(function() {
        actualizarMontoTotalMasiva();
    }, 100);
}

// Función para actualizar el monto total en el modal masivo
function actualizarMontoTotalMasiva() {
    const inputsMonto = document.querySelectorAll('#tbodyMasiva .monto-masiva-input');
    let total = 0;
    
    inputsMonto.forEach(function(input) {
        const valor = parseFloat(input.value || '0');
        if (!isNaN(valor) && valor > 0) {
            total += valor;
        }
    });
    
    // Actualizar campo de monto total
    const montoTotalInput = document.getElementById('monto_total_masiva');
    if (montoTotalInput) {
        montoTotalInput.value = total.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    
    // Validar contra el saldo disponible del contrato
    validarSaldoDisponibleMasiva(total);
}

// Función para validar saldo disponible en modal masivo
function validarSaldoDisponibleMasiva(montoTotal) {
    const contratoSelect = document.getElementById('contrato_id_masiva');
    const alertaSaldo = document.getElementById('alerta_saldo_masiva');
    const saldoHint = document.getElementById('saldo_contrato_masiva_hint');
    
    if (!contratoSelect || !contratoSelect.value) {
        if (alertaSaldo) alertaSaldo.style.display = 'none';
        if (saldoHint) saldoHint.textContent = '';
        return;
    }
    
    const selectedOption = contratoSelect.options[contratoSelect.selectedIndex];
    if (!selectedOption) {
        if (alertaSaldo) alertaSaldo.style.display = 'none';
        if (saldoHint) saldoHint.textContent = '';
        return;
    }
    
    const saldoDisponible = parseFloat(selectedOption.getAttribute('data-saldo') || '0');
    
    // Actualizar hint de saldo disponible
    if (saldoHint) {
        saldoHint.textContent = 'Saldo disponible: $ ' + saldoDisponible.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        if (montoTotal > saldoDisponible) {
            saldoHint.className = 'text-danger fw-bold';
        } else {
            saldoHint.className = 'text-muted';
        }
    }
    
    // Mostrar/ocultar alerta
    if (alertaSaldo) {
        if (montoTotal > saldoDisponible + 0.01) {
            const diferencia = montoTotal - saldoDisponible;
            alertaSaldo.innerHTML = '<i class="bi bi-exclamation-triangle"></i> <strong>ADVERTENCIA:</strong> El monto total ($' + 
                montoTotal.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                ') supera el saldo disponible del contrato ($' + 
                saldoDisponible.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                ') por $' + diferencia.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                '. Por favor, ajuste los montos.';
            alertaSaldo.style.display = 'block';
            alertaSaldo.className = 'alert alert-danger mt-2 mb-0';
        } else {
            alertaSaldo.style.display = 'none';
        }
    }
}

// Función para buscar establecimientos en el modal masivo
function buscarEstablecimientoMasiva(termino, index) {
    const dropdown = document.getElementById('establecimiento-dropdown-' + index);
    const inputHidden = document.querySelector('#establecimiento-search-container-' + index + ' .establecimiento-id-hidden');
    
    if (!dropdown || !inputHidden) return;
    
    // Limpiar dropdown
    dropdown.innerHTML = '';
    
    if (!termino || termino.trim().length < 2) {
        dropdown.style.display = 'none';
        return;
    }
    
    const terminoLower = termino.toLowerCase().trim();
    const resultados = [];
    
    // Buscar en establecimientos originales
    establecimientosOriginales.forEach(function(est) {
        const nombreEst = est.text ? est.text.toLowerCase() : '';
        const comunaId = est.comuna ? String(est.comuna).trim() : '';
        const nombreComuna = comunasMap[comunaId] ? comunasMap[comunaId].toLowerCase() : '';
        
        // Buscar en nombre del establecimiento o nombre de la comuna
        if (nombreEst.includes(terminoLower) || nombreComuna.includes(terminoLower)) {
            resultados.push(est);
        }
    });
    
    // Limitar a 10 resultados
    const resultadosLimitados = resultados.slice(0, 10);
    
    if (resultadosLimitados.length === 0) {
        dropdown.innerHTML = '<div class="list-group-item text-muted">No se encontraron establecimientos</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    // Mostrar resultados
    resultadosLimitados.forEach(function(est) {
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action';
        item.style.cursor = 'pointer';
        
        const nombreDisplay = est.text || '';
        const comunaId = est.comuna ? String(est.comuna).trim() : '';
        const nombreComuna = comunasMap[comunaId] || '';
        const comunaDisplay = nombreComuna ? ' - ' + nombreComuna : '';
        item.innerHTML = '<div><strong>' + nombreDisplay + '</strong>' + comunaDisplay + '</div>';
        
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const inputEstablecimiento = document.querySelector('#establecimiento-search-container-' + index + ' .establecimiento-search-input');
            if (inputEstablecimiento) {
                // Mostrar nombre del establecimiento y comuna
                const valorMostrado = nombreDisplay + comunaDisplay;
                inputEstablecimiento.value = valorMostrado;
                inputEstablecimiento.setAttribute('data-establecimiento-id', est.value);
            }
            if (inputHidden) {
                inputHidden.value = est.value;
            }
            dropdown.style.display = 'none';
        });
        
        dropdown.appendChild(item);
    });
    
    dropdown.style.display = 'block';
}

// Preparar formulario antes de enviar
document.getElementById('formOrdenTrabajo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Asegurar que el token CSRF esté presente
    const csrfToken = document.querySelector('input[name="_token"]') || document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const tokenValue = csrfToken.value || csrfToken.getAttribute('content');
        if (tokenValue) {
            formData.set('_token', tokenValue);
        }
    }
    
    // Asegurar que el monto enviado sea el total con IVA
    const montoTotalConIva = document.getElementById('monto_total_con_iva');
    if (montoTotalConIva && montoTotalConIva.value) {
        formData.set('monto', montoTotalConIva.value);
    }
    
    // Limpiar items anteriores
    formData.delete('presupuesto_items[]');
    
    // Agregar cada item del presupuesto
    presupuestoItems.forEach(function(item, index) {
        if (item.partida && item.cantidad > 0 && item.precio > 0) {
            // Enviar cada campo por separado para que Laravel lo procese correctamente
            formData.append('presupuesto_items[' + index + '][item]', item.item || (index + 1));
            formData.append('presupuesto_items[' + index + '][partida]', item.partida || '');
            // Si tiene numero_partida, enviarlo
            if (item.numero_partida) {
                formData.append('presupuesto_items[' + index + '][numero_partida]', item.numero_partida);
            }
            formData.append('presupuesto_items[' + index + '][unidad]', item.unidad || '');
            formData.append('presupuesto_items[' + index + '][cantidad]', item.cantidad);
            formData.append('presupuesto_items[' + index + '][precio]', item.precio);
            formData.append('presupuesto_items[' + index + '][total]', item.total);
        }
    });
    
    // Detectar si es edición (la URL contiene /ordenes-trabajo/{id} donde {id} es un número)
    const urlParts = this.action.split('/');
    const isEdit = urlParts.length > 2 && urlParts[urlParts.length - 1] && !isNaN(parseInt(urlParts[urlParts.length - 1])) && urlParts[urlParts.length - 2] === 'ordenes-trabajo';
    const otId = isEdit ? parseInt(urlParts[urlParts.length - 1]) : null;
    
    // Validar saldo disponible del contrato antes de enviar
    const contratoSelect = document.getElementById('contrato_id');
    const contratoId = contratoSelect ? contratoSelect.value : null;
    
    if (contratoId) {
        const selectedOption = contratoSelect.options[contratoSelect.selectedIndex];
        const saldoDisponible = selectedOption ? parseFloat(selectedOption.getAttribute('data-saldo') || '0') : 0;
        
        // Obtener el monto total de la OT (con IVA)
        const montoTotalConIva = document.getElementById('monto_total_con_iva');
        const montoOT = montoTotalConIva ? parseFloat(montoTotalConIva.value || '0') : 0;
        
        // Calcular el saldo real disponible
        // En edición, el saldo mostrado en el select ya excluye esta OT (se actualiza al cargar)
        // En creación, el saldo mostrado ya es el correcto
        let saldoReal = saldoDisponible;
        
        if (montoOT > saldoReal + 0.01) {
            const mensaje = '⚠️ ADVERTENCIA: El monto total de la OT ($' + montoOT.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                ') supera el saldo disponible del contrato ($' + saldoReal.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ').\n\n' +
                'Por favor, ajuste el presupuesto para que no exceda el saldo disponible.';
            
            alert(mensaje);
            e.preventDefault(); // Prevenir el envío
            return false;
        }
    }
    
    // Si es edición, agregar _method PUT
    if (isEdit) {
        formData.set('_method', 'PUT');
    }
    
    // Enviar con fetch para tener más control
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(function(response) {
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Si no es JSON, intentar leer como texto para debugging
            return response.text().then(function(text) {
                console.error('Respuesta no es JSON:', text);
                return { success: false, message: 'Error en la respuesta del servidor. Ver consola para más detalles.' };
            });
        }
    })
    .then(function(data) {
        if (data.success) {
            window.location.reload();
        } else {
            // Mostrar errores de validación si existen
            let mensajeError = data.message || 'No se pudo guardar la orden de trabajo';
            if (data.errors) {
                const errores = Object.values(data.errors).flat();
                mensajeError = errores.join('\n');
            }
            alert('Error: ' + mensajeError);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('Error al guardar la orden de trabajo: ' + error.message);
    });
});

// Funciones para editar y eliminar
function editarOrden(id) {
    fetch('/ordenes-trabajo/' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Llenar el formulario con los datos
                document.getElementById('numero_ot').value = data.orden.numero_ot || '';
                document.getElementById('fecha_ot').value = data.orden.fecha_ot || '';
                
                // Establecer comuna primero para que filtre los establecimientos
                if (data.orden.comuna_id) {
                    document.getElementById('comuna_id').value = data.orden.comuna_id;
                    // Disparar evento para filtrar establecimientos
                    if (window.filtrarEstablecimientosPorComuna) {
                        window.filtrarEstablecimientosPorComuna(data.orden.comuna_id);
                    }
                }
                
                // Establecer contrato
                if (data.orden.contrato_id) {
                    const contratoSelect = document.getElementById('contrato_id');
                    contratoSelect.value = data.orden.contrato_id;
                    
                    // Si hay saldo disponible específico para esta OT, actualizar el atributo data-saldo
                    if (data.saldo_disponible_contrato !== undefined) {
                        const selectedOption = contratoSelect.options[contratoSelect.selectedIndex];
                        if (selectedOption) {
                            selectedOption.setAttribute('data-saldo', data.saldo_disponible_contrato);
                            // Actualizar también el texto del option si es necesario
                            const textoOriginal = selectedOption.textContent.split(' - Saldo:')[0];
                            selectedOption.textContent = textoOriginal + ' - Saldo: $ ' + data.saldo_disponible_contrato.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                            
                            // Actualizar el hint de saldo disponible
                            const saldoHint = document.getElementById('saldo_contrato_hint');
                            if (saldoHint) {
                                saldoHint.textContent = 'Saldo disponible: $ ' + data.saldo_disponible_contrato.toLocaleString('es-CL');
                                saldoHint.className = 'text-muted';
                            }
                        }
                    }
                }
                
                // Establecer establecimiento después de que se filtre por comuna
                setTimeout(function() {
                    if (data.orden.establecimiento_id) {
                        document.getElementById('establecimiento_id').value = data.orden.establecimiento_id;
                        // Actualizar RBD
                        if (window.actualizarRBDDesdeSelect) {
                            window.actualizarRBDDesdeSelect();
                        }
                    }
                    if (data.orden.rbd) {
                        document.getElementById('rbd').value = data.orden.rbd;
                    }
                }, 100);
                
                document.getElementById('estado').value = data.orden.estado || 'Pendiente';
                document.getElementById('tipo').value = data.orden.tipo || 'Normal';
                
                // Cargar monto si existe
                if (data.orden.monto) {
                    const montoDisplay = document.getElementById('monto_display');
                    if (montoDisplay) {
                        montoDisplay.value = parseFloat(data.orden.monto || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    }
                    // También actualizar el campo oculto
                    const montoTotalConIvaInput = document.getElementById('monto_total_con_iva');
                    if (montoTotalConIvaInput) {
                        montoTotalConIvaInput.value = Math.round(parseFloat(data.orden.monto || 0)).toString();
                    }
                }
                
                // Cargar observación si existe
                const observacionInput = document.querySelector('textarea[name="observacion"]') || document.getElementById('observacion');
                if (observacionInput && data.orden.observacion) {
                    observacionInput.value = data.orden.observacion || '';
                }
                
                // Cargar oferente si existe
                const oferenteSelect = document.getElementById('oferente_id');
                if (oferenteSelect && data.orden.oferente_id) {
                    oferenteSelect.value = data.orden.oferente_id;
                }
                
                // Función para cargar items del presupuesto después de que se carguen los precios unitarios
                function cargarItemsPresupuesto() {
                    if (data.presupuesto_items && data.presupuesto_items.length > 0) {
                        // Limpiar tabla actual
                        const tbody = document.getElementById('tbodyPresupuesto');
                        if (tbody) {
                            tbody.innerHTML = '';
                            presupuestoItems = [];
                            itemCounter = 1;
                            
                            // Mostrar sección de presupuesto
                            const seccionPresupuesto = document.getElementById('seccionPresupuesto');
                            if (seccionPresupuesto) {
                                seccionPresupuesto.style.display = 'block';
                            }
                            
                            // Agregar cada item del presupuesto
                            data.presupuesto_items.forEach(function(item) {
                                const index = presupuestoItems.length;
                                
                                // Crear objeto del item
                                const itemData = {
                                    item: item.item || itemCounter++,
                                    partida: item.partida || '',
                                    numero_partida: item.numero_partida || null,
                                    unidad: item.unidad || '',
                                    cantidad: item.cantidad || 0,
                                    precio: item.precio || 0,
                                    total: item.total || 0
                                };
                                
                                presupuestoItems.push(itemData);
                                
                                // Crear fila en la tabla
                                const row = document.createElement('tr');
                                
                                // Formato de partida para mostrar
                                const partidaDisplay = item.numero_partida ? 
                                    (item.numero_partida + ' - ' + item.partida) : 
                                    item.partida;
                                
                                // Escapar valores para HTML
                                const partidaEscapada = (partidaDisplay || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                const unidadEscapada = (item.unidad || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                
                                row.innerHTML = 
                                    '<td>' + itemData.item + '</td>' +
                                    '<td><input type="text" class="form-control partida-input" value="' + partidaEscapada + '" list="datalistPartidas"></td>' +
                                    '<td><input type="text" class="form-control unidad-input" value="' + unidadEscapada + '"></td>' +
                                    '<td><input type="number" step="0.00000001" class="form-control cantidad-input" value="' + (item.cantidad || 0) + '"></td>' +
                                    '<td><input type="number" step="0.01" class="form-control precio-input" value="' + (item.precio || 0) + '"></td>' +
                                    '<td><input type="text" class="form-control total-display" value="' + (item.total || 0).toLocaleString('es-CL') + '" readonly></td>' +
                                    '<td><button type="button" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></td>';
                                
                                tbody.appendChild(row);
                                
                                // Agregar event listeners después de agregar la fila al DOM
                                const partidaInputLoaded = row.querySelector('.partida-input');
                                const unidadInputLoaded = row.querySelector('.unidad-input');
                                const cantidadInputLoaded = row.querySelector('.cantidad-input');
                                const precioInputLoaded = row.querySelector('.precio-input');
                                const deleteBtnLoaded = row.querySelector('.btn-danger');
                                
                                if (partidaInputLoaded) {
                                    partidaInputLoaded.addEventListener('change', function() {
                                        autocompletarPartidaDesdeInput(this);
                                    });
                                    partidaInputLoaded.addEventListener('input', function() {
                                        autocompletarPartidaDesdeInput(this);
                                    });
                                }
                                if (unidadInputLoaded) {
                                    unidadInputLoaded.addEventListener('change', function() {
                                        calcularTotal(index);
                                    });
                                }
                                if (cantidadInputLoaded) {
                                    cantidadInputLoaded.addEventListener('change', function() {
                                        calcularTotal(index);
                                    });
                                    cantidadInputLoaded.addEventListener('input', function() {
                                        calcularTotal(index);
                                    });
                                }
                                if (precioInputLoaded) {
                                    precioInputLoaded.addEventListener('change', function() {
                                        calcularTotal(index);
                                    });
                                    precioInputLoaded.addEventListener('input', function() {
                                        calcularTotal(index);
                                    });
                                }
                                if (deleteBtnLoaded) {
                                    deleteBtnLoaded.addEventListener('click', function() {
                                        eliminarFilaPresupuesto(index);
                                    });
                                }
                            });
                            
                            // Actualizar datalists si hay precios unitarios cargados
                            if (preciosUnitarios.length > 0) {
                                actualizarTodosLosDatalists();
                            }
                            
                            // Actualizar totales
                            actualizarMontoTotal();
                            
                            // Actualizar botón de Acta Recepción Conforme
                            const btnActaRecepcion = document.getElementById('btnActaRecepcionConforme');
                            if (btnActaRecepcion) {
                                btnActaRecepcion.href = `/ordenes-trabajo/${id}/acta-recepcion-conforme`;
                                btnActaRecepcion.style.pointerEvents = 'auto';
                                btnActaRecepcion.style.opacity = '1';
                            }
                            
                            // Actualizar banner de presupuesto
                            const bannerPresupuesto = document.getElementById('bannerPresupuesto');
                            if (bannerPresupuesto) {
                                bannerPresupuesto.style.display = 'block';
                                const numeroOtElement = document.getElementById('numero_ot_presupuesto');
                                const fechaOtElement = document.getElementById('fecha_ot_presupuesto');
                                if (numeroOtElement) numeroOtElement.textContent = data.orden.numero_ot || '';
                                if (fechaOtElement) {
                                    const fecha = data.orden.fecha_ot || '';
                                    if (fecha) {
                                        // Formato: YYYY-MM-DD a DD-MM-YYYY
                                        const partes = fecha.split('-');
                                        if (partes.length === 3) {
                                            fechaOtElement.textContent = partes[2] + '-' + partes[1] + '-' + partes[0];
                                        } else {
                                            fechaOtElement.textContent = fecha;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Si hay contrato seleccionado, cargar precios unitarios primero
                if (data.orden.contrato_id) {
                    const contratoSelect = document.getElementById('contrato_id');
                    if (contratoSelect) {
                        // Esperar un poco más para que se complete el filtrado de establecimientos
                        setTimeout(function() {
                            // Disparar evento de cambio para cargar precios unitarios
                            contratoSelect.dispatchEvent(new Event('change'));
                            // Esperar a que se carguen los precios antes de cargar los items
                            setTimeout(cargarItemsPresupuesto, 800);
                        }, 200);
                    }
                } else {
                    // Si no hay contrato, cargar items directamente después de un pequeño delay
                    setTimeout(cargarItemsPresupuesto, 300);
                }
                
                // Cambiar el formulario a modo edición
                const form = document.getElementById('formOrdenTrabajo');
                form.action = '/ordenes-trabajo/' + id;
                // El método PUT se manejará en el submit del formulario
                
                // Scroll al formulario
                form.scrollIntoView({ behavior: 'smooth' });
            }
        })
        .catch(function(err) { console.error('Error:', err); });
}

function eliminarOrden(id) {
    if (!confirm('¿Está seguro de eliminar esta orden de trabajo?')) return;
    
    fetch('/ordenes-trabajo/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(response) {
        // Si la respuesta es un redirect (302), seguirlo
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        
        // Si no es JSON, intentar parsear como texto
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Si no es JSON, recargar la página
            window.location.reload();
            return null;
        }
    })
    .then(function(data) {
        if (data) {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo eliminar la orden de trabajo'));
            }
        }
    })
    .catch(function(err) {
        console.error('Error:', err);
        alert('Error al eliminar la orden de trabajo. Por favor, recarga la página.');
    });
}

// Función para mostrar presupuesto de una OT
window.mostrarPresupuestoOt = function(otId) {
    console.log('🔍 Cargando presupuesto para OT:', otId);
    
    fetch('/ordenes-trabajo/' + otId + '/presupuesto', {
        headers: { 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(r) {
        console.log('📡 Respuesta recibida, status:', r.status);
        if (!r.ok) {
            return r.text().then(function(text) {
                console.error('❌ Error HTTP:', r.status, text);
                throw new Error('Error HTTP ' + r.status + ': ' + text);
            });
        }
        return r.json();
    })
    .then(function(data) {
        console.log('✅ Datos recibidos:', data);
        
        if (data.success) {
            const orden = data.orden;
            const presupuesto = data.presupuesto;
            const items = data.items || [];
            
            // Verificar si hay presupuesto
            if (!presupuesto || !items || items.length === 0) {
                // No hay presupuesto - mostrar mensaje de OT masiva
                mostrarMensajeSinPresupuesto(orden);
                // Deshabilitar botón de Acta Recepción Conforme (pero mantenerlo visible)
                const btnActaRecepcion = document.getElementById('btnActaRecepcionConforme');
                if (btnActaRecepcion) {
                    btnActaRecepcion.href = 'javascript:void(0);';
                    btnActaRecepcion.style.pointerEvents = 'none';
                    btnActaRecepcion.style.opacity = '0.5';
                }
                return;
            }
            
            // Hay presupuesto - mostrar normalmente
            // Ocultar mensaje de sin presupuesto si existe
            const mensajeSinPresupuesto = document.getElementById('mensaje_sin_presupuesto');
            if (mensajeSinPresupuesto) {
                mensajeSinPresupuesto.style.display = 'none';
            }
            
            // Mostrar sección de presupuesto
            const seccionPresupuesto = document.getElementById('seccion_presupuesto_modal');
            if (seccionPresupuesto) {
                seccionPresupuesto.style.display = 'block';
            }
            
            // Mostrar botón de imprimir
            const btnImprimir = document.querySelector('#modalPresupuestoOt .btn-imprimir');
            if (btnImprimir) {
                btnImprimir.style.display = 'inline-block';
            }
            
            // Actualizar información del contrato y proveedor
            const contratoElement = document.getElementById('presupuesto_contrato');
            const proveedorElement = document.getElementById('presupuesto_proveedor');
            
            if (contratoElement) contratoElement.textContent = orden.contrato ? orden.contrato.nombre_contrato : '-';
            if (proveedorElement) proveedorElement.textContent = orden.contrato && orden.contrato.proveedor ? orden.contrato.proveedor : '-';
            
            // Actualizar información de la OT
            const numeroOtElement = document.getElementById('presupuesto_numero_ot');
            const fechaOtElement = document.getElementById('presupuesto_fecha_ot');
            const establecimientoElement = document.getElementById('presupuesto_establecimiento');
            const rbdElement = document.getElementById('presupuesto_rbd');
            const comunaElement = document.getElementById('presupuesto_comuna');
            
            if (numeroOtElement) numeroOtElement.textContent = orden.numero_ot || '-';
            if (fechaOtElement) fechaOtElement.textContent = orden.fecha_ot || '-';
            if (establecimientoElement) establecimientoElement.textContent = orden.establecimiento ? orden.establecimiento.nombre : '-';
            if (rbdElement) rbdElement.textContent = orden.establecimiento && orden.establecimiento.rbd ? orden.establecimiento.rbd : '-';
            // Usar comuna de OT o del establecimiento como fallback
            if (comunaElement) {
                if (orden.comuna && orden.comuna.nombre) {
                    comunaElement.textContent = orden.comuna.nombre;
                } else if (orden.establecimiento && orden.establecimiento.comuna && orden.establecimiento.comuna.nombre) {
                    comunaElement.textContent = orden.establecimiento.comuna.nombre;
                } else {
                    comunaElement.textContent = '-';
                }
            }
            
            // Actualizar usuario desde el presupuesto si está disponible, sino usar el de sesión
            const usuarioElement = document.getElementById('presupuesto_usuario_actual');
            if (usuarioElement && presupuesto && presupuesto.usuario && presupuesto.usuario.email) {
                usuarioElement.textContent = presupuesto.usuario.email;
            }
            
            // Información para firmas (ya está prellenada en el HTML con el usuario de sesión)
            // No necesitamos actualizar esto ya que se obtiene de la sesión en el servidor
            
            // Actualizar título con cantidad de ítems
            const cantidadItemsElement = document.getElementById('presupuesto_cantidad_items');
            if (cantidadItemsElement) {
                cantidadItemsElement.textContent = items.length;
            }
            
            // Actualizar botón de Acta Recepción Conforme
            const btnActaRecepcion = document.getElementById('btnActaRecepcionConforme');
            if (btnActaRecepcion) {
                btnActaRecepcion.href = `/ordenes-trabajo/${otId}/acta-recepcion-conforme`;
                btnActaRecepcion.style.pointerEvents = 'auto';
                btnActaRecepcion.style.opacity = '1';
            }

                    // Limpiar tabla
                    const tbody = document.getElementById('tbodyPresupuestoOt');
                    tbody.innerHTML = '';

                    let totalNeto = 0;

                    if (items.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No hay items en el presupuesto</td></tr>';
                    } else {
                        items.forEach(function(item) {
                            const row = document.createElement('tr');

                            // Formato de partida: "numero - partida" si tiene numero, sino solo partida
                            const partidaDisplay = item.numero_partida ?
                                (item.numero_partida + ' - ' + item.partida) :
                                item.partida;

                            const total = parseFloat(item.total) || 0;
                            totalNeto += total;

                            row.innerHTML =
                                '<td class="text-center">' + (item.item || '-') + '</td>' +
                                '<td>' + (partidaDisplay || '-') + '</td>' +
                                '<td class="text-center">' + (item.unidad || '-') + '</td>' +
                                '<td class="text-end">' + (parseFloat(item.cantidad) || 0).toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                                '<td class="text-end">$' + (parseFloat(item.precio) || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + '</td>' +
                                '<td class="text-end">$' + total.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + '</td>';

                            tbody.appendChild(row);
                        });
                    }
            
                    // Calcular IVA y total
                    const iva = totalNeto * 0.19;
                    const totalConIva = totalNeto + iva;
            
                    // Actualizar totales
                    const totalNetoElement = document.getElementById('presupuesto_total_neto');
                    const ivaElement = document.getElementById('presupuesto_iva');
                    const totalConIvaElement = document.getElementById('presupuesto_total_con_iva');

                    if (totalNetoElement) totalNetoElement.textContent = '$' + totalNeto.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    if (ivaElement) ivaElement.textContent = '$' + iva.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    if (totalConIvaElement) totalConIvaElement.textContent = '$' + totalConIva.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            
            // Mostrar modal
            const modalElement = document.getElementById('modalPresupuestoOt');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                console.error('❌ No se encontró el modal');
                alert('Error: No se encontró el modal de presupuesto');
            }
        } else {
            const mensaje = data.message || 'No se pudo cargar el presupuesto';
            console.error('❌ Error en respuesta:', mensaje);
            alert(mensaje);
        }
    })
    .catch(function(err) {
        console.error('❌ Error al cargar presupuesto:', err);
        alert('Error al cargar el presupuesto: ' + err.message);
    });
};

// Función para mostrar mensaje cuando no hay presupuesto (OT masiva)
function mostrarMensajeSinPresupuesto(orden) {
    // Actualizar información básica de la OT
    const contratoElement = document.getElementById('presupuesto_contrato');
    const numeroOtElement = document.getElementById('presupuesto_numero_ot');
    const fechaOtElement = document.getElementById('presupuesto_fecha_ot');
    const establecimientoElement = document.getElementById('presupuesto_establecimiento');
    const comunaElement = document.getElementById('presupuesto_comuna');
    
    if (contratoElement) contratoElement.textContent = orden.contrato ? orden.contrato.nombre_contrato : '-';
    if (numeroOtElement) numeroOtElement.textContent = orden.numero_ot || '-';
    if (fechaOtElement) fechaOtElement.textContent = orden.fecha_ot || '-';
    if (establecimientoElement) establecimientoElement.textContent = orden.establecimiento ? orden.establecimiento.nombre : '-';
    if (comunaElement) comunaElement.textContent = orden.comuna ? orden.comuna.nombre : '-';
    
    // Ocultar sección de presupuesto
    const seccionPresupuesto = document.getElementById('seccion_presupuesto_modal');
    if (seccionPresupuesto) {
        seccionPresupuesto.style.display = 'none';
    }
    
    // Mostrar mensaje de sin presupuesto
    const mensajeSinPresupuesto = document.getElementById('mensaje_sin_presupuesto');
    if (mensajeSinPresupuesto) {
        mensajeSinPresupuesto.style.display = 'block';
    }
    
    // Ocultar botón de imprimir
    const btnImprimir = document.querySelector('#modalPresupuestoOt .btn-imprimir');
    if (btnImprimir) {
        btnImprimir.style.display = 'none';
    }
    
    // Deshabilitar botón de Acta Recepción Conforme (pero mantenerlo visible)
    const btnActaRecepcion = document.getElementById('btnActaRecepcionConforme');
    if (btnActaRecepcion) {
        btnActaRecepcion.href = 'javascript:void(0);';
        btnActaRecepcion.style.pointerEvents = 'none';
        btnActaRecepcion.style.opacity = '0.5';
    }
    
    // Mostrar modal
    const modalElement = document.getElementById('modalPresupuestoOt');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Función para imprimir el presupuesto
window.imprimirPresupuestoOt = function() {
    // Asegurar que el modal esté visible
    const modal = document.getElementById('modalPresupuestoOt');
    if (!modal) {
        alert('Error: No se encontró el modal de presupuesto');
        return;
    }
    
    // Crear una ventana de impresión
    window.print();
};

// Listener para cambio de contrato en modal masivo
document.addEventListener('DOMContentLoaded', function() {
    const contratoMasivaSelect = document.getElementById('contrato_id_masiva');
    if (contratoMasivaSelect) {
        contratoMasivaSelect.addEventListener('change', function() {
            // Actualizar validación cuando cambia el contrato
            actualizarMontoTotalMasiva();
        });
    }
    
    // Listener para el formulario masivo
    const formOrdenMasiva = document.getElementById('formOrdenMasiva');
    if (formOrdenMasiva) {
        formOrdenMasiva.addEventListener('submit', function(e) {
            // Validar saldo antes de enviar
            const inputsMonto = document.querySelectorAll('#tbodyMasiva .monto-masiva-input');
            let total = 0;
            
            inputsMonto.forEach(function(input) {
                const valor = parseFloat(input.value || '0');
                if (!isNaN(valor) && valor > 0) {
                    total += valor;
                }
            });
            
            const contratoSelect = document.getElementById('contrato_id_masiva');
            if (contratoSelect && contratoSelect.value) {
                const selectedOption = contratoSelect.options[contratoSelect.selectedIndex];
                if (selectedOption) {
                    const saldoDisponible = parseFloat(selectedOption.getAttribute('data-saldo') || '0');
                    
                    if (total > saldoDisponible + 0.01) {
                        e.preventDefault();
                        const diferencia = total - saldoDisponible;
                        alert('⚠️ ADVERTENCIA: El monto total ($' + 
                            total.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                            ') supera el saldo disponible del contrato ($' + 
                            saldoDisponible.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                            ') por $' + diferencia.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + 
                            '.\n\nPor favor, ajuste los montos antes de guardar.');
                        return false;
                    }
                }
            }
        });
    }
    
    // Limpiar monto total cuando se cierra el modal
    const modalOrdenMasiva = document.getElementById('modalOrdenMasiva');
    if (modalOrdenMasiva) {
        modalOrdenMasiva.addEventListener('hidden.bs.modal', function() {
            const montoTotalInput = document.getElementById('monto_total_masiva');
            if (montoTotalInput) {
                montoTotalInput.value = '0';
            }
            const alertaSaldo = document.getElementById('alerta_saldo_masiva');
            if (alertaSaldo) {
                alertaSaldo.style.display = 'none';
            }
            const saldoHint = document.getElementById('saldo_contrato_masiva_hint');
            if (saldoHint) {
                saldoHint.textContent = '';
            }
        });
    }
    
    // Resetear modal de presupuesto cuando se cierra
    const modalPresupuestoOt = document.getElementById('modalPresupuestoOt');
    if (modalPresupuestoOt) {
        modalPresupuestoOt.addEventListener('hidden.bs.modal', function() {
            // Ocultar mensaje de sin presupuesto
            const mensajeSinPresupuesto = document.getElementById('mensaje_sin_presupuesto');
            if (mensajeSinPresupuesto) {
                mensajeSinPresupuesto.style.display = 'none';
            }
            
            // Mostrar sección de presupuesto (por defecto)
            const seccionPresupuesto = document.getElementById('seccion_presupuesto_modal');
            if (seccionPresupuesto) {
                seccionPresupuesto.style.display = 'block';
            }
            
            // Mostrar botón de imprimir (por defecto)
            const btnImprimir = document.querySelector('#modalPresupuestoOt .btn-imprimir');
            if (btnImprimir) {
                btnImprimir.style.display = 'inline-block';
            }
        });
    }
    
    // Hacer scroll al listado si hay hash en la URL o si hay búsqueda activa
    window.addEventListener('load', function() {
        if (window.location.hash === '#listadoOT' || '{{ !empty($busqueda) ? 'true' : 'false' }}' === 'true') {
            setTimeout(function() {
                const listado = document.getElementById('listadoOT');
                if (listado) {
                    listado.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        }
    });
    
    // Pre-llenar campos si vienen parámetros de requerimiento
    @if(isset($requerimientoComunaId) && $requerimientoComunaId)
        const comunaSelect = document.getElementById('comuna_id');
        if (comunaSelect) {
            comunaSelect.value = {{ $requerimientoComunaId }};
            // Disparar evento change para filtrar establecimientos
            const event = new Event('change');
            comunaSelect.dispatchEvent(event);
        }
    @endif
    
    @if(isset($requerimientoEstablecimientoId) && $requerimientoEstablecimientoId)
        // Esperar un momento para que se carguen los establecimientos
        setTimeout(function() {
            const establecimientoSelect = document.getElementById('establecimiento_id');
            if (establecimientoSelect) {
                establecimientoSelect.value = {{ $requerimientoEstablecimientoId }};
                // Disparar evento change para actualizar RBD
                const event = new Event('change');
                establecimientoSelect.dispatchEvent(event);
            }
        }, 500);
    @endif
    
    @if(isset($requerimientoContratoId) && $requerimientoContratoId)
        const contratoSelect = document.getElementById('contrato_id');
        if (contratoSelect) {
            contratoSelect.value = {{ $requerimientoContratoId }};
            // Disparar evento change para actualizar información del contrato
            const event = new Event('change');
            contratoSelect.dispatchEvent(event);
        }
    @endif
});
</script>
@endpush
