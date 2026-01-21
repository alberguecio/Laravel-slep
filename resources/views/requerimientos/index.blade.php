@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="mb-0">
            <i class="bi bi-clipboard-check"></i> Requerimientos
        </h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Filtros compactos -->
            <form id="formFiltros" method="GET" action="{{ route('requerimientos.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
                <select class="form-select form-select-sm" id="filtro_comuna" name="comuna_id" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
                    <option value="">Todas las comunas</option>
                    @foreach($comunas as $comuna)
                        <option value="{{ $comuna->id }}" {{ request('comuna_id') == $comuna->id ? 'selected' : '' }}>
                            {{ $comuna->nombre }}
                        </option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" id="filtro_contrato" name="contrato_id" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
                    <option value="">Todos los contratos</option>
                    @foreach($contratos as $contrato)
                        <option value="{{ $contrato->id }}" {{ request('contrato_id') == $contrato->id ? 'selected' : '' }}>
                            {{ $contrato->nombre_contrato }}
                        </option>
                    @endforeach
                </select>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="filtro_urgencias" name="solo_urgencias" value="1" {{ request('solo_urgencias') ? 'checked' : '' }} onchange="aplicarFiltroUrgencias(this)">
                    <label class="form-check-label" for="filtro_urgencias" style="white-space: nowrap;">
                        Urgencias
                    </label>
                </div>
                @if(request('comuna_id') || request('contrato_id') || request('solo_urgencias'))
                    <a href="{{ route('requerimientos.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoRequerimiento">
                <i class="bi bi-plus-circle"></i> Nuevo Requerimiento
            </button>
        </div>
    </div>

    <!-- Sección Pendientes -->
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-clock-history"></i> Pendientes
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Fecha</th>
                            <th>Comuna</th>
                            <th>Establecimiento</th>
                            <th>Contrato</th>
                            <th>Vía Solicitud</th>
                            <th>Crear OT</th>
                            <th>Seguimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendientes as $requerimiento)
                        <tr class="{{ $requerimiento->emergencia ? 'table-danger' : '' }} requerimiento-row" 
                            style="{{ $requerimiento->emergencia ? 'background-color: #fff5f5 !important;' : '' }} cursor: pointer;"
                            data-requerimiento-id="{{ $requerimiento->id }}"
                            onclick="abrirModalDetalle({{ $requerimiento->id }})">
                            <td onclick="event.stopPropagation();">
                                @if($requerimiento->emergencia)
                                    <i class="bi bi-exclamation-triangle-fill text-danger" title="Emergencia"></i>
                                @endif
                            </td>
                            <td>{{ $requerimiento->fecha_ingreso ? $requerimiento->fecha_ingreso->format('d/m/Y') : '-' }}</td>
                            <td>{{ $requerimiento->comuna ? $requerimiento->comuna->nombre : '-' }}</td>
                            <td>{{ $requerimiento->establecimiento ? $requerimiento->establecimiento->nombre : '-' }}</td>
                            <td>{{ $requerimiento->contrato ? $requerimiento->contrato->nombre_contrato : '-' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $requerimiento->via_solicitud }}</span>
                            </td>
                            <td onclick="event.stopPropagation();">
                                @if($requerimiento->comuna_id && $requerimiento->establecimiento_id && $requerimiento->contrato_id)
                                    <a href="{{ route('requerimientos.crear-ot', $requerimiento->id) }}" class="btn btn-sm btn-success" title="Crear OT" onclick="event.stopPropagation();">
                                        <i class="bi bi-plus-circle"></i> Crear OT
                                    </a>
                                @else
                                    <span class="text-muted">Faltan datos</span>
                                @endif
                            </td>
                            <td onclick="event.stopPropagation();">
                                <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); abrirModalSeguimiento({{ $requerimiento->id }})" title="Seguimiento">
                                    <i class="bi bi-journal-text"></i> Ver
                                </button>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); abrirModalEditar({{ $requerimiento->id }})" title="Editar">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No hay requerimientos pendientes</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sección En Proceso -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-gear"></i> En Proceso
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Fecha</th>
                            <th>Comuna</th>
                            <th>Establecimiento</th>
                            <th>Contrato</th>
                            <th>Vía Solicitud</th>
                            <th>Estado</th>
                            <th>Seguimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enProceso as $requerimiento)
                        <tr class="{{ $requerimiento->emergencia ? 'table-danger' : '' }} requerimiento-row" 
                            style="{{ $requerimiento->emergencia ? 'background-color: #fff5f5 !important;' : '' }} cursor: pointer;"
                            data-requerimiento-id="{{ $requerimiento->id }}"
                            onclick="abrirModalDetalle({{ $requerimiento->id }})">
                            <td onclick="event.stopPropagation();">
                                @if($requerimiento->emergencia)
                                    <i class="bi bi-exclamation-triangle-fill text-danger" title="Emergencia"></i>
                                @endif
                            </td>
                            <td>{{ $requerimiento->fecha_ingreso ? $requerimiento->fecha_ingreso->format('d/m/Y') : '-' }}</td>
                            <td>{{ $requerimiento->comuna ? $requerimiento->comuna->nombre : '-' }}</td>
                            <td>{{ $requerimiento->establecimiento ? $requerimiento->establecimiento->nombre : '-' }}</td>
                            <td>{{ $requerimiento->contrato ? $requerimiento->contrato->nombre_contrato : '-' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $requerimiento->via_solicitud }}</span>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); abrirModalFinalizar({{ $requerimiento->id }})" title="Finalizar Requerimiento">
                                    <i class="bi bi-check-circle"></i> Finalizado
                                </button>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); abrirModalSeguimiento({{ $requerimiento->id }})" title="Seguimiento">
                                    <i class="bi bi-journal-text"></i> Ver
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay requerimientos en proceso</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sección Resueltos -->
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-check-circle"></i> Resueltos
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Fecha</th>
                            <th>Comuna</th>
                            <th>Establecimiento</th>
                            <th>Contrato</th>
                            <th>Vía Solicitud</th>
                            <th>Seguimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resueltos as $requerimiento)
                        <tr class="{{ $requerimiento->emergencia ? 'table-danger' : '' }} requerimiento-row" 
                            style="{{ $requerimiento->emergencia ? 'background-color: #fff5f5 !important;' : '' }} cursor: pointer;"
                            data-requerimiento-id="{{ $requerimiento->id }}"
                            onclick="abrirModalDetalle({{ $requerimiento->id }})">
                            <td onclick="event.stopPropagation();">
                                @if($requerimiento->emergencia)
                                    <i class="bi bi-exclamation-triangle-fill text-danger" title="Emergencia"></i>
                                @endif
                            </td>
                            <td>{{ $requerimiento->fecha_ingreso ? $requerimiento->fecha_ingreso->format('d/m/Y') : '-' }}</td>
                            <td>{{ $requerimiento->comuna ? $requerimiento->comuna->nombre : '-' }}</td>
                            <td>{{ $requerimiento->establecimiento ? $requerimiento->establecimiento->nombre : '-' }}</td>
                            <td>{{ $requerimiento->contrato ? $requerimiento->contrato->nombre_contrato : '-' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $requerimiento->via_solicitud }}</span>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); abrirModalSeguimiento({{ $requerimiento->id }})" title="Seguimiento">
                                    <i class="bi bi-journal-text"></i> Ver
                                </button>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); eliminarRequerimiento({{ $requerimiento->id }})" title="Eliminar">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay requerimientos resueltos</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Requerimiento -->
