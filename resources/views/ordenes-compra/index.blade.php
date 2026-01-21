@extends('layouts.app')

@section('content')
<style>
    /* Ocultar spinners (flechas) de campos numéricos */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    input[type="number"] {
        -moz-appearance: textfield;
    }
    
    /* Asegurar que los campos numéricos sean editables */
    input[type="number"]:not([readonly]):not([disabled]) {
        pointer-events: auto;
        cursor: text;
    }
</style>
<div class="container-fluid">
    <!-- Título -->
    <h4 class="mb-4">
        <i class="bi bi-cart-check"></i> Gestión de Órdenes de Compra
    </h4>

    <!-- Formulario de Orden de Compra -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Crear Orden de Compra</h5>
        </div>
        <div class="card-body">
            <form id="formOrdenCompra" action="{{ route('ordenes-compra.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="methodFieldOC" value="POST">
                <input type="hidden" name="oc_id" id="oc_id" value="">
                
                <!-- Selección de Contrato -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Contrato *</label>
                        <select class="form-select" name="contrato_id" id="contrato_id" required>
                            <option value="">Seleccionar contrato...</option>
                            @foreach($contratos as $contrato)
                            <option value="{{ $contrato->id }}" 
                                data-orden-compra="{{ $contrato->orden_compra ?? '' }}"
                                data-fecha-oc="{{ $contrato->fecha_oc ? $contrato->fecha_oc->format('Y-m-d') : '' }}">
                                {{ $contrato->nombre_contrato }} ({{ $contrato->numero_contrato }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Se muestran contratos con OTs disponibles</small>
                    </div>
                </div>

                <!-- N° OC y Fecha OC -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">N° Orden de Compra *</label>
                        <input type="text" class="form-control" name="numero" id="numero_oc" required>
                        <small class="text-muted">Se precarga desde el contrato si existe, pero es editable</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fecha Orden de Compra *</label>
                        <input type="date" class="form-control" name="fecha" id="fecha_oc" required>
                    </div>
                </div>

                <!-- Botón Asociar OTs -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-outline-primary" id="btnAsociarOTs" disabled>
                            <i class="bi bi-link-45deg"></i> Asociar Órdenes de Trabajo
                        </button>
                        <span id="contadorOTs" class="ms-3 fw-bold text-primary" style="display: none;">
                            <i class="bi bi-check-circle"></i> <span id="cantidadOTs">0</span> OT(s) asociada(s)
                        </span>
                        <small class="text-muted d-block mt-2">Seleccione un contrato primero</small>
                    </div>
                </div>

                <!-- Monto Total -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Monto Total *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-end fw-bold" id="monto_total" required readonly style="font-size: 1.25rem; width: 180px;">
                            <input type="hidden" id="monto_total_hidden" name="monto_total">
                        </div>
                        <small class="text-muted">Se calcula automáticamente desde las OTs seleccionadas</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Monto Oficial Mercado Público</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-end" id="monto_mercado_publico" name="monto_mercado_publico" style="width: 180px;">
                            <input type="hidden" id="monto_mercado_publico_hidden" name="monto_mercado_publico">
                        </div>
                        <small class="text-muted">Monto oficial de Mercado Público (se usa en formularios y facturas). Se llena automáticamente con el monto total, pero puede editarse si hay diferencia.</small>
                    </div>
                </div>

                <!-- Estado -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estado *</label>
                        <select class="form-select" name="estado" id="estado_oc" required>
                            <option value="">Seleccionar...</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Pagado" id="opcion_pagado" disabled>Pagado (requiere datos de facturación)</option>
                        </select>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="3"></textarea>
                    </div>
                </div>

                <!-- Sección Facturación -->
                <div class="card border-primary mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-receipt"></i> Facturación</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Factura (Número factura)</label>
                                <input type="text" class="form-control" name="factura" id="factura">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto Factura</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control text-end" name="monto_factura" id="monto_factura" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fecha Factura</label>
                                <input type="date" class="form-control" name="fecha_factura" id="fecha_factura">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha Recepción Factura</label>
                                <input type="date" class="form-control" name="fecha_recepcion_factura" id="fecha_recepcion_factura">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mes (Estimado pago)</label>
                                <select class="form-select" name="mes_estimado_pago" id="mes_estimado_pago">
                                    <option value="">Seleccionar...</option>
                                    <option value="Enero">Enero</option>
                                    <option value="Febrero">Febrero</option>
                                    <option value="Marzo">Marzo</option>
                                    <option value="Abril">Abril</option>
                                    <option value="Mayo">Mayo</option>
                                    <option value="Junio">Junio</option>
                                    <option value="Julio">Julio</option>
                                    <option value="Agosto">Agosto</option>
                                    <option value="Septiembre">Septiembre</option>
                                    <option value="Octubre">Octubre</option>
                                    <option value="Noviembre">Noviembre</option>
                                    <option value="Diciembre">Diciembre</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón Guardar -->
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary" id="btnGuardarOC">
                            <i class="bi bi-save"></i> <span id="textoBotonGuardar">Guardar Orden de Compra</span>
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnCancelarEdicion" style="display: none;" onclick="cancelarEdicion()">
                            <i class="bi bi-x-circle"></i> Cancelar Edición
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Listado de Órdenes de Compra Registradas -->
<div class="card shadow mt-4" id="listadoOC">
    <div class="card-header bg-info text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Órdenes de Compra Registradas</h5>
            <form method="GET" action="{{ route('ordenes-compra.index') }}#listadoOC" class="d-flex" style="max-width: 400px;" id="formBusquedaOC">
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="busqueda" 
                       id="busqueda_oc"
                       value="{{ $busqueda ?? '' }}" 
                       placeholder="Buscar OC, contrato...">
                <button type="submit" class="btn btn-sm btn-light ms-2">
                    <i class="bi bi-search"></i>
                </button>
                @if(!empty($busqueda))
                <a href="{{ route('ordenes-compra.index') }}#listadoOC" class="btn btn-sm btn-outline-light ms-2">
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
            @if($ordenesCompra->count() > 0)
                ({{ $ordenesCompra->count() }} resultado(s))
            @endif
        </div>
        @else
        <div class="text-muted mb-3">
            <i class="bi bi-info-circle"></i> Mostrando las últimas 15 órdenes de compra creadas
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>N° OC</th>
                        <th>Fecha OC</th>
                        <th>Contrato</th>
                        <th class="text-end">Monto Total</th>
                        <th>Estado</th>
                        <th>OTs Asociadas</th>
                        <th>Formularios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordenesCompra as $oc)
                    <tr style="cursor: pointer;" onclick="verDetalleOC({{ $oc->id }})">
                        <td>{{ $oc->numero }}</td>
                        <td>{{ $oc->fecha ? $oc->fecha->format('d-m-Y') : '-' }}</td>
                        <td>
                            @php
                                // Obtener el contrato desde las OT asociadas
                                $contrato = null;
                                if ($oc->ordenesTrabajo && $oc->ordenesTrabajo->count() > 0) {
                                    $primeraOT = $oc->ordenesTrabajo->first();
                                    $contrato = $primeraOT->contrato;
                                } elseif ($oc->contrato) {
                                    $contrato = $oc->contrato;
                                }
                            @endphp
                            {{ $contrato ? $contrato->nombre_contrato : '-' }}
                        </td>
                        <td class="text-end">$ {{ number_format($oc->monto_total ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @if($oc->estado == 'Pendiente')
                                <span class="badge bg-warning">Pendiente</span>
                            @elseif($oc->estado == 'Aprobado')
                                <span class="badge bg-info">Aprobado</span>
                            @elseif($oc->estado == 'Pagado')
                                <span class="badge bg-success">Pagado</span>
                            @else
                                <span class="badge bg-secondary">{{ $oc->estado ?? '-' }}</span>
                            @endif
                        </td>
                        <td>{{ $oc->ordenesTrabajo ? $oc->ordenesTrabajo->count() : 0 }}</td>
                        <td onclick="event.stopPropagation();">
                            <div class="d-flex flex-column gap-1">
                                @if($oc->rcs_numero)
                                    <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.75rem;">{{ $oc->rcs_numero }}</span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">Sin RCS</span>
                                @endif
                                @if($oc->rcf_numero)
                                    <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.75rem;">{{ $oc->rcf_numero }}</span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">Sin RCF</span>
                                @endif
                            </div>
                        </td>
                        <td onclick="event.stopPropagation();">
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarOC({{ $oc->id }})" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarOC({{ $oc->id }})" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No hay órdenes de compra registradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Asociar Órdenes de Trabajo -->
<div class="modal fade" id="modalAsociarOTs" tabindex="-1" aria-labelledby="modalAsociarOTsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAsociarOTsLabel">
                    <i class="bi bi-link-45deg"></i> Asociar Órdenes de Trabajo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Seleccione las Órdenes de Trabajo que desea asociar a esta OC:</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="checkAllOTs">
                                </th>
                                <th>N° OT</th>
                                <th>Fecha OT</th>
                                <th>Establecimiento</th>
                                <th>Comuna</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyOTs">
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="bi bi-hourglass-split"></i> Cargando...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <strong>Total seleccionado: $ <span id="totalSeleccionado">0</span></strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarAsociar">Confirmar Selección</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle OC -->
<div class="modal fade" id="modalDetalleOC" tabindex="-1" aria-labelledby="modalDetalleOCLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleOCLabel">
                    <i class="bi bi-file-earmark-text"></i> Detalle de Orden de Compra
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <!-- Información General -->
                    <div class="border rounded p-3 mb-3" style="background-color: #f8f9fa;">
                        <h6 class="text-muted mb-3 small text-uppercase fw-semibold">Información General</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <small class="text-muted d-block">N° OC</small>
                                    <span id="detalle_oc_numero" class="fw-medium">-</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Fecha OC</small>
                                    <span id="detalle_oc_fecha" class="fw-medium">-</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Contrato</small>
                                    <span id="detalle_oc_contrato" class="fw-medium">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Monto Total</small>
                                    <span id="detalle_oc_monto" class="fw-medium">-</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Estado</small>
                                    <span id="detalle_oc_estado" class="fw-medium">-</span>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block">Descripción</small>
                                    <span id="detalle_oc_descripcion" class="fw-medium">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de Facturación -->
                    <div class="border rounded p-3" style="background-color: #f8f9fa;">
                        <h6 class="text-muted mb-3 small text-uppercase fw-semibold">Información de Facturación</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Factura</small>
                                    <span id="detalle_oc_factura" class="fw-medium">-</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Monto Factura</small>
                                    <span id="detalle_oc_monto_factura" class="fw-medium">-</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Fecha Factura</small>
                                    <span id="detalle_oc_fecha_factura" class="fw-medium">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Fecha Recepción Factura</small>
                                    <span id="detalle_oc_fecha_recepcion" class="fw-medium">-</span>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block">Mes Estimado Pago</small>
                                    <span id="detalle_oc_mes_pago" class="fw-medium">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Órdenes de Trabajo Asociadas -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Órdenes de Trabajo Asociadas</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 15%;">N° OT</th>
                                    <th style="width: 40%;">Establecimiento</th>
                                    <th style="width: 20%;">Comuna</th>
                                    <th class="text-end" style="width: 20%;">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="detalle_oc_ots_tbody">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex gap-2">
                    <a href="#" id="btnFormularioRCS" class="btn btn-success" target="_blank">
                        <i class="bi bi-file-earmark-check"></i> Recepción Conforme Servicios
                    </a>
                    <a href="#" id="btnFormularioRCF" class="btn btn-primary" target="_blank">
                        <i class="bi bi-receipt"></i> Recepción Conforme Factura
                    </a>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contratoSelect = document.getElementById('contrato_id');
    const numeroOCInput = document.getElementById('numero_oc');
    const fechaOCInput = document.getElementById('fecha_oc');
    const btnAsociarOTs = document.getElementById('btnAsociarOTs');
    const montoTotalInput = document.getElementById('monto_total');
    const modalAsociarOTs = new bootstrap.Modal(document.getElementById('modalAsociarOTs'));
    const tbodyOTs = document.getElementById('tbodyOTs');
    const checkAllOTs = document.getElementById('checkAllOTs');
    const btnConfirmarAsociar = document.getElementById('btnConfirmarAsociar');
    const totalSeleccionadoSpan = document.getElementById('totalSeleccionado');
    
    let ordenesTrabajoDisponibles = [];
    let ordenesTrabajoSeleccionadas = [];
    
    // Referencias a campos de facturación y estado
    const estadoOCSelect = document.getElementById('estado_oc');
    const opcionPagado = document.getElementById('opcion_pagado');
    const facturaInput = document.getElementById('factura');
    const montoFacturaInput = document.getElementById('monto_factura');
    const fechaFacturaInput = document.getElementById('fecha_factura');
    const fechaRecepcionFacturaInput = document.getElementById('fecha_recepcion_factura');
    const mesEstimadoPagoSelect = document.getElementById('mes_estimado_pago');
    
    // Función para verificar si todos los campos de facturación están llenos
    function verificarCamposFacturacion() {
        const factura = facturaInput.value.trim();
        const montoFactura = montoFacturaInput.value.trim();
        const fechaFactura = fechaFacturaInput.value.trim();
        const fechaRecepcionFactura = fechaRecepcionFacturaInput.value.trim();
        const mesEstimadoPago = mesEstimadoPagoSelect.value.trim();
        
        const todosLlenos = factura && montoFactura && fechaFactura && fechaRecepcionFactura && mesEstimadoPago;
        
        // Habilitar/deshabilitar opción "Pagado"
        if (todosLlenos) {
            opcionPagado.disabled = false;
            opcionPagado.textContent = 'Pagado';
        } else {
            opcionPagado.disabled = true;
            opcionPagado.textContent = 'Pagado (requiere datos de facturación)';
            
            // Si el estado actual es "Pagado" y se vació un campo, cambiar a "Aprobado"
            if (estadoOCSelect.value === 'Pagado') {
                estadoOCSelect.value = 'Aprobado';
            }
        }
    }
    
    // Agregar event listeners a todos los campos de facturación
    facturaInput.addEventListener('input', verificarCamposFacturacion);
    facturaInput.addEventListener('change', verificarCamposFacturacion);
    montoFacturaInput.addEventListener('input', verificarCamposFacturacion);
    montoFacturaInput.addEventListener('change', verificarCamposFacturacion);
    
    // Formatear monto_factura cuando el usuario lo edita
    montoFacturaInput.addEventListener('blur', function() {
        const valor = this.value.replace(/[$.]/g, '').replace(',', '.');
        const numero = parseFloat(valor) || 0;
        if (numero > 0) {
            this.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(numero);
        }
    });
    
    fechaFacturaInput.addEventListener('change', verificarCamposFacturacion);
    fechaRecepcionFacturaInput.addEventListener('change', verificarCamposFacturacion);
    mesEstimadoPagoSelect.addEventListener('change', verificarCamposFacturacion);
    
    // Formatear monto_mercado_publico cuando el usuario lo edita
    const montoMercadoPublicoInput = document.getElementById('monto_mercado_publico');
    if (montoMercadoPublicoInput) {
        montoMercadoPublicoInput.addEventListener('blur', function() {
            const valor = this.value.replace(/[$.]/g, '').replace(',', '.');
            const numero = parseFloat(valor) || 0;
            if (numero > 0) {
                this.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(numero);
                const hiddenInput = document.getElementById('monto_mercado_publico_hidden');
                if (hiddenInput) {
                    hiddenInput.value = Math.round(numero);
                }
            }
        });
    }
    
    // Verificar al cargar la página
    verificarCamposFacturacion();

    // Precargar N° OC y Fecha OC cuando se selecciona un contrato
    contratoSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const ordenCompra = selectedOption.getAttribute('data-orden-compra') || '';
            const fechaOC = selectedOption.getAttribute('data-fecha-oc') || '';
            
            numeroOCInput.value = ordenCompra;
            fechaOCInput.value = fechaOC;
            
            btnAsociarOTs.disabled = false;
            montoTotalInput.value = '';
            document.getElementById('monto_total_hidden').value = '';
            ordenesTrabajoSeleccionadas = [];
            document.getElementById('contadorOTs').style.display = 'none';
        } else {
            numeroOCInput.value = '';
            fechaOCInput.value = '';
            btnAsociarOTs.disabled = true;
            montoTotalInput.value = '';
            document.getElementById('monto_total_hidden').value = '';
            ordenesTrabajoSeleccionadas = [];
            document.getElementById('contadorOTs').style.display = 'none';
        }
    });

    // Abrir modal para asociar OTs
    btnAsociarOTs.addEventListener('click', function() {
        const contratoId = contratoSelect.value;
        if (!contratoId) {
            alert('Por favor seleccione un contrato primero');
            return;
        }
        
        // Obtener ID de OC si está en modo edición
        const ocId = document.getElementById('oc_id').value;
        const url = ocId ? `/ordenes-compra/ordenes-trabajo/${contratoId}?oc_id=${ocId}` : `/ordenes-compra/ordenes-trabajo/${contratoId}`;
        
        // Cargar OTs del contrato
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    ordenesTrabajoDisponibles = data.ordenes_trabajo;
                    renderOTsTable();
                    modalAsociarOTs.show();
                } else {
                    alert('Error al cargar las Órdenes de Trabajo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar las Órdenes de Trabajo');
            });
    });

    // Renderizar tabla de OTs
    function renderOTsTable() {
        if (ordenesTrabajoDisponibles.length === 0) {
            tbodyOTs.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay Órdenes de Trabajo disponibles</td></tr>';
            return;
        }
        
        tbodyOTs.innerHTML = ordenesTrabajoDisponibles.map((ot, index) => {
            const isChecked = ordenesTrabajoSeleccionadas.includes(ot.id);
            return `
                <tr>
                    <td>
                        <input type="checkbox" class="check-ot" value="${ot.id}" data-monto="${ot.monto}" ${isChecked ? 'checked' : ''}>
                    </td>
                    <td>${ot.numero_ot || '-'}</td>
                    <td>${ot.fecha_ot || '-'}</td>
                    <td>${ot.establecimiento || '-'}</td>
                    <td>${ot.comuna || '-'}</td>
                    <td class="text-end">$${parseFloat(ot.monto).toLocaleString('es-CL')}</td>
                </tr>
            `;
        }).join('');
        
        // Agregar event listeners a los checkboxes
        document.querySelectorAll('.check-ot').forEach(checkbox => {
            checkbox.addEventListener('change', actualizarTotalSeleccionado);
        });
        
        checkAllOTs.addEventListener('change', function() {
            document.querySelectorAll('.check-ot').forEach(cb => {
                cb.checked = this.checked;
            });
            actualizarTotalSeleccionado();
        });
        
        actualizarTotalSeleccionado();
    }

    // Actualizar total seleccionado
    function actualizarTotalSeleccionado() {
        ordenesTrabajoSeleccionadas = [];
        let total = 0;
        
        document.querySelectorAll('.check-ot:checked').forEach(checkbox => {
            const otId = parseInt(checkbox.value);
            const monto = parseFloat(checkbox.getAttribute('data-monto'));
            ordenesTrabajoSeleccionadas.push(otId);
            total += monto;
        });
        
        totalSeleccionadoSpan.textContent = total.toLocaleString('es-CL', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    // Confirmar selección de OTs
    btnConfirmarAsociar.addEventListener('click', function() {
        if (ordenesTrabajoSeleccionadas.length === 0) {
            alert('Por favor seleccione al menos una Orden de Trabajo');
            return;
        }
        
        // Calcular monto total
        let montoTotal = 0;
        ordenesTrabajoSeleccionadas.forEach(otId => {
            const ot = ordenesTrabajoDisponibles.find(o => o.id === otId);
            if (ot) {
                montoTotal += parseFloat(ot.monto);
            }
        });
        
        const montoRedondeado = Math.round(montoTotal);
        montoTotalInput.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoRedondeado);
        document.getElementById('monto_total_hidden').value = montoRedondeado;
        
        // Llenar automáticamente monto_mercado_publico con el monto_total
        const montoMercadoPublicoInput = document.getElementById('monto_mercado_publico');
        const montoMercadoPublicoHidden = document.getElementById('monto_mercado_publico_hidden');
        if (montoMercadoPublicoInput && montoMercadoPublicoHidden) {
            // Solo llenar si está vacío (para no sobrescribir si el usuario ya lo editó)
            if (!montoMercadoPublicoInput.value || montoMercadoPublicoInput.value.trim() === '') {
                montoMercadoPublicoInput.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoRedondeado);
                montoMercadoPublicoHidden.value = montoRedondeado;
            }
        }
        
        // Precargar monto_factura con el monto_mercado_publico (o monto_total si no hay) si está vacío
        const montoParaFactura = montoMercadoPublicoInput && montoMercadoPublicoInput.value ? 
            parseFloat(montoMercadoPublicoInput.value.replace(/[$.]/g, '').replace(',', '.')) || montoRedondeado : 
            montoRedondeado;
        if (!montoFacturaInput.value || montoFacturaInput.value.trim() === '') {
            // Formatear el valor antes de asignarlo
            montoFacturaInput.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoParaFactura);
            // Disparar evento para verificar campos de facturación
            verificarCamposFacturacion();
        }
        
        // Mostrar contador de OTs seleccionadas
        const cantidadOTs = ordenesTrabajoSeleccionadas.length;
        document.getElementById('cantidadOTs').textContent = cantidadOTs;
        document.getElementById('contadorOTs').style.display = 'inline-block';
        
        modalAsociarOTs.hide();
    });

    // Enviar formulario
    document.getElementById('formOrdenCompra').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (ordenesTrabajoSeleccionadas.length === 0) {
            alert('Por favor asocie al menos una Orden de Trabajo');
            return;
        }
        
        // Validar que si el estado es "Pagado", todos los campos de facturación estén llenos
        if (estadoOCSelect.value === 'Pagado') {
            const factura = facturaInput.value.trim();
            const montoFactura = montoFacturaInput.value.trim();
            const fechaFactura = fechaFacturaInput.value.trim();
            const fechaRecepcionFactura = fechaRecepcionFacturaInput.value.trim();
            const mesEstimadoPago = mesEstimadoPagoSelect.value.trim();
            
            if (!factura || !montoFactura || !fechaFactura || !fechaRecepcionFactura || !mesEstimadoPago) {
                alert('Para seleccionar el estado "Pagado", debe completar todos los campos de facturación');
                return;
            }
        }
        
        const formData = new FormData(this);
        
        // Usar el valor numérico del campo hidden para monto_total
        const montoTotalHidden = document.getElementById('monto_total_hidden').value;
        formData.set('monto_total', montoTotalHidden);
        
        // Obtener monto_mercado_publico: si el campo visible tiene valor, usarlo; si no, usar monto_total
        const montoMercadoPublicoInput = document.getElementById('monto_mercado_publico');
        const montoMercadoPublicoHidden = document.getElementById('monto_mercado_publico_hidden');
        let montoMercadoPublico = montoTotalHidden;
        
        // Primero intentar obtener del campo hidden (más confiable)
        if (montoMercadoPublicoHidden && montoMercadoPublicoHidden.value) {
            montoMercadoPublico = parseFloat(montoMercadoPublicoHidden.value) || montoTotalHidden;
        } 
        // Si no hay hidden, intentar del campo visible
        else if (montoMercadoPublicoInput && montoMercadoPublicoInput.value) {
            // Limpiar formato de moneda del valor visible
            const valorLimpio = montoMercadoPublicoInput.value.replace(/[$.]/g, '').replace(',', '.');
            montoMercadoPublico = parseFloat(valorLimpio) || montoTotalHidden;
            // Actualizar el hidden con el valor limpio
            if (montoMercadoPublicoHidden) {
                montoMercadoPublicoHidden.value = montoMercadoPublico;
            }
        }
        // Si no hay valor en ninguno de los campos, usar monto_total y actualizar ambos campos
        else {
            montoMercadoPublico = montoTotalHidden;
            if (montoMercadoPublicoHidden) {
                montoMercadoPublicoHidden.value = montoMercadoPublico;
            }
            if (montoMercadoPublicoInput) {
                montoMercadoPublicoInput.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoMercadoPublico);
            }
        }
        
        formData.set('monto_mercado_publico', montoMercadoPublico);
        
        // Limpiar formato de monto_factura antes de enviar
        let montoFacturaLimpio = '';
        if (montoFacturaInput && montoFacturaInput.value) {
            // Limpiar formato de moneda: quitar puntos (separadores de miles) y comas (decimales)
            const valorLimpio = montoFacturaInput.value.replace(/[$.]/g, '').replace(',', '.');
            montoFacturaLimpio = parseFloat(valorLimpio) || '';
        }
        if (montoFacturaLimpio !== '') {
            formData.set('monto_factura', montoFacturaLimpio);
        }
        
        // Agregar cada ID de OT como un elemento del array
        ordenesTrabajoSeleccionadas.forEach(otId => {
            formData.append('ordenes_trabajo_ids[]', otId);
        });
        
        // Obtener token CSRF
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     document.querySelector('input[name="_token"]')?.value;
        formData.append('_token', token);
        
        // Agregar _method para PUT si está en modo edición
        const method = document.getElementById('methodFieldOC').value;
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Orden de Compra creada exitosamente');
                // Recargar la página para actualizar el listado
                window.location.reload();
            } else {
                alert(data.message || 'Error al crear la Orden de Compra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear la Orden de Compra');
        });
    });
    
    // Función para editar OC
    window.editarOC = function(id) {
        fetch('/ordenes-compra/' + id, { headers: { 'Accept': 'application/json' }})
            .then(r => r.json())
            .then(data => {
                if (!data.success) { 
                    alert('No se pudo cargar la OC'); 
                    return; 
                }
                const oc = data.oc;
                
                // Cambiar formulario a modo edición
                const form = document.getElementById('formOrdenCompra');
                form.action = '/ordenes-compra/' + id;
                document.getElementById('methodFieldOC').value = 'PUT';
                document.getElementById('oc_id').value = id;
                
                // Llenar campos del formulario
                // Establecer el contrato primero
                const contratoSelect = document.getElementById('contrato_id');
                
                // Obtener contrato_id desde oc.contrato_id o desde las OTs asociadas
                let contratoId = oc.contrato_id;
                if (!contratoId && oc.ordenes_trabajo && oc.ordenes_trabajo.length > 0) {
                    // Si no hay contrato_id directo, obtenerlo de la primera OT
                    const primeraOT = oc.ordenes_trabajo[0];
                    if (primeraOT && primeraOT.contrato_id) {
                        contratoId = primeraOT.contrato_id;
                    }
                }
                
                if (contratoId) {
                    // Verificar si el contrato existe en el dropdown
                    const contratoOption = contratoSelect.querySelector(`option[value="${contratoId}"]`);
                    if (!contratoOption) {
                        // Si no existe, intentar obtenerlo desde oc.contrato o agregarlo
                        if (oc.contrato) {
                            const option = document.createElement('option');
                            option.value = contratoId;
                            option.setAttribute('data-orden-compra', oc.contrato.orden_compra || '');
                            option.setAttribute('data-fecha-oc', oc.contrato.fecha_oc || '');
                            option.textContent = `${oc.contrato.nombre_contrato} (${oc.contrato.numero_contrato})`;
                            contratoSelect.appendChild(option);
                        } else {
                            // Si no tenemos datos del contrato, cargarlo desde el servidor
                            fetch(`/contratos/${contratoId}`, { headers: { 'Accept': 'application/json' }})
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success && data.contrato) {
                                        const option = document.createElement('option');
                                        option.value = contratoId;
                                        option.setAttribute('data-orden-compra', data.contrato.orden_compra || '');
                                        option.setAttribute('data-fecha-oc', data.contrato.fecha_oc || '');
                                        option.textContent = `${data.contrato.nombre_contrato} (${data.contrato.numero_contrato})`;
                                        contratoSelect.appendChild(option);
                                        contratoSelect.value = contratoId;
                                        contratoSelect.dispatchEvent(new Event('change'));
                                    }
                                })
                                .catch(err => console.error('Error cargando contrato:', err));
                        }
                    }
                    if (contratoOption || oc.contrato) {
                        contratoSelect.value = contratoId;
                        // Disparar evento change para precargar datos del contrato si es necesario
                        contratoSelect.dispatchEvent(new Event('change'));
                    }
                }
                document.getElementById('numero_oc').value = oc.numero || '';
                document.getElementById('fecha_oc').value = oc.fecha || '';
                const montoTotal = oc.monto_total ? Math.round(oc.monto_total) : 0;
                document.getElementById('monto_total').value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoTotal);
                document.getElementById('monto_total_hidden').value = montoTotal;
                
                // Cargar monto_mercado_publico: si existe, usarlo; si no, usar monto_total
                const montoMercadoPublico = oc.monto_mercado_publico ? Math.round(oc.monto_mercado_publico) : montoTotal;
                const montoMercadoPublicoInput = document.getElementById('monto_mercado_publico');
                const montoMercadoPublicoHidden = document.getElementById('monto_mercado_publico_hidden');
                if (montoMercadoPublicoInput && montoMercadoPublicoHidden) {
                    montoMercadoPublicoInput.value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoMercadoPublico);
                    montoMercadoPublicoHidden.value = montoMercadoPublico;
                }
                
                document.getElementById('estado_oc').value = oc.estado || '';
                document.getElementById('descripcion').value = oc.descripcion || '';
                document.getElementById('factura').value = oc.factura || '';
                // Precargar monto_factura: usar monto_mercado_publico como prioridad, luego monto_factura si existe
                const montoParaFactura = montoMercadoPublico || (oc.monto_factura ? Math.round(oc.monto_factura) : montoTotal);
                document.getElementById('monto_factura').value = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(montoParaFactura);
                document.getElementById('fecha_factura').value = oc.fecha_factura || '';
                document.getElementById('fecha_recepcion_factura').value = oc.fecha_recepcion_factura || '';
                document.getElementById('mes_estimado_pago').value = oc.mes_estimado_pago || '';
                
                // Verificar campos de facturación después de cargar
                verificarCamposFacturacion();
                
                // Cargar OTs asociadas
                ordenesTrabajoSeleccionadas = oc.ordenes_trabajo_ids || [];
                
                // Mostrar contador de OTs
                const cantidadOTs = ordenesTrabajoSeleccionadas.length;
                document.getElementById('cantidadOTs').textContent = cantidadOTs;
                document.getElementById('contadorOTs').style.display = cantidadOTs > 0 ? 'inline-block' : 'none';
                
                // Habilitar botón de asociar OTs
                btnAsociarOTs.disabled = false;
                
                // Precargar N° OC y Fecha OC desde el contrato
                // Usar contratoId que ya fue determinado arriba
                if (contratoId) {
                    const contratoOption = document.querySelector(`#contrato_id option[value="${contratoId}"]`);
                    if (contratoOption) {
                        const ordenCompra = contratoOption.getAttribute('data-orden-compra') || '';
                        const fechaOC = contratoOption.getAttribute('data-fecha-oc') || '';
                        if (!document.getElementById('numero_oc').value) {
                            document.getElementById('numero_oc').value = ordenCompra;
                        }
                        if (!document.getElementById('fecha_oc').value) {
                            document.getElementById('fecha_oc').value = fechaOC;
                        }
                    }
                    
                    // Cargar OTs del contrato para el modal (incluyendo las ya asociadas)
                    fetch(`/ordenes-compra/ordenes-trabajo/${contratoId}?oc_id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                ordenesTrabajoDisponibles = data.ordenes_trabajo;
                            }
                        });
                }
                
                // Actualizar título del formulario y botones
                document.querySelector('.card-header h5').innerHTML = '<i class="bi bi-file-earmark-plus"></i> Editar Orden de Compra';
                document.getElementById('textoBotonGuardar').textContent = 'Actualizar Orden de Compra';
                document.getElementById('btnCancelarEdicion').style.display = 'inline-block';
                
                // Verificar campos de facturación para habilitar "Pagado"
                verificarCamposFacturacion();
                
                // Scroll al formulario
                document.querySelector('.card.shadow').scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al cargar la OC: ' + err.message);
            });
    };
    
    // Función para cancelar edición
    window.cancelarEdicion = function() {
        const form = document.getElementById('formOrdenCompra');
        form.reset();
        form.action = '{{ route('ordenes-compra.store') }}';
        document.getElementById('methodFieldOC').value = 'POST';
        document.getElementById('oc_id').value = '';
        document.querySelector('.card-header h5').innerHTML = '<i class="bi bi-file-earmark-plus"></i> Crear Orden de Compra';
        document.getElementById('textoBotonGuardar').textContent = 'Guardar Orden de Compra';
        document.getElementById('btnCancelarEdicion').style.display = 'none';
        ordenesTrabajoSeleccionadas = [];
        montoTotalInput.value = '';
        document.getElementById('monto_total_hidden').value = '';
        document.getElementById('monto_mercado_publico').value = '';
        document.getElementById('monto_mercado_publico_hidden').value = '';
        document.getElementById('contadorOTs').style.display = 'none';
        contratoSelect.value = '';
        numeroOCInput.value = '';
        fechaOCInput.value = '';
        btnAsociarOTs.disabled = true;
        verificarCamposFacturacion();
    };
    
    // Resetear formulario cuando se resetea
    document.getElementById('formOrdenCompra').addEventListener('reset', function() {
        cancelarEdicion();
    });
    
    // Función para ver detalle de OC
    window.verDetalleOC = function(id) {
        fetch('/ordenes-compra/' + id, { headers: { 'Accept': 'application/json' }})
            .then(r => r.json())
            .then(data => {
                if (!data.success) { 
                    alert('No se pudo cargar la OC'); 
                    return; 
                }
                const oc = data.oc;
                
                // Llenar información en el modal
                document.getElementById('detalle_oc_numero').textContent = oc.numero || '-';
                document.getElementById('detalle_oc_fecha').textContent = oc.fecha || '-';
                document.getElementById('detalle_oc_contrato').textContent = oc.contrato ? oc.contrato.nombre_contrato : '-';
                document.getElementById('detalle_oc_monto').textContent = '$ ' + (oc.monto_total ? new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(oc.monto_total) : '0');
                document.getElementById('detalle_oc_estado').textContent = oc.estado || '-';
                document.getElementById('detalle_oc_descripcion').textContent = oc.descripcion || '-';
                document.getElementById('detalle_oc_factura').textContent = oc.factura || '-';
                document.getElementById('detalle_oc_monto_factura').textContent = oc.monto_factura ? '$ ' + new Intl.NumberFormat('es-CL').format(oc.monto_factura) : '-';
                document.getElementById('detalle_oc_fecha_factura').textContent = oc.fecha_factura || '-';
                document.getElementById('detalle_oc_fecha_recepcion').textContent = oc.fecha_recepcion_factura || '-';
                document.getElementById('detalle_oc_mes_pago').textContent = oc.mes_estimado_pago || '-';
                
                // Llenar OTs asociadas
                const tbodyOTs = document.getElementById('detalle_oc_ots_tbody');
                tbodyOTs.innerHTML = '';
                if (oc.ordenes_trabajo && oc.ordenes_trabajo.length > 0) {
                    oc.ordenes_trabajo.forEach((ot, index) => {
                        const tr = document.createElement('tr');
                        // Obtener nombre del establecimiento
                        let establecimientoNombre = '-';
                        if (ot.establecimiento) {
                            establecimientoNombre = ot.establecimiento.nombre || '-';
                        }
                        
                        // Obtener nombre de la comuna (usar comuna de OT o del establecimiento como fallback)
                        let comunaNombre = '-';
                        if (ot.comuna && ot.comuna.nombre) {
                            comunaNombre = ot.comuna.nombre;
                        } else if (ot.establecimiento && ot.establecimiento.comuna && ot.establecimiento.comuna.nombre) {
                            comunaNombre = ot.establecimiento.comuna.nombre;
                        }
                        
                        tr.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${ot.numero_ot || '-'}</td>
                            <td>${establecimientoNombre}</td>
                            <td>${comunaNombre}</td>
                            <td class="text-end">$ ${ot.monto ? new Intl.NumberFormat('es-CL').format(ot.monto) : '0'}</td>
                        `;
                        tbodyOTs.appendChild(tr);
                    });
                } else {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td colspan="5" class="text-center text-muted">No hay OTs asociadas</td>';
                    tbodyOTs.appendChild(tr);
                }
                
                // Configurar enlaces de formularios
                document.getElementById('btnFormularioRCS').href = `/ordenes-compra/${id}/formulario-recepcion-servicios`;
                document.getElementById('btnFormularioRCF').href = `/ordenes-compra/${id}/formulario-recepcion-factura`;
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalDetalleOC'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los detalles de la OC');
            });
    };
    
    // Función para eliminar OC
    window.eliminarOC = function(id) {
        if (!confirm('¿Está seguro de que desea eliminar esta Orden de Compra? Esta acción no se puede deshacer.')) {
            return;
        }
        
        fetch('/ordenes-compra/' + id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Orden de Compra eliminada correctamente');
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar la Orden de Compra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la Orden de Compra');
        });
    };
    
    // Hacer scroll al listado si hay hash en la URL o si hay búsqueda activa
    window.addEventListener('load', function() {
        if (window.location.hash === '#listadoOC' || '{{ !empty($busqueda) ? 'true' : 'false' }}' === 'true') {
            setTimeout(function() {
                const listado = document.getElementById('listadoOC');
                if (listado) {
                    listado.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        }
    });
});
</script>
@endpush
@endsection

