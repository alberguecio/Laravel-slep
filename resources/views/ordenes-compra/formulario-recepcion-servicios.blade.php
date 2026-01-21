@extends('layouts.app')

@section('title', 'Recepción Conforme Servicios')

@push('styles')
<style>
    /* Estilos para pantalla */
    .formulario-screen {
        display: block;
    }
    
    .formulario-print {
        display: none;
    }
    
    /* Estilos para impresión */
    @media print {
        @page {
            margin: 0.5cm;
            size: A4;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        
        /* Ocultar navbar y otros elementos del layout */
        .navbar,
        .navbar *,
        nav,
        nav * {
            display: none !important;
            visibility: hidden !important;
        }
        
        body * {
            visibility: hidden;
        }
        
        .formulario-screen,
        .formulario-screen * {
            display: none !important;
        }
        
        .formulario-print,
        .formulario-print * {
            visibility: visible;
        }
        
        .formulario-print {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white;
            padding: 0;
            margin: 0;
        }
        
        .header-print {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        
        .logo-section {
            flex: 1;
            display: flex;
            align-items: flex-start;
        }
        
        .logo-image {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
        }
        
        .header-info {
            text-align: right;
            font-size: 11px;
        }
        
        .header-info div {
            margin-bottom: 3px;
        }
        
        .title-print {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .info-section {
            margin: 20px 0;
            font-size: 11px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex: 1;
        }
        
        .table-print {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }
        
        .table-print th,
        .table-print td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        
        .table-print th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .table-print td.text-right {
            text-align: right;
        }
        
        .table-print tfoot td {
            font-weight: bold;
            text-align: right;
        }
        
        .declaracion {
            margin: 20px 0;
            font-size: 11px;
            font-style: italic;
        }
        
        .firmas-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        
        .firma-block {
            width: 45%;
            text-align: center;
        }
        
        .firma-line {
            border-top: 1px solid #000;
            margin: 50px 0 5px 0;
            width: 100%;
        }
        
        .firma-nombre {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        .firma-cargo {
            font-size: 10px;
            color: #666;
        }
        
        .no-print {
            display: none !important;
        }
        
        /* Ocultar la nota informativa en impresión */
        .alert {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<!-- Vista para pantalla -->
<div class="container-fluid py-4 formulario-screen">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-check"></i> Recepción Conforme Servicios
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Información de la OC -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Información de la Orden de Compra</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2"><strong>N° OC:</strong> {{ $oc->numero }}</div>
                                <div class="mb-2"><strong>Fecha OC:</strong> {{ $oc->fecha ? $oc->fecha->format('d-m-Y') : '-' }}</div>
                                <div class="mb-2"><strong>Contrato:</strong> {{ $oc->contrato ? $oc->contrato->nombre_contrato : '-' }}</div>
                                <div class="mb-2"><strong>Proyecto:</strong> {{ $oc->contrato && $oc->contrato->proyecto ? $oc->contrato->proyecto->nombre : '-' }}</div>
                                <div class="mb-2"><strong>Item:</strong> {{ $oc->contrato && $oc->contrato->proyecto && $oc->contrato->proyecto->item ? $oc->contrato->proyecto->item->nombre : '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2"><strong>Monto Total:</strong> $ {{ number_format($oc->monto_total ?? 0, 0, ',', '.') }}</div>
                                <div class="mb-2"><strong>Monto Oficial Mercado Público:</strong> $ {{ number_format($oc->monto_mercado_publico ?? $oc->monto_total ?? 0, 0, ',', '.') }}</div>
                                <div class="mb-2"><strong>Estado:</strong> {{ $oc->estado ?? '-' }}</div>
                                <div class="mb-2"><strong>Proveedor:</strong> {{ $oc->contrato ? ($oc->contrato->proveedor ?? '-') : '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Listado de OTs -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">OTs asociadas a la OC seleccionada:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">ITEM</th>
                                        <th style="width: 20%;">ESTABLECIMIENTO</th>
                                        <th style="width: 10%;">RBD</th>
                                        <th style="width: 15%;">COMUNA</th>
                                        <th class="text-end" style="width: 20%;">VALOR (IVA INCLUIDO)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($oc->ordenesTrabajo as $index => $ot)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $ot->establecimiento ? $ot->establecimiento->nombre : '-' }}</td>
                                        <td>{{ $ot->establecimiento ? ($ot->establecimiento->rbd ?? '-') : '-' }}</td>
                                        <td>{{ $ot->comuna ? $ot->comuna->nombre : '-' }}</td>
                                        <td class="text-end">$ {{ number_format($ot->monto ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay OTs asociadas</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end">TOTAL:</td>
                                        <td class="text-end">$ {{ number_format($oc->monto_mercado_publico ?? $oc->monto_total ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Campos adicionales del formulario -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Datos del Formulario</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">N° RCS</label>
                                <input type="text" class="form-control" id="rcs_numero" value="{{ $oc->rcs_numero ? $oc->rcs_numero : '' }}" readonly>
                                <small class="text-muted">Número correlativo automático</small>
                                @if($oc->rcs_numero)
                                    <div class="text-success small mt-1">
                                        <i class="bi bi-check-circle"></i> Número asignado: {{ $oc->rcs_numero }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" class="form-control" id="rcs_fecha" value="{{ $oc->rcs_fecha ? $oc->rcs_fecha->format('Y-m-d') : date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" id="rcs_email" value="{{ $userEmail }}" readonly>
                                <small class="text-muted">Email del usuario que crea el formulario</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tipo de Jefatura</label>
                                <select class="form-select" id="tipo_jefatura" onchange="toggleJefatura()">
                                    <option value="Titular" {{ !$oc->rcs_tipo_jefatura || $oc->rcs_tipo_jefatura == 'Titular' ? 'selected' : '' }}>Titular</option>
                                    <option value="Suplencia" {{ $oc->rcs_tipo_jefatura == 'Suplencia' ? 'selected' : '' }}>Suplencia</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Jefatura que Firma</label>
                                <input type="text" class="form-control" id="jefatura_firma" value="{{ $oc->rcs_jefatura_firma ?? 'JORGE DÍAZ TORREJÓN' }}" {{ !$oc->rcs_tipo_jefatura || $oc->rcs_tipo_jefatura == 'Titular' ? 'readonly' : '' }}>
                                <small class="text-muted" id="jefatura_hint">Campo no editable para jefatura titular</small>
                            </div>
                        </div>
                    </div>

                    <!-- Nota sobre impresión -->
                    <div class="alert alert-info mb-3">
                        <small>
                            <strong>Nota:</strong> Para eliminar los encabezados y pies de página (fecha, URL, título, número de página) en la impresión:
                            <ul class="mb-0 mt-2">
                                <li><strong>Chrome/Edge:</strong> En el diálogo de impresión, desactivar "Encabezados y pies de página"</li>
                                <li><strong>Firefox:</strong> En "Más configuraciones" → desactivar "Encabezados y pies de página"</li>
                            </ul>
                        </small>
                    </div>

                    <!-- Botones -->
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="generarEImprimir()">
                            <i class="bi bi-printer"></i> Generar e Imprimir
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.close()">
                            <i class="bi bi-x-circle"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vista para impresión -->
<div class="formulario-print">
    <!-- Header -->
    <div class="header-print">
        <div class="logo-section">
            @if(file_exists(public_path('logo.png')))
                <img src="{{ asset('logo.png') }}" alt="Logo SLEP Chiloé" class="logo-image">
            @elseif(file_exists(public_path('logo.jpg')))
                <img src="{{ asset('logo.jpg') }}" alt="Logo SLEP Chiloé" class="logo-image">
            @elseif(file_exists(public_path('logo.svg')))
                <img src="{{ asset('logo.svg') }}" alt="Logo SLEP Chiloé" class="logo-image">
            @endif
        </div>
        <div class="header-info">
            <div><strong>N° Formulario:</strong> N/A</div>
            <div><strong>N° RCS:</strong> <span id="print_rcs_numero">{{ $oc->rcs_numero ?? 'RCS-0001-2025' }}</span></div>
            <div><strong>Fecha:</strong> <span id="print_fecha">{{ $oc->rcs_fecha ? $oc->rcs_fecha->format('d-m-Y') : date('d-m-Y') }}</span></div>
            <div><strong>Subdirección:</strong> Infraestructura</div>
        </div>
    </div>

    <!-- Título -->
    <div class="title-print">
        RECEPCIÓN CONFORME SERVICIOS
    </div>

    <!-- Información principal -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Requirente:</div>
            <div class="info-value">SUBDIRECCIÓN INFRAESTRUCTURA</div>
        </div>
        <div class="info-row">
            <div class="info-label">Jefatura que Firma:</div>
            <div class="info-value"><span id="print_jefatura">{{ $oc->rcs_jefatura_firma ?? 'JORGE DÍAZ TORREJÓN' }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">RUT Jefatura:</div>
            <div class="info-value">{{ $rutJefatura }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">N° Orden de Compra:</div>
            <div class="info-value">{{ $oc->numero }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Nombre Contratista:</div>
            <div class="info-value">{{ $oc->contrato ? ($oc->contrato->proveedor ?? '-') : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Contrato:</div>
            <div class="info-value">{{ $oc->contrato ? $oc->contrato->nombre_contrato : '-' }}</div>
        </div>
    </div>

    <!-- Tabla de OTs -->
    <table class="table-print">
        <thead>
            <tr>
                <th style="width: 5%;">ITEM</th>
                <th style="width: 35%;">ESTABLECIMIENTO</th>
                <th style="width: 10%;">RBD</th>
                <th style="width: 15%;">COMUNA</th>
                <th style="width: 35%;" class="text-right">VALOR (IVA INCLUIDO)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($oc->ordenesTrabajo as $index => $ot)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $ot->establecimiento ? $ot->establecimiento->nombre : '-' }}</td>
                <td style="text-align: center;">{{ $ot->establecimiento ? ($ot->establecimiento->rbd ?? '-') : '-' }}</td>
                <td>{{ $ot->comuna ? $ot->comuna->nombre : '-' }}</td>
                <td class="text-right">$ {{ number_format($ot->monto ?? 0, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">No hay OTs asociadas</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>$ {{ number_format($oc->monto_mercado_publico ?? $oc->monto_total ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Declaración -->
    <div class="declaracion">
        Declaro recepcionar en conformidad los bienes y servicios solicitados.
    </div>

    <!-- Firmas -->
    <div class="firmas-section">
        <div class="firma-block">
            <div class="firma-line"></div>
            <div class="firma-nombre">{{ $userNombre }}</div>
            <div class="firma-cargo">PROFESIONAL DE EJECUCIÓN DE PROYECTOS DE INFRAESTRUCTURA</div>
        </div>
        <div class="firma-block">
            <div class="firma-line"></div>
            <div class="firma-nombre"><span id="print_jefatura_firma">{{ $oc->rcs_jefatura_firma ?? 'JORGE DÍAZ TORREJÓN' }}</span></div>
            <div class="firma-cargo">SUBDIRECTOR INFRAESTRUCTURA</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const ocId = {{ $oc->id }};
const cargoBase = 'SUBDIRECCIÓN INFRAESTRUCTURA';

function toggleJefatura() {
    const tipoJefatura = document.getElementById('tipo_jefatura').value;
    const jefaturaInput = document.getElementById('jefatura_firma');
    const jefaturaHint = document.getElementById('jefatura_hint');
    
    if (tipoJefatura === 'Titular') {
        jefaturaInput.readOnly = true;
        jefaturaInput.value = 'JORGE DÍAZ TORREJÓN';
        jefaturaHint.textContent = 'Campo no editable para jefatura titular';
        updatePrintFields();
    } else {
        jefaturaInput.readOnly = false;
        jefaturaHint.textContent = 'Campo editable para jefatura suplente';
        updatePrintFields();
    }
}

function updatePrintFields() {
    const rcsNumero = document.getElementById('rcs_numero').value;
    const rcsFecha = document.getElementById('rcs_fecha').value;
    const jefaturaFirma = document.getElementById('jefatura_firma').value;
    
    if (rcsNumero) {
        document.getElementById('print_rcs_numero').textContent = rcsNumero;
    }
    
    if (rcsFecha) {
        const fecha = new Date(rcsFecha);
        const fechaFormateada = fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        document.getElementById('print_fecha').textContent = fechaFormateada;
    }
    
    if (jefaturaFirma) {
        document.getElementById('print_jefatura').textContent = jefaturaFirma;
        document.getElementById('print_jefatura_firma').textContent = jefaturaFirma;
    }
}

// Actualizar información cuando cambia el nombre de jefatura
document.getElementById('jefatura_firma').addEventListener('input', function() {
    updatePrintFields();
});

// Actualizar fecha cuando cambia
document.getElementById('rcs_fecha').addEventListener('change', function() {
    updatePrintFields();
});

async function generarEImprimir() {
    const rcsNumero = document.getElementById('rcs_numero').value;
    const rcsFecha = document.getElementById('rcs_fecha').value;
    const tipoJefatura = document.getElementById('tipo_jefatura').value;
    const jefaturaFirma = document.getElementById('jefatura_firma').value;
    
    // Si no hay número RCS, generar uno
    if (!rcsNumero) {
        try {
            const response = await fetch(`/ordenes-compra/${ocId}/generar-rcs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: rcsFecha,
                    tipo_jefatura: tipoJefatura,
                    jefatura_firma: jefaturaFirma
                })
            });
            
            const data = await response.json();
            console.log('Respuesta del servidor:', data);
            if (data.success && data.rcs_numero) {
                document.getElementById('rcs_numero').value = data.rcs_numero;
                updatePrintFields();
                console.log('Número RCS actualizado:', data.rcs_numero);
            } else {
                const errorMsg = data.message || 'Error desconocido';
                console.error('Error en respuesta:', errorMsg, data);
                alert('Error al generar el número RCS: ' + errorMsg);
                return;
            }
        } catch (error) {
            console.error('Error en fetch:', error);
            alert('Error al generar el número RCS: ' + error.message);
            return;
        }
    } else {
        // Actualizar fecha y jefatura si ya existe
        try {
            const response = await fetch(`/ordenes-compra/${ocId}/actualizar-rcs`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: rcsFecha,
                    tipo_jefatura: tipoJefatura,
                    jefatura_firma: jefaturaFirma
                })
            });
            
            const data = await response.json();
            if (!data.success) {
                console.error('Error al actualizar:', data.message);
            } else {
                updatePrintFields();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    // Imprimir
    setTimeout(() => {
        window.print();
    }, 100);
}

// Función para generar RCS automáticamente
async function generarRCSAutomatico() {
    const rcsNumeroInput = document.getElementById('rcs_numero');
    const rcsFechaInput = document.getElementById('rcs_fecha');
    const tipoJefaturaInput = document.getElementById('tipo_jefatura');
    const jefaturaFirmaInput = document.getElementById('jefatura_firma');
    
    // Si no hay número RCS, generarlo automáticamente
    if (!rcsNumeroInput.value || rcsNumeroInput.value.trim() === '') {
        // Asegurar que los campos tengan valores por defecto
        if (!rcsFechaInput.value) {
            const hoy = new Date();
            rcsFechaInput.value = hoy.toISOString().split('T')[0];
        }
        if (!tipoJefaturaInput.value) {
            tipoJefaturaInput.value = 'Titular';
            toggleJefatura(); // Actualizar campos relacionados
        }
        if (!jefaturaFirmaInput.value) {
            jefaturaFirmaInput.value = 'JORGE DÍAZ TORREJÓN';
        }
        
        // Generar el número
        try {
            const response = await fetch(`/ordenes-compra/${ocId}/generar-rcs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: rcsFechaInput.value,
                    tipo_jefatura: tipoJefaturaInput.value,
                    jefatura_firma: jefaturaFirmaInput.value
                })
            });
            
            const data = await response.json();
            console.log('Respuesta del servidor (auto-generación):', data);
            if (data.success && data.rcs_numero) {
                rcsNumeroInput.value = data.rcs_numero;
                updatePrintFields();
                console.log('Número RCS generado automáticamente:', data.rcs_numero);
            } else {
                console.error('Error al generar RCS automáticamente:', data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error en generación automática de RCS:', error);
        }
    } else {
        updatePrintFields();
    }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function() {
    toggleJefatura();
    updatePrintFields();
    
    // Generar RCS automáticamente si no existe
    setTimeout(() => {
        generarRCSAutomatico();
    }, 500);
});
</script>
@endpush
@endsection