<div class="modal fade" id="modalNuevoRequerimiento" tabindex="-1" aria-labelledby="modalNuevoRequerimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevoRequerimientoLabel">
                    <i class="bi bi-plus-circle"></i> Nuevo Requerimiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoRequerimiento" method="POST" action="{{ route('requerimientos.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Emergencia -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="emergencia" id="emergencia" value="1">
                                <label class="form-check-label" for="emergencia">
                                    <strong>Emergencia</strong>
                                </label>
                            </div>
                        </div>

                        <!-- Comuna -->
                        <div class="col-md-6">
                            <label for="comuna_id" class="form-label">Comuna <span class="text-danger">*</span></label>
                            <select class="form-select" id="comuna_id" name="comuna_id" required onchange="filtrarEstablecimientosPorComuna()">
                                <option value="">Seleccionar...</option>
                                @foreach($comunas as $comuna)
                                <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Establecimiento -->
                        <div class="col-md-6 position-relative">
                            <label for="establecimiento_id" class="form-label">Establecimiento</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="establecimiento_buscar" 
                                   placeholder="Buscar establecimiento..."
                                   autocomplete="off">
                            <input type="hidden" id="establecimiento_id" name="establecimiento_id" value="">
                            <div id="establecimiento-dropdown" class="dropdown-menu w-100" style="display: none; max-height: 300px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000;"></div>
                        </div>

                        <!-- Contrato -->
                        <div class="col-12">
                            <label for="contrato_id" class="form-label">Contrato</label>
                            <select class="form-select" id="contrato_id" name="contrato_id">
                                <option value="">Seleccionar...</option>
                                @foreach($contratos as $contrato)
                                <option value="{{ $contrato->id }}">
                                    {{ $contrato->nombre_contrato }} 
                                    @if($contrato->proyecto)
                                        - {{ $contrato->proyecto->nombre }}
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Detalle de Requerimiento -->
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Detalle de Requerimiento <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required placeholder="Ingrese el detalle del requerimiento..."></textarea>
                        </div>

                        <!-- Vía Solicitud -->
                        <div class="col-12">
                            <label for="via_solicitud" class="form-label">Vía Solicitud <span class="text-danger">*</span></label>
                            <select class="form-select" id="via_solicitud" name="via_solicitud" required onchange="mostrarCamposViaSolicitud()">
                                <option value="">Seleccionar...</option>
                                <option value="Email">Email</option>
                                <option value="Oficio">Oficio</option>
                                <option value="Telefono">Teléfono</option>
                            </select>
                        </div>

                        <!-- Campos condicionales según vía de solicitud -->
                        <!-- Email -->
                        <div class="col-12" id="campo-email" style="display: none;">
                            <label for="fecha_email" class="form-label">Fecha Email <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_email" name="fecha_email">
                        </div>

                        <!-- Oficio -->
                        <div class="col-md-6" id="campo-oficio-numero" style="display: none;">
                            <label for="numero_oficio" class="form-label">Número de Oficio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numero_oficio" name="numero_oficio" maxlength="50">
                        </div>
                        <div class="col-md-6" id="campo-oficio-fecha" style="display: none;">
                            <label for="fecha_oficio" class="form-label">Fecha de Oficio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_oficio" name="fecha_oficio">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Requerimiento -->
<div class="modal fade" id="modalEditarRequerimiento" tabindex="-1" aria-labelledby="modalEditarRequerimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="modalEditarRequerimientoLabel">
                    <i class="bi bi-pencil"></i> Editar Requerimiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarRequerimiento" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" id="editar_requerimiento_id" name="requerimiento_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Emergencia -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="emergencia" id="editar_emergencia" value="1">
                                <label class="form-check-label" for="editar_emergencia">
                                    <strong>Emergencia</strong>
                                </label>
                            </div>
                        </div>

                        <!-- Comuna -->
                        <div class="col-md-6">
                            <label for="editar_comuna_id" class="form-label">Comuna <span class="text-danger">*</span></label>
                            <select class="form-select" id="editar_comuna_id" name="comuna_id" required onchange="filtrarEstablecimientosPorComunaEditar()">
                                <option value="">Seleccionar...</option>
                                @foreach($comunas as $comuna)
                                <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Establecimiento -->
                        <div class="col-md-6 position-relative">
                            <label for="editar_establecimiento_id" class="form-label">Establecimiento</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="editar_establecimiento_buscar" 
                                   placeholder="Buscar establecimiento..."
                                   autocomplete="off">
                            <input type="hidden" id="editar_establecimiento_id" name="establecimiento_id" value="">
                            <div id="editar_establecimiento-dropdown" class="dropdown-menu w-100" style="display: none; max-height: 300px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000;"></div>
                        </div>

                        <!-- Contrato -->
                        <div class="col-12">
                            <label for="editar_contrato_id" class="form-label">Contrato</label>
                            <select class="form-select" id="editar_contrato_id" name="contrato_id">
                                <option value="">Seleccionar...</option>
                                @foreach($contratos as $contrato)
                                <option value="{{ $contrato->id }}">
                                    {{ $contrato->nombre_contrato }} 
                                    @if($contrato->proyecto)
                                        - {{ $contrato->proyecto->nombre }}
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Detalle de Requerimiento -->
                        <div class="col-12">
                            <label for="editar_descripcion" class="form-label">Detalle de Requerimiento <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="editar_descripcion" name="descripcion" rows="4" required placeholder="Ingrese el detalle del requerimiento..."></textarea>
                        </div>

                        <!-- Vía Solicitud -->
                        <div class="col-12">
                            <label for="editar_via_solicitud" class="form-label">Vía Solicitud <span class="text-danger">*</span></label>
                            <select class="form-select" id="editar_via_solicitud" name="via_solicitud" required onchange="mostrarCamposViaSolicitudEditar()">
                                <option value="">Seleccionar...</option>
                                <option value="Email">Email</option>
                                <option value="Oficio">Oficio</option>
                                <option value="Telefono">Teléfono</option>
                            </select>
                        </div>

                        <!-- Campos condicionales según vía de solicitud -->
                        <!-- Email -->
                        <div class="col-12" id="editar_campo-email" style="display: none;">
                            <label for="editar_fecha_email" class="form-label">Fecha Email <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editar_fecha_email" name="fecha_email">
                        </div>

                        <!-- Oficio -->
                        <div class="col-md-6" id="editar_campo-oficio-numero" style="display: none;">
                            <label for="editar_numero_oficio" class="form-label">Número de Oficio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editar_numero_oficio" name="numero_oficio" maxlength="50">
                        </div>
                        <div class="col-md-6" id="editar_campo-oficio-fecha" style="display: none;">
                            <label for="editar_fecha_oficio" class="form-label">Fecha de Oficio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editar_fecha_oficio" name="fecha_oficio">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const establecimientoBuscar = document.getElementById('establecimiento_buscar');
    const establecimientoId = document.getElementById('establecimiento_id');
    const establecimientoDropdown = document.getElementById('establecimiento-dropdown');
    const comunaId = document.getElementById('comuna_id');
    
    let timeoutEstablecimiento = null;
    
    // Buscar establecimientos
    establecimientoBuscar.addEventListener('input', function() {
        const termino = this.value.trim();
        const comunaIdValue = comunaId.value;
        
        clearTimeout(timeoutEstablecimiento);
        
        if (termino.length < 2) {
            establecimientoDropdown.style.display = 'none';
            establecimientoId.value = '';
            return;
        }
        
        timeoutEstablecimiento = setTimeout(function() {
            let url = '/requerimientos/buscar-establecimientos?q=' + encodeURIComponent(termino);
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
                establecimientoBuscar.value = est.nombre;
                establecimientoId.value = est.id;
                establecimientoDropdown.style.display = 'none';
            });
            establecimientoDropdown.appendChild(item);
        });
        
        establecimientoDropdown.style.display = 'block';
    }
    
    // Ocultar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!establecimientoBuscar.contains(e.target) && !establecimientoDropdown.contains(e.target)) {
            establecimientoDropdown.style.display = 'none';
        }
    });
    
    // Filtrar establecimientos por comuna
    window.filtrarEstablecimientosPorComuna = function() {
        establecimientoBuscar.value = '';
        establecimientoId.value = '';
        establecimientoDropdown.style.display = 'none';
    };
    
    // Mostrar campos según vía de solicitud
    window.mostrarCamposViaSolicitud = function() {
        const viaSolicitud = document.getElementById('via_solicitud').value;
        
        // Ocultar todos los campos
        document.getElementById('campo-email').style.display = 'none';
        document.getElementById('campo-oficio-numero').style.display = 'none';
        document.getElementById('campo-oficio-fecha').style.display = 'none';
        
        // Limpiar campos
        document.getElementById('fecha_email').required = false;
        document.getElementById('numero_oficio').required = false;
        document.getElementById('fecha_oficio').required = false;
        
        // Mostrar campos según selección
        if (viaSolicitud === 'Email') {
            document.getElementById('campo-email').style.display = 'block';
            document.getElementById('fecha_email').required = true;
        } else if (viaSolicitud === 'Oficio') {
            document.getElementById('campo-oficio-numero').style.display = 'block';
            document.getElementById('campo-oficio-fecha').style.display = 'block';
            document.getElementById('numero_oficio').required = true;
            document.getElementById('fecha_oficio').required = true;
        }
    };
    
    // Función para abrir modal de edición
    window.abrirModalEditar = function(requerimientoId) {
        const modal = new bootstrap.Modal(document.getElementById('modalEditarRequerimiento'));
        const form = document.getElementById('formEditarRequerimiento');
        
        // Cargar datos del requerimiento
        fetch('/requerimientos/' + requerimientoId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const req = data.requerimiento;
                    
                    // Llenar formulario
                    document.getElementById('editar_requerimiento_id').value = req.id;
                    document.getElementById('editar_emergencia').checked = req.emergencia;
                    document.getElementById('editar_comuna_id').value = req.comuna_id || '';
                    document.getElementById('editar_establecimiento_id').value = req.establecimiento_id || '';
                    document.getElementById('editar_establecimiento_buscar').value = req.establecimiento || '';
                    document.getElementById('editar_contrato_id').value = req.contrato_id || '';
                    document.getElementById('editar_descripcion').value = req.descripcion || '';
                    document.getElementById('editar_via_solicitud').value = req.via_solicitud || '';
                    
                    // Mostrar campos según vía de solicitud
                    mostrarCamposViaSolicitudEditar();
                    
                    // Llenar campos condicionales
                    if (req.via_solicitud === 'Email' && req.fecha_email) {
                        // Convertir fecha de d/m/Y a Y-m-d
                        const fechaParts = req.fecha_email.split('/');
                        if (fechaParts.length === 3) {
                            document.getElementById('editar_fecha_email').value = fechaParts[2] + '-' + fechaParts[1] + '-' + fechaParts[0];
                        }
                    }
                    if (req.via_solicitud === 'Oficio') {
                        if (req.numero_oficio) {
                            document.getElementById('editar_numero_oficio').value = req.numero_oficio;
                        }
                        if (req.fecha_oficio) {
                            // Convertir fecha de d/m/Y a Y-m-d
                            const fechaParts = req.fecha_oficio.split('/');
                            if (fechaParts.length === 3) {
                                document.getElementById('editar_fecha_oficio').value = fechaParts[2] + '-' + fechaParts[1] + '-' + fechaParts[0];
                            }
                        }
                    }
                    
                    // Actualizar action del formulario
                    form.action = '/requerimientos/' + req.id;
                    
                    modal.show();
                } else {
                    alert('Error al cargar el requerimiento');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el requerimiento');
            });
    };
    
    // Configurar autocompletado para establecimiento en modal de edición
    const editarEstablecimientoBuscar = document.getElementById('editar_establecimiento_buscar');
    const editarEstablecimientoId = document.getElementById('editar_establecimiento_id');
    const editarEstablecimientoDropdown = document.getElementById('editar_establecimiento-dropdown');
    const editarComunaId = document.getElementById('editar_comuna_id');
    
    let timeoutEditarEstablecimiento = null;
    
    if (editarEstablecimientoBuscar) {
        editarEstablecimientoBuscar.addEventListener('input', function() {
            const termino = this.value.trim();
            const comunaIdValue = editarComunaId.value;
            
            clearTimeout(timeoutEditarEstablecimiento);
            
            if (termino.length < 2) {
                editarEstablecimientoDropdown.style.display = 'none';
                editarEstablecimientoId.value = '';
                return;
            }
            
            timeoutEditarEstablecimiento = setTimeout(function() {
                let url = '/requerimientos/buscar-establecimientos?q=' + encodeURIComponent(termino);
                if (comunaIdValue) {
                    url += '&comuna_id=' + comunaIdValue;
                }
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        mostrarEstablecimientosEditar(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 300);
        });
        
        function mostrarEstablecimientosEditar(establecimientos) {
            editarEstablecimientoDropdown.innerHTML = '';
            
            if (establecimientos.length === 0) {
                editarEstablecimientoDropdown.innerHTML = '<div class="dropdown-item text-muted">No se encontraron establecimientos</div>';
                editarEstablecimientoDropdown.style.display = 'block';
                return;
            }
            
            establecimientos.forEach(function(est) {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'dropdown-item';
                item.innerHTML = '<strong>' + est.nombre + '</strong>' + (est.comuna ? ' - ' + est.comuna : '');
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    editarEstablecimientoBuscar.value = est.nombre;
                    editarEstablecimientoId.value = est.id;
                    editarEstablecimientoDropdown.style.display = 'none';
                });
                editarEstablecimientoDropdown.appendChild(item);
            });
            
            editarEstablecimientoDropdown.style.display = 'block';
        }
        
        // Filtrar establecimientos por comuna en edición
        window.filtrarEstablecimientosPorComunaEditar = function() {
            if (editarEstablecimientoBuscar) {
                editarEstablecimientoBuscar.value = '';
                editarEstablecimientoId.value = '';
                editarEstablecimientoDropdown.style.display = 'none';
            }
        };
    }
    
    // Mostrar campos según vía de solicitud en edición
    window.mostrarCamposViaSolicitudEditar = function() {
        const viaSolicitud = document.getElementById('editar_via_solicitud').value;
        const campoEmail = document.getElementById('editar_campo-email');
        const campoOficioNumero = document.getElementById('editar_campo-oficio-numero');
        const campoOficioFecha = document.getElementById('editar_campo-oficio-fecha');
        
        // Ocultar todos los campos
        if (campoEmail) campoEmail.style.display = 'none';
        if (campoOficioNumero) campoOficioNumero.style.display = 'none';
        if (campoOficioFecha) campoOficioFecha.style.display = 'none';
        
        // Limpiar requerimientos
        const fechaEmail = document.getElementById('editar_fecha_email');
        const numeroOficio = document.getElementById('editar_numero_oficio');
        const fechaOficio = document.getElementById('editar_fecha_oficio');
        
        if (fechaEmail) fechaEmail.required = false;
        if (numeroOficio) numeroOficio.required = false;
        if (fechaOficio) fechaOficio.required = false;
        
        // Mostrar campos según la vía seleccionada
        if (viaSolicitud === 'Email') {
            if (campoEmail) campoEmail.style.display = 'block';
            if (fechaEmail) fechaEmail.required = true;
        } else if (viaSolicitud === 'Oficio') {
            if (campoOficioNumero) campoOficioNumero.style.display = 'block';
            if (campoOficioFecha) campoOficioFecha.style.display = 'block';
            if (numeroOficio) numeroOficio.required = true;
            if (fechaOficio) fechaOficio.required = true;
        }
    };
    
    // Limpiar formulario al cerrar modal
    const modal = document.getElementById('modalNuevoRequerimiento');
    modal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('formNuevoRequerimiento').reset();
        establecimientoId.value = '';
        mostrarCamposViaSolicitud();
    });
});

