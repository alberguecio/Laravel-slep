@php
    // Inicializar variables si no existen (para evitar errores cuando no se está en esta pestaña)
    $montoSubvencionMant = $montoSubvencionMant ?? null;
    $montoSubvencionGeneral = $montoSubvencionGeneral ?? null;
    $montoVTF = $montoVTF ?? null;
    $totalMantenimientoRegulares = $totalMantenimientoRegulares ?? 0;
    $establecimientosVTF = $establecimientosVTF ?? 0;
    $establecimientos = $establecimientos ?? collect();
    $comunas = $comunas ?? collect();
    $sumaMontosRegulares = $sumaMontosRegulares ?? 0;
    $diferenciaMontos = $diferenciaMontos ?? null;
    $hayDiferencia = $hayDiferencia ?? false;
@endphp

<div class="container-fluid">
    <!-- Resumen de Subvenciones -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart"></i> Resumen de Subvenciones
                </h5>
                <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-lightbulb me-2"></i>
                    <small>Ahora puedes editar el aporte de cada establecimiento individualmente en el modal</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="card-body text-white">
                            <h6 class="card-subtitle mb-2 text-white-50">Subvención Mantenimiento</h6>
                            <h3 class="mb-2">$ {{ number_format($montoSubvencionMant->monto ?? $totalMantenimientoRegulares, 0, ',', '.') }}</h3>
                            <small class="text-white-50">(Suma de establecimientos regulares, sin jardines VTF)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <div class="card-body text-white">
                            <h6 class="card-subtitle mb-2 text-white-50">Subvención General (Aporte)</h6>
                            <h3 class="mb-2">$ {{ number_format($montoSubvencionGeneral->monto ?? 0, 0, ',', '.') }}</h3>
                            <small class="text-white-50">(Monto que se reparte entre los {{ $establecimientos->count() > 0 ? '210' : '0' }} establecimientos)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);">
                        <div class="card-body text-white">
                            <h6 class="card-subtitle mb-2 text-white-50">Mantención VTF</h6>
                            <h3 class="mb-2">$ {{ number_format($montoVTF->monto ?? 0, 0, ',', '.') }}</h3>
                            <small class="text-white-50">(Monto que se reparte entre {{ $establecimientosVTF }} jardines VTF)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de Comparación de Montos (Siempre visible) -->
    @if(!empty($montoSubvencionMant) && isset($sumaMontosRegulares) && isset($diferenciaMontos))
        @php
            $mostrarAlerta = abs($diferenciaMontos) > 0.01; // Hay diferencia si es > $0.01
        @endphp
        @if($mostrarAlerta)
        <div class="alert alert-{{ abs($diferenciaMontos) > 1000 ? 'danger' : 'warning' }} alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-exclamation-triangle-fill"></i> 
                @if(abs($diferenciaMontos) > 1000)
                    ⚠️ Diferencia Significativa Detectada
                @else
                    ⚠️ Diferencia en Montos Detectada
                @endif
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2">
                        <strong>Monto configurado en Presupuestos:</strong><br>
                        <span class="fs-5 text-primary">$ {{ number_format($montoSubvencionMant->monto ?? 0, 0, ',', '.') }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2">
                        <strong>Suma de montos de establecimientos regulares:</strong><br>
                        <span class="fs-5 text-secondary">$ {{ number_format($sumaMontosRegulares, 0, ',', '.') }}</span>
                    </p>
                </div>
            </div>
            <p class="mb-2">
                <strong>Diferencia:</strong> 
                <span class="badge bg-{{ abs($diferenciaMontos) > 1000 ? 'danger' : ($diferenciaMontos > 0 ? 'warning' : 'info') }} fs-6">
                    $ {{ number_format(abs($diferenciaMontos), 2, ',', '.') }}
                    {{ $diferenciaMontos > 0 ? '(Faltante)' : '(Excedente)' }}
                </span>
            </p>
            <hr>
            <p class="mb-0">
                <small>
                    <i class="bi bi-info-circle"></i> 
                    Por favor, verifica que la suma de montos importados coincida con el monto configurado en Presupuestos.
                    @if(abs($diferenciaMontos) <= 100)
                        <br>(Diferencia menor a $100, posible redondeo)
                    @endif
                </small>
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @else
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-check-circle-fill"></i> Montos Correctos
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Monto configurado en Presupuestos:</strong><br>
                        <span class="fs-5 text-primary">$ {{ number_format($montoSubvencionMant->monto ?? 0, 0, ',', '.') }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Suma de montos de establecimientos regulares:</strong><br>
                        <span class="fs-5 text-success">$ {{ number_format($sumaMontosRegulares, 0, ',', '.') }}</span>
                    </p>
                </div>
            </div>
            <p class="mb-0 mt-2">
                <small><i class="bi bi-check-circle"></i> La suma de montos de establecimientos regulares coincide con el monto configurado en Presupuestos.</small>
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
    @endif

    <!-- Establecimientos por Comuna -->
    <div class="card shadow">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-building"></i> Establecimientos por Comuna
                </h5>
                <div>
                    <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalImportarContacto">
                        <i class="bi bi-person-lines-fill"></i> IMPORTAR DATOS CONTACTO
                    </button>
                    <button type="button" class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalImportarMontos">
                        <i class="bi bi-upload"></i> IMPORTAR MONTOS
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearEstablecimiento">
                        <i class="bi bi-plus-circle"></i> NUEVO ESTABLECIMIENTO
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtro por Comuna -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filtroComuna" class="form-label fw-bold">Comuna</label>
                    <select class="form-select" id="filtroComuna" onchange="filtrarPorComuna()">
                        <option value="">Todas las comunas</option>
                        @foreach($comunas as $comuna)
                        <option value="{{ $comuna->id }}" {{ request('comuna_id') == $comuna->id ? 'selected' : '' }}>
                            {{ $comuna->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tabla de Establecimientos -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Tipo</th>
                            <th>RBD</th>
                            <th>Nombre</th>
                            <th>Comuna</th>
                            <th>Ruralidad</th>
                            <th>Matrícula</th>
                            <th>Director</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th class="text-end">Subv. Mantenimiento</th>
                            <th class="text-end">Aporte</th>
                            <th class="text-end">Total Subsidio</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($establecimientos as $establecimiento)
                        @php
                            // Aporte calculado: Subvención General repartida entre los 210 establecimientos
                            $aporteMostrar = $establecimiento->aporte_calculado ?? 0;
                            // Subvención Mantenimiento: para VTF es el monto VTF repartido, para Regular es el valor de BD
                            $subvencionMantMostrar = $establecimiento->subvencion_mantenimiento_calculada ?? $establecimiento->subvencion_mantenimiento;
                            $totalSubsidio = $subvencionMantMostrar + $aporteMostrar;
                            
                            // Obtener valores directamente del modelo
                            $matricula = $establecimiento->getAttribute('matricula');
                            $director = $establecimiento->getAttribute('director');
                            $telefono = $establecimiento->getAttribute('telefono');
                            $email = $establecimiento->getAttribute('email');
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-{{ $establecimiento->tipo_calculado == 'VTF' ? 'warning' : 'primary' }}">
                                    {{ $establecimiento->tipo_calculado ?? 'Regular' }}
                                </span>
                            </td>
                            <td>{{ $establecimiento->rbd ?? '-' }}</td>
                            <td>
                                <span class="fw-medium">{{ $establecimiento->nombre }}</span>
                            </td>
                            <td>{{ $establecimiento->comuna->nombre ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $establecimiento->ruralidad_calculada == 'Urbano' ? 'success' : 'warning' }}">
                                    {{ $establecimiento->ruralidad_calculada ?? 'Rural' }}
                                </span>
                            </td>
                            <td>
                                {{ $matricula !== null && $matricula !== '' ? $matricula : '-' }}
                            </td>
                            <td>
                                <small>{{ $director !== null && $director !== '' ? $director : '-' }}</small>
                            </td>
                            <td>
                                <small>{{ $telefono !== null && $telefono !== '' ? $telefono : '-' }}</small>
                            </td>
                            <td>
                                <small>
                                    @if($email !== null && $email !== '')
                                        <a href="mailto:{{ $email }}" class="text-decoration-none">
                                            {{ $email }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </small>
                            </td>
                            <td class="text-end">
                                $ {{ number_format($subvencionMantMostrar, 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                $ {{ number_format($aporteMostrar, 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <strong>$ {{ number_format($totalSubsidio, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editarEstablecimiento({{ $establecimiento->id }}, '{{ addslashes($establecimiento->nombre) }}', {{ $establecimiento->comuna_id ?? 'null' }}, '{{ $establecimiento->comuna->nombre ?? '' }}', '{{ $establecimiento->rbd ?? '' }}', '{{ $establecimiento->tipo ?? $establecimiento->tipo_calculado ?? 'Regular' }}', '{{ $establecimiento->ruralidad ?? $establecimiento->ruralidad_calculada ?? 'Rural' }}', {{ $subvencionMantMostrar }}, {{ $aporteMostrar }}, {{ $establecimiento->matricula ?? 'null' }}, '{{ addslashes($establecimiento->director ?? '') }}', '{{ addslashes($establecimiento->telefono ?? '') }}', '{{ addslashes($establecimiento->email ?? '') }}')"
                                            title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="eliminarEstablecimiento({{ $establecimiento->id }}, '{{ addslashes($establecimiento->nombre) }}')"
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-5">
                                No hay establecimientos registrados para esta comuna.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Establecimiento -->
<div class="modal fade" id="modalCrearEstablecimiento" tabindex="-1" aria-labelledby="modalCrearEstablecimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalCrearEstablecimientoLabel">Agregar Establecimiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEstablecimiento" method="POST" action="{{ route('establecimientos.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodField" value="POST">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="rbd" class="form-label fw-bold">RBD *</label>
                            <input type="text" class="form-control" id="rbd" name="rbd" placeholder="Ej: 8047" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo" class="form-label fw-bold">Tipo de Establecimiento *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="Regular" selected>Regular</option>
                                <option value="VTF">VTF</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="nombre" class="form-label fw-bold">Nombre del Establecimiento *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subvencion_mantenimiento" class="form-label fw-bold">Subvención Mantenimiento <span id="subvMantLabel"></span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control text-end" id="subvencion_mantenimiento" name="subvencion_mantenimiento" value="0" step="0.01">
                            </div>
                            <small class="text-muted" id="subvMantInfo"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="comuna_id" class="form-label fw-bold">Comuna</label>
                            <input type="text" class="form-control d-none" id="comuna_display" readonly>
                            <select class="form-select" id="comuna_select" name="comuna_id">
                                <option value="">Seleccionar comuna</option>
                                @foreach($comunas as $comuna)
                                <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="comuna_id_hidden" name="">
                            <small class="text-muted d-none" id="comuna_hint">No editable</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="ruralidad" class="form-label fw-bold">Ruralidad *</label>
                            <select class="form-select" id="ruralidad" name="ruralidad" required>
                                <option value="Rural" selected>Rural</option>
                                <option value="Urbano">Urbano</option>
                                <option value="Insular/Rural">Insular/Rural</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="aporte_subvencion_general" class="form-label fw-bold">Aporte General por Establecimiento</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control text-end bg-light" id="aporte_subvencion_general" name="aporte_subvencion_general" value="0" step="0.01" readonly>
                            </div>
                            <small class="text-muted">Monto calculado automáticamente (Subvención General / 210 establecimientos)</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="matricula" class="form-label fw-bold">Matrícula</label>
                            <input type="number" class="form-control" id="matricula" name="matricula" placeholder="Ej: 150">
                        </div>
                        <div class="col-md-6">
                            <label for="director" class="form-label fw-bold">Director(a)</label>
                            <input type="text" class="form-control" id="director" name="director" placeholder="Ej: Juan Pérez">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="telefono" class="form-label fw-bold">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ej: +56 9 1234 5678">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Ej: contacto@establecimiento.cl">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEstablecimiento">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Importar Montos -->
<div class="modal fade" id="modalImportarMontos" tabindex="-1" aria-labelledby="modalImportarMontosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title mb-0" id="modalImportarMontosLabel">Importar Montos de Subvención Mantenimiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formImportarMontos" method="POST" action="{{ route('establecimientos.importar-montos') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>Instrucciones:</strong>
                        <ul class="mb-0 mt-2">
                            <li>El archivo debe ser CSV (valores separados por coma) o Excel (.xlsx)</li>
                            <li>La primera fila debe contener los encabezados: <code>RBD</code> o <code>Nombre</code> y <code>Monto</code></li>
                            <li>El monto debe estar sin símbolo de peso ni puntos (ejemplo: 6776655)</li>
                            <li>Solo se actualizarán los establecimientos de tipo <strong>Regular</strong></li>
                            <li>Los establecimientos VTF mantendrán su monto calculado automáticamente</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="archivo_montos" class="form-label fw-bold">Archivo *</label>
                        <input type="file" class="form-control" id="archivo_montos" name="archivo_montos" accept=".csv,.xlsx,.xls" required>
                        <small class="text-muted">Formatos aceptados: CSV, Excel (.xlsx, .xls)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ejemplo de formato CSV:</label>
                        <div class="bg-light p-3 rounded">
                            <code style="font-size: 0.85rem;">
                                RBD,Monto<br>
                                8047,6776655<br>
                                8046,2375212<br>
                                22089,1198882
                            </code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">O por Nombre:</label>
                        <div class="bg-light p-3 rounded">
                            <code style="font-size: 0.85rem;">
                                Nombre,Monto<br>
                                ESCUELA ANEXA,6776655<br>
                                ESCUELA PUDETO,2375212<br>
                                C.E.I.A. SALOMON FUENTES MARTINEZ,1198882
                            </code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload"></i> IMPORTAR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Importar Datos de Contacto -->
<div class="modal fade" id="modalImportarContacto" tabindex="-1" aria-labelledby="modalImportarContactoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title mb-0" id="modalImportarContactoLabel">
                    <i class="bi bi-person-lines-fill"></i> Importar Datos de Contacto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formImportarContacto" method="POST" action="{{ route('establecimientos.importar-contacto') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle"></i> Instrucciones
                        </h6>
                        <p class="mb-2">
                            <strong>Formato del archivo:</strong> CSV o Excel con las siguientes columnas:
                        </p>
                        <ul class="mb-2">
                            <li><strong>Nombre</strong> o <strong>RBD</strong> (requerido para identificar el establecimiento)</li>
                            <li><strong>Matrícula</strong> (opcional)</li>
                            <li><strong>Comuna</strong> (opcional, se mapeará automáticamente)</li>
                            <li><strong>Teléfono</strong> (opcional)</li>
                            <li><strong>Director</strong> (opcional)</li>
                            <li><strong>Correo</strong> o <strong>Email</strong> (opcional)</li>
                        </ul>
                        <p class="mb-0">
                            <small>
                                <i class="bi bi-lightbulb"></i> 
                                <strong>Nota:</strong> El sistema identificará los establecimientos por <strong>Nombre</strong> o <strong>RBD</strong>. 
                                Si el archivo es Excel, guárdalo como CSV (Delimitado por comas) antes de importarlo.
                            </small>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="archivo_contacto" class="form-label">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Seleccionar archivo
                        </label>
                        <input type="file" class="form-control" id="archivo_contacto" name="archivo_contacto" accept=".csv,.txt,.xlsx,.xls" required>
                        <div class="form-text">
                            Formatos aceptados: CSV, TXT, XLSX, XLS (máx. 10MB)
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-upload"></i> IMPORTAR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function filtrarPorComuna() {
    const comunaId = document.getElementById('filtroComuna').value;
    const url = new URL(window.location.href);
    if (comunaId) {
        url.searchParams.set('comuna_id', comunaId);
    } else {
        url.searchParams.delete('comuna_id');
    }
    window.location.href = url.toString();
}

function editarEstablecimiento(id, nombre, comunaId, comunaNombre, rbd, tipo, ruralidad, subvMant, aporte, matricula, director, telefono, email) {
    const esVTF = tipo === 'VTF';
    
    // Actualizar formulario
    document.getElementById('rbd').value = rbd || '';
    document.getElementById('tipo').value = tipo || 'Regular';
    document.getElementById('nombre').value = nombre;
    document.getElementById('ruralidad').value = ruralidad || 'Rural';
    document.getElementById('matricula').value = matricula || '';
    document.getElementById('director').value = director || '';
    document.getElementById('telefono').value = telefono || '';
    document.getElementById('email').value = email || '';
    
    // Para VTF, los montos son readonly y se calculan automáticamente
    const campoMant = document.getElementById('subvencion_mantenimiento');
    const campoAporte = document.getElementById('aporte_subvencion_general');
    
    // Aporte siempre es readonly (se calcula automáticamente)
    campoAporte.value = aporte || 0;
    campoAporte.readOnly = true;
    campoAporte.classList.add('bg-light');
    campoAporte.required = false;
    
    if (esVTF) {
        // VTF: Subvención Mantenimiento también es readonly (calculado automáticamente)
        campoMant.value = subvMant || 0;
        campoMant.readOnly = true;
        campoMant.classList.add('bg-light');
        campoMant.required = false;
        
        // Actualizar labels
        document.getElementById('subvMantLabel').textContent = '';
        document.getElementById('subvMantInfo').innerHTML = '<i class="bi bi-info-circle"></i> Monto calculado automáticamente (Mantención VTF repartida entre establecimientos VTF)';
        document.getElementById('subvMantInfo').className = 'form-text text-info mt-1';
    } else {
        // Regular: Solo Subvención Mantenimiento es editable
        campoMant.value = subvMant || 0;
        campoMant.readOnly = false;
        campoMant.classList.remove('bg-light');
        campoMant.required = true;
        
        // Actualizar labels
        document.getElementById('subvMantLabel').textContent = '*';
        document.getElementById('subvMantInfo').innerHTML = '<i class="bi bi-info-circle"></i> Monto asignado según matrícula (puede importarse desde archivo CSV)';
        document.getElementById('subvMantInfo').className = 'form-text text-muted mt-1';
    }
    
    // Mostrar comuna como solo lectura en modo edición
    document.getElementById('comuna_display').value = comunaNombre || '';
    document.getElementById('comuna_display').classList.remove('d-none');
    document.getElementById('comuna_select').classList.add('d-none');
    document.getElementById('comuna_hint').classList.remove('d-none');
    // En modo edición, usar el hidden field con el nombre correcto
    document.getElementById('comuna_id_hidden').name = 'comuna_id';
    document.getElementById('comuna_id_hidden').value = comunaId || '';
    // Deshabilitar el select para que no envíe su valor
    document.getElementById('comuna_select').name = '';
    
    // Actualizar modal
    document.getElementById('modalCrearEstablecimientoLabel').textContent = 'Editar Establecimiento';
    const form = document.getElementById('formEstablecimiento');
    form.action = '/configuracion/establecimientos/' + id;
    
    // CRÍTICO: Eliminar TODOS los campos _method duplicados y crear uno nuevo limpio
    const allMethodFields = form.querySelectorAll('input[name="_method"]');
    allMethodFields.forEach(field => field.remove());
    
    // Crear UN SOLO campo _method con valor PUT
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.id = 'methodField';
    methodField.value = 'PUT';
    methodField.setAttribute('value', 'PUT');
    
    // Insertar justo después del CSRF token
    const csrfField = form.querySelector('input[name="_token"]');
    if (csrfField && csrfField.parentNode) {
        csrfField.parentNode.insertBefore(methodField, csrfField.nextSibling);
    } else {
        form.insertBefore(methodField, form.firstChild);
    }
    
    console.log('Campo _method creado con valor:', methodField.value);
    console.log('Form action:', form.action);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCrearEstablecimiento'));
    modal.show();
}

function eliminarEstablecimiento(id, nombre) {
    if (confirm('¿Estás seguro de eliminar el establecimiento "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        fetch('/configuracion/establecimientos/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json().catch(() => {
                    // Si no es JSON, asumir éxito
                    return { success: true };
                });
            }
            // Si la respuesta no es OK, intentar leer el JSON del error
            return response.json().then(data => {
                throw new Error(data.message || 'Error al eliminar el establecimiento');
            }).catch(() => {
                throw new Error('Error al eliminar el establecimiento');
            });
        })
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo eliminar el establecimiento'));
            }
        })
        .catch(error => {
            console.error('Error al eliminar:', error);
            alert('Error al eliminar: ' + (error.message || 'Error desconocido'));
        });
    }
}

// Resetear modal al cerrar
document.getElementById('modalCrearEstablecimiento').addEventListener('hidden.bs.modal', function () {
    const form = document.getElementById('formEstablecimiento');
    form.reset();
    form.action = '{{ route("establecimientos.store") }}';
    const methodField = document.getElementById('methodField');
    if (methodField) {
        methodField.value = 'POST';
        methodField.setAttribute('value', 'POST');
    }
    
    // Restablecer campos de contacto
    if (document.getElementById('matricula')) document.getElementById('matricula').value = '';
    if (document.getElementById('director')) document.getElementById('director').value = '';
    if (document.getElementById('telefono')) document.getElementById('telefono').value = '';
    if (document.getElementById('email')) document.getElementById('email').value = '';
    
    // Limpiar todos los event listeners del formulario (crear nuevos handlers)
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    // Re-asignar el ID al nuevo formulario
    newForm.id = 'formEstablecimiento';
    document.getElementById('modalCrearEstablecimientoLabel').textContent = 'Agregar Establecimiento';
    document.getElementById('tipo').value = 'Regular';
    document.getElementById('ruralidad').value = 'Rural';
    
    // Restaurar campos de montos (modo crear - Regular por defecto)
    const campoMant = document.getElementById('subvencion_mantenimiento');
    const campoAporte = document.getElementById('aporte_subvencion_general');
    
    // Aporte siempre readonly
    campoAporte.readOnly = true;
    campoAporte.classList.add('bg-light');
    campoAporte.required = false;
    
    // Subvención Mantenimiento editable (Regular por defecto)
    campoMant.readOnly = false;
    campoMant.classList.remove('bg-light');
    campoMant.required = true;
    
    // Restaurar labels
    document.getElementById('subvMantLabel').textContent = '*';
    document.getElementById('subvMantInfo').innerHTML = '<i class="bi bi-info-circle"></i> Monto asignado según matrícula (puede importarse desde archivo CSV)';
    document.getElementById('subvMantInfo').className = 'form-text text-muted mt-1';
    
    // Restaurar selector de comuna para modo crear
    document.getElementById('comuna_display').classList.add('d-none');
    document.getElementById('comuna_select').classList.remove('d-none');
    document.getElementById('comuna_hint').classList.add('d-none');
    document.getElementById('comuna_display').value = '';
    document.getElementById('comuna_select').value = '';
    // Restaurar el name del select y limpiar el hidden en modo crear
    document.getElementById('comuna_select').name = 'comuna_id';
    document.getElementById('comuna_id_hidden').name = '';
    document.getElementById('comuna_id_hidden').value = '';
});
</script>
@endpush