// Función para abrir modal de seguimiento
function abrirModalSeguimiento(requerimientoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
    document.getElementById('requerimiento_id_seguimiento').value = requerimientoId;
    cargarComentarios(requerimientoId);
    modal.show();
}

// Función para cargar comentarios
function cargarComentarios(requerimientoId) {
    const listaComentarios = document.getElementById('listaComentarios');
    listaComentarios.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
    
    fetch('/requerimientos/' + requerimientoId + '/comentarios')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarComentarios(data.comentarios);
            } else {
                listaComentarios.innerHTML = '<div class="alert alert-warning">No se pudieron cargar los comentarios</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            listaComentarios.innerHTML = '<div class="alert alert-danger">Error al cargar los comentarios</div>';
        });
}

// Función para mostrar comentarios
function mostrarComentarios(comentarios) {
    const listaComentarios = document.getElementById('listaComentarios');
    
    if (comentarios.length === 0) {
        listaComentarios.innerHTML = '<div class="text-center text-muted py-3">No hay comentarios aún</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    comentarios.forEach(function(comentario) {
        html += '<div class="list-group-item">';
        html += '<div class="d-flex w-100 justify-content-between mb-2">';
        html += '<h6 class="mb-1"><i class="bi bi-person-circle"></i> ' + comentario.usuario + '</h6>';
        html += '<small class="text-muted"><i class="bi bi-clock"></i> ' + comentario.fecha + '</small>';
        html += '</div>';
        html += '<p class="mb-0">' + comentario.comentario.replace(/\n/g, '<br>') + '</p>';
        html += '</div>';
    });
    html += '</div>';
    
    listaComentarios.innerHTML = html;
}

// Función para agregar comentario
function agregarComentario() {
    const requerimientoId = document.getElementById('requerimiento_id_seguimiento').value;
    const comentario = document.getElementById('nuevo_comentario').value.trim();
    
    if (!comentario) {
        alert('Por favor ingrese un comentario');
        return;
    }
    
    if (!requerimientoId) {
        alert('Error: No se encontró el ID del requerimiento');
        return;
    }
    
    const btnAgregar = document.getElementById('btnAgregarComentario');
    btnAgregar.disabled = true;
    btnAgregar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...';
    
    // Obtener token CSRF
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        const tokenInput = document.querySelector('input[name="_token"]');
        csrfToken = tokenInput ? tokenInput.value : null;
    }
    
    console.log('Agregando comentario:', {
        requerimientoId: requerimientoId,
        comentario: comentario.substring(0, 50) + '...',
        csrfToken: csrfToken ? 'Presente' : 'FALTANTE'
    });
    
    fetch('/requerimientos/' + requerimientoId + '/comentarios', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            comentario: comentario
        })
    })
    .then(response => {
        console.log('Respuesta recibida:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok
        });
        
        // Intentar parsear la respuesta
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', text);
                throw new Error('Error en la respuesta del servidor: ' + text.substring(0, 100));
            }
        }).then(data => {
            if (!response.ok) {
                throw data;
            }
            return data;
        });
    })
    .then(data => {
        console.log('Comentario agregado exitosamente:', data);
        if (data.success) {
            document.getElementById('nuevo_comentario').value = '';
            cargarComentarios(requerimientoId);
        } else {
            alert(data.message || 'Error al agregar el comentario');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        const mensaje = error.message || (error.error || error) || 'Error al agregar el comentario. Por favor, intente nuevamente.';
        alert(mensaje);
    })
    .finally(() => {
        btnAgregar.disabled = false;
        btnAgregar.innerHTML = '<i class="bi bi-plus-circle"></i> Agregar Comentario';
    });
}

// Función para abrir modal de detalle
function abrirModalDetalle(requerimientoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleRequerimiento'));
    const contenido = document.getElementById('contenidoDetalleRequerimiento');
    
    // Mostrar spinner
    contenido.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
    modal.show();
    
    // Cargar datos del requerimiento
    fetch('/requerimientos/' + requerimientoId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetalleRequerimiento(data.requerimiento);
            } else {
                contenido.innerHTML = '<div class="alert alert-danger">Error al cargar el requerimiento</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contenido.innerHTML = '<div class="alert alert-danger">Error al cargar el requerimiento</div>';
        });
}

// Función para mostrar detalle del requerimiento
function mostrarDetalleRequerimiento(req) {
    const contenido = document.getElementById('contenidoDetalleRequerimiento');
    
    let html = '<div class="row g-3">';
    
    // Fecha de Ingreso
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Fecha de Ingreso</label>';
    html += '<p class="form-control-plaintext">' + req.fecha_ingreso + '</p>';
    html += '</div>';
    
    // Estado
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Estado</label>';
    let estadoBadge = '';
    if (req.estado === 'pendiente') {
        estadoBadge = '<span class="badge bg-warning">Pendiente</span>';
    } else if (req.estado === 'en proceso' || req.estado === 'en_proceso' || req.estado === 'proceso') {
        estadoBadge = '<span class="badge bg-info">En Proceso</span>';
    } else if (req.estado === 'resuelto') {
        estadoBadge = '<span class="badge bg-success">Resuelto</span>';
    } else {
        estadoBadge = '<span class="badge bg-secondary">' + req.estado + '</span>';
    }
    html += '<p class="form-control-plaintext">' + estadoBadge + '</p>';
    html += '</div>';
    
    // Emergencia
    if (req.emergencia) {
        html += '<div class="col-12">';
        html += '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Emergencia</strong></div>';
        html += '</div>';
    }
    
    // Comuna
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Comuna</label>';
    html += '<p class="form-control-plaintext">' + req.comuna + '</p>';
    html += '</div>';
    
    // Establecimiento
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Establecimiento</label>';
    html += '<p class="form-control-plaintext">' + req.establecimiento + '</p>';
    html += '</div>';
    
    // Contrato
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Contrato</label>';
    html += '<p class="form-control-plaintext">' + req.contrato + '</p>';
    html += '</div>';
    
    // Vía de Solicitud
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Vía de Solicitud</label>';
    html += '<p class="form-control-plaintext"><span class="badge bg-info">' + req.via_solicitud + '</span></p>';
    html += '</div>';
    
    // Campos según vía de solicitud
    if (req.via_solicitud === 'Email' && req.fecha_email) {
        html += '<div class="col-md-6">';
        html += '<label class="form-label fw-bold">Fecha Email</label>';
        html += '<p class="form-control-plaintext">' + req.fecha_email + '</p>';
        html += '</div>';
    }
    
    if (req.via_solicitud === 'Oficio') {
        if (req.numero_oficio) {
            html += '<div class="col-md-6">';
            html += '<label class="form-label fw-bold">Número de Oficio</label>';
            html += '<p class="form-control-plaintext">' + req.numero_oficio + '</p>';
            html += '</div>';
        }
        if (req.fecha_oficio) {
            html += '<div class="col-md-6">';
            html += '<label class="form-label fw-bold">Fecha de Oficio</label>';
            html += '<p class="form-control-plaintext">' + req.fecha_oficio + '</p>';
            html += '</div>';
        }
    }
    
    // Descripción
    html += '<div class="col-12">';
    html += '<label class="form-label fw-bold">Detalle del Requerimiento</label>';
    html += '<div class="border rounded p-3 bg-light">' + req.descripcion.replace(/\n/g, '<br>') + '</div>';
    html += '</div>';
    
    // Usuario Creador
    html += '<div class="col-md-6">';
    html += '<label class="form-label fw-bold">Usuario Creador</label>';
    html += '<p class="form-control-plaintext">' + req.usuario_creador + '</p>';
    html += '</div>';
    
    // Usuario Modificación
    if (req.usuario_mod) {
        html += '<div class="col-md-6">';
        html += '<label class="form-label fw-bold">Última Modificación por</label>';
        html += '<p class="form-control-plaintext">' + req.usuario_mod;
        if (req.fecha_mod) {
            html += ' <small class="text-muted">(' + req.fecha_mod + ')</small>';
        }
        html += '</p>';
        html += '</div>';
    }
    
    html += '</div>';
    
    contenido.innerHTML = html;
}

// Función para aplicar filtro de urgencias
function aplicarFiltroUrgencias(checkbox) {
    const form = checkbox.closest('form');
    const url = new URL(form.action);
    
    // Limpiar parámetros existentes
    url.searchParams.delete('solo_urgencias');
    url.searchParams.delete('comuna_id');
    url.searchParams.delete('contrato_id');
    
    // Mantener comuna y contrato si existen
    const comunaSelect = document.getElementById('filtro_comuna');
    const contratoSelect = document.getElementById('filtro_contrato');
    
    if (comunaSelect && comunaSelect.value) {
        url.searchParams.set('comuna_id', comunaSelect.value);
    }
    if (contratoSelect && contratoSelect.value) {
        url.searchParams.set('contrato_id', contratoSelect.value);
    }
    
    // Si el checkbox está marcado, agregar el filtro de urgencias
    if (checkbox.checked) {
        url.searchParams.set('solo_urgencias', '1');
    }
    
    // Redirigir con los nuevos parámetros
    window.location.href = url.toString();
}

// Función para eliminar requerimiento
function eliminarRequerimiento(requerimientoId) {
    if (!confirm('¿Está seguro de que desea eliminar este requerimiento? Esta acción no se puede deshacer.')) {
        return;
    }
    
    // Obtener token CSRF
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        const tokenInput = document.querySelector('input[name="_token"]');
        csrfToken = tokenInput ? tokenInput.value : null;
    }
    
    fetch('/requerimientos/' + requerimientoId, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }
        return response.json().then(err => Promise.reject(err));
    })
    .then(data => {
        if (data.success) {
            // Recargar la página para actualizar la lista
            window.location.reload();
        } else {
            alert('Error al eliminar el requerimiento: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el requerimiento: ' + (error.message || 'Error desconocido'));
    });
}

// Función para abrir modal de finalizar
function abrirModalFinalizar(requerimientoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalFinalizar'));
    document.getElementById('requerimiento_id_finalizar').value = requerimientoId;
    document.getElementById('situacion_final').value = '';
    modal.show();
}

// Manejar envío del formulario de finalizar
document.addEventListener('DOMContentLoaded', function() {
    const formFinalizar = document.getElementById('formFinalizarRequerimiento');
    if (!formFinalizar) {
        console.warn('Formulario formFinalizarRequerimiento no encontrado');
        return;
    }
    
    formFinalizar.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const requerimientoId = document.getElementById('requerimiento_id_finalizar').value;
    const situacionFinal = document.getElementById('situacion_final').value.trim();
    
    if (!situacionFinal) {
        alert('Por favor ingrese la situación final del requerimiento');
        return;
    }
    
    const btnFinalizar = document.getElementById('btnFinalizarRequerimiento');
    btnFinalizar.disabled = true;
    btnFinalizar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Finalizando...';
    
    // Obtener token CSRF
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        const tokenInput = document.querySelector('input[name="_token"]');
        csrfToken = tokenInput ? tokenInput.value : null;
    }
    
    console.log('Finalizando requerimiento:', {
        requerimientoId: requerimientoId,
        situacionFinal: situacionFinal.substring(0, 50) + '...',
        csrfToken: csrfToken ? 'Presente' : 'FALTANTE'
    });
    
    fetch('/requerimientos/' + requerimientoId + '/finalizar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            situacion_final: situacionFinal
        })
    })
    .then(response => {
        console.log('Respuesta recibida:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok
        });
        
        // Intentar parsear la respuesta
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', text);
                throw new Error('Error en la respuesta del servidor: ' + text.substring(0, 100));
            }
        }).then(data => {
            if (!response.ok) {
                throw data;
            }
            return data;
        });
    })
    .then(data => {
        console.log('Respuesta del servidor:', data);
        if (data.success) {
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFinalizar'));
            if (modal) {
                modal.hide();
            }
            
            // Limpiar el formulario
            document.getElementById('situacion_final').value = '';
            
            // Recargar la página para ver los cambios
            setTimeout(function() {
                window.location.href = '/requerimientos';
            }, 500);
        } else {
            alert(data.message || 'Error al finalizar el requerimiento');
            btnFinalizar.disabled = false;
            btnFinalizar.innerHTML = '<i class="bi bi-check-circle"></i> Finalizar';
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        const mensaje = error.message || (error.error || error) || 'Error al finalizar el requerimiento. Por favor, intente nuevamente.';
        alert(mensaje);
        btnFinalizar.disabled = false;
        btnFinalizar.innerHTML = '<i class="bi bi-check-circle"></i> Finalizar';
    });
    }); // Cierre del addEventListener
}); // Cierre del DOMContentLoaded
</script>

<!-- Modal Seguimiento -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1" aria-labelledby="modalSeguimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalSeguimientoLabel">
                    <i class="bi bi-journal-text"></i> Seguimiento de Requerimiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="requerimiento_id_seguimiento" value="">
                
                <!-- Lista de comentarios -->
                <div class="mb-4">
                    <h6 class="mb-3"><i class="bi bi-chat-left-text"></i> Bitácora de Comentarios</h6>
                    <div id="listaComentarios" style="max-height: 400px; overflow-y: auto;">
                        <!-- Los comentarios se cargarán aquí -->
                    </div>
                </div>
                
                <!-- Formulario para agregar comentario -->
                <div class="border-top pt-3">
                    <h6 class="mb-3"><i class="bi bi-plus-circle"></i> Agregar Comentario</h6>
                    <div class="mb-3">
                        <textarea class="form-control" id="nuevo_comentario" rows="3" placeholder="Escriba su comentario aquí..."></textarea>
                    </div>
                    <button type="button" class="btn btn-primary" id="btnAgregarComentario" onclick="agregarComentario()">
                        <i class="bi bi-plus-circle"></i> Agregar Comentario
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Requerimiento -->
<div class="modal fade" id="modalDetalleRequerimiento" tabindex="-1" aria-labelledby="modalDetalleRequerimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleRequerimientoLabel">
                    <i class="bi bi-file-text"></i> Detalle del Requerimiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="contenidoDetalleRequerimiento">
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Finalizar Requerimiento -->
<div class="modal fade" id="modalFinalizar" tabindex="-1" aria-labelledby="modalFinalizarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalFinalizarLabel">
                    <i class="bi bi-check-circle"></i> Finalizar Requerimiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formFinalizarRequerimiento">
                <div class="modal-body">
                    <input type="hidden" id="requerimiento_id_finalizar" value="">
                    
                    <div class="mb-3">
                        <label for="situacion_final" class="form-label">
                            <strong>Situación Final Requerimiento</strong> <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="situacion_final" name="situacion_final" rows="5" required placeholder="Describa la situación final del requerimiento..."></textarea>
                        <small class="text-muted">Este texto se guardará en la bitácora de comentarios como último mensaje.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnFinalizarRequerimiento">
                        <i class="bi bi-check-circle"></i> Finalizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

