@extends('layouts.app')

@section('title', 'Recepción Conforme Factura')

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
        
        /* Títulos de sección ocupan todo el ancho */
        .section-title {
            width: 100%;
            margin-bottom: 10px;
            font-weight: bold;
            display: block;
        }
        
        .section-title-row {
            margin-bottom: 10px;
        }
        
        /* Línea separadora antes de secciones 2 y 3 */
        .section-divider {
            border-top: 1px solid #ccc;
            margin: 15px 0;
            width: 100%;
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
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i> Recepción Conforme Factura
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
                                <div class="mb-2"><strong>Estado:</strong> {{ $oc->estado ?? '-' }}</div>
                                <div class="mb-2"><strong>Proveedor:</strong> {{ $oc->contrato ? ($oc->contrato->proveedor ?? '-') : '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Campos adicionales del formulario -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Datos del Formulario</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">N° RCF</label>
                                <input type="text" class="form-control" id="rcf_numero" value="{{ $oc->rcf_numero ? $oc->rcf_numero : '' }}" readonly>
                                <small class="text-muted">Número correlativo automático</small>
                                @if($oc->rcf_numero)
                                    <div class="text-success small mt-1">
                                        <i class="bi bi-check-circle"></i> Número asignado: {{ $oc->rcf_numero }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" class="form-control" id="rcf_fecha" value="{{ $oc->rcf_fecha ? $oc->rcf_fecha->format('Y-m-d') : date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" id="rcf_email" value="{{ $userEmail }}" readonly>
                                <small class="text-muted">Email del usuario que crea el formulario</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tipo de Jefatura</label>
                                <select class="form-select" id="tipo_jefatura" onchange="toggleJefatura()">
                                    <option value="Titular" {{ !$oc->rcf_tipo_jefatura || $oc->rcf_tipo_jefatura == 'Titular' ? 'selected' : '' }}>Titular</option>
                                    <option value="Suplencia" {{ $oc->rcf_tipo_jefatura == 'Suplencia' ? 'selected' : '' }}>Suplencia</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Jefatura que Firma</label>
                                <input type="text" class="form-control" id="jefatura_firma" value="{{ $oc->rcf_jefatura_firma ?? 'JORGE DÍAZ TORREJÓN' }}" {{ !$oc->rcf_tipo_jefatura || $oc->rcf_tipo_jefatura == 'Titular' ? 'readonly' : '' }}>
                                <small class="text-muted" id="jefatura_hint">Campo no editable para jefatura titular</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">N° RCS</label>
                                <input type="text" class="form-control" id="rcs_numero" value="{{ $oc->rcs_numero ?? '' }}" readonly>
                                <small class="text-muted">Asociado a la OC</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha RCS</label>
                                <input type="date" class="form-control" id="rcs_fecha" value="{{ $oc->rcs_fecha ? $oc->rcs_fecha->format('Y-m-d') : '' }}" readonly>
                                <small class="text-muted">Asociado a la OC</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">N° Factura</label>
                                <input type="text" class="form-control" id="factura" value="{{ $oc->factura ?? '' }}" readonly>
                                <small class="text-muted">Asociado a la OC</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Monto Factura</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    @php
                                        // Prioridad: monto_mercado_publico > monto_total > monto_factura
                                        // Verificar explícitamente si monto_mercado_publico existe y no es null
                                        if (isset($oc->monto_mercado_publico) && $oc->monto_mercado_publico !== null && $oc->monto_mercado_publico > 0) {
                                            $montoParaMostrar = $oc->monto_mercado_publico;
                                        } elseif (isset($oc->monto_total) && $oc->monto_total !== null && $oc->monto_total > 0) {
                                            $montoParaMostrar = $oc->monto_total;
                                        } elseif (isset($oc->monto_factura) && $oc->monto_factura !== null && $oc->monto_factura > 0) {
                                            $montoParaMostrar = $oc->monto_factura;
                                        } else {
                                            $montoParaMostrar = 0;
                                        }
                                    @endphp
                                    <input type="text" class="form-control text-end" id="monto_factura" value="{{ $montoParaMostrar ? number_format($montoParaMostrar, 0, ',', '.') : '' }}" readonly>
                                </div>
                                <small class="text-muted">Monto oficial de Mercado Público (prioridad sobre monto total y monto factura)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha Factura</label>
                                <input type="date" class="form-control" id="fecha_factura" value="{{ $oc->fecha_factura ? $oc->fecha_factura->format('Y-m-d') : '' }}" readonly>
                                <small class="text-muted">Asociado a la OC</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha Recepción Conforme Factura</label>
                                <input type="date" class="form-control" id="fecha_recepcion_factura" value="{{ $oc->fecha_recepcion_factura ? $oc->fecha_recepcion_factura->format('Y-m-d') : '' }}" readonly>
                                <small class="text-muted">Asociado a la OC</small>
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
                        <button type="button" class="btn btn-primary" onclick="generarEImprimir()">
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
            <div><strong>N° RCF:</strong> <span id="print_rcf_numero">{{ $oc->rcf_numero ?? 'RCF-0001-2025' }}</span></div>
            <div><strong>Fecha:</strong> <span id="print_fecha">{{ $oc->rcf_fecha ? $oc->rcf_fecha->format('d-m-Y') : date('d-m-Y') }}</span></div>
            <div><strong>Subdirección:</strong> Infraestructura</div>
        </div>
    </div>

    <!-- Título -->
    <div class="title-print">
        RECEPCIÓN CONFORME FACTURA
    </div>

    <!-- Sección 1: Antecedentes recepción conforme -->
    <div class="info-section">
        <div class="section-title">1. Antecedentes recepción conforme</div>
        <div class="info-row">
            <div class="info-label">N° RCS:</div>
            <div class="info-value">{{ $oc->rcs_numero ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha RCS:</div>
            <div class="info-value">{{ $oc->rcs_fecha ? $oc->rcs_fecha->format('d-m-Y') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Recibe conforme:</div>
            <div class="info-value">SUBDIRECCIÓN INFRAESTRUCTURA</div>
        </div>
        <div class="info-row">
            <div class="info-label">Jefatura unidad:</div>
            <div class="info-value"><span id="print_jefatura">{{ $oc->rcf_jefatura_firma ?? 'JORGE DÍAZ TORREJÓN' }}</span></div>
        </div>
    </div>

    <!-- Sección 2: Descripción de bienes y/o servicios -->
    <div class="info-section">
        <div class="section-divider"></div>
        <div class="section-title">2. Descripción de bienes y/o servicios a los que está referida la factura</div>
        <div class="info-row">
            <div class="info-label">Contrato:</div>
            <div class="info-value">{{ $oc->contrato ? $oc->contrato->nombre_contrato : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Nombre Contratista:</div>
            <div class="info-value">{{ $oc->contrato ? ($oc->contrato->proveedor ?? '-') : '-' }}</div>
        </div>
    </div>

    <!-- Sección 3: Datos de facturación -->
    <div class="info-section">
        <div class="section-divider"></div>
        <div class="section-title">3. Datos de facturación</div>
        <div class="info-row">
            <div class="info-label">N° Factura:</div>
            <div class="info-value">{{ $oc->factura ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Monto Factura:</div>
            @php
                // Prioridad: monto_mercado_publico > monto_total > monto_factura
                // Verificar explícitamente si monto_mercado_publico existe y no es null
                if (isset($oc->monto_mercado_publico) && $oc->monto_mercado_publico !== null && $oc->monto_mercado_publico > 0) {
                    $montoParaMostrar = $oc->monto_mercado_publico;
                } elseif (isset($oc->monto_total) && $oc->monto_total !== null && $oc->monto_total > 0) {
                    $montoParaMostrar = $oc->monto_total;
                } elseif (isset($oc->monto_factura) && $oc->monto_factura !== null && $oc->monto_factura > 0) {
                    $montoParaMostrar = $oc->monto_factura;
                } else {
                    $montoParaMostrar = 0;
                }
            @endphp
            <div class="info-value">$ {{ $montoParaMostrar ? number_format($montoParaMostrar, 0, ',', '.') : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha Factura:</div>
            <div class="info-value">{{ $oc->fecha_factura ? $oc->fecha_factura->format('d-m-Y') : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha Recepción Conforme Factura:</div>
            <div class="info-value">{{ $oc->fecha_recepcion_factura ? $oc->fecha_recepcion_factura->format('d-m-Y') : '-' }}</div>
        </div>
    </div>

    <!-- Declaración -->
    <div class="declaracion">
        Declaro recepcionar en conformidad los bienes y servicios solicitados. Asimismo, con esta fecha, se da recepción conforme en su totalidad a lo solicitado, según se indica en el punto 1, 2 y 3 de este documento, con el fin de que se proceda al pago.
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
            <div class="firma-nombre"><span id="print_jefatura_firma">{{ $oc->rcf_jefatura_firma ?? 'JORGE DÍAZ TORREJÓN' }}</span></div>
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
    const rcfNumero = document.getElementById('rcf_numero').value;
    const rcfFecha = document.getElementById('rcf_fecha').value;
    const jefaturaFirma = document.getElementById('jefatura_firma').value;
    
    if (rcfNumero) {
        document.getElementById('print_rcf_numero').textContent = rcfNumero;
    }
    
    if (rcfFecha) {
        const fecha = new Date(rcfFecha);
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
document.getElementById('rcf_fecha').addEventListener('change', function() {
    updatePrintFields();
});

async function generarEImprimir() {
    const rcfNumero = document.getElementById('rcf_numero').value;
    const rcfFecha = document.getElementById('rcf_fecha').value;
    const tipoJefatura = document.getElementById('tipo_jefatura').value;
    const jefaturaFirma = document.getElementById('jefatura_firma').value;
    
    // Si no hay número RCF, generar uno
    if (!rcfNumero) {
        try {
            const response = await fetch(`/ordenes-compra/${ocId}/generar-rcf`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: rcfFecha,
                    tipo_jefatura: tipoJefatura,
                    jefatura_firma: jefaturaFirma
                })
            });
            
            const data = await response.json();
            console.log('Respuesta del servidor:', data);
            if (data.success && data.rcf_numero) {
                document.getElementById('rcf_numero').value = data.rcf_numero;
                updatePrintFields();
                console.log('Número RCF actualizado:', data.rcf_numero);
            } else {
                const errorMsg = data.message || 'Error desconocido';
                console.error('Error en respuesta:', errorMsg, data);
                alert('Error al generar el número RCF: ' + errorMsg);
                return;
            }
        } catch (error) {
            console.error('Error en fetch:', error);
            alert('Error al generar el número RCF: ' + error.message);
            return;
        }
    } else {
        // Actualizar fecha y jefatura si ya existe
        try {
            const response = await fetch(`/ordenes-compra/${ocId}/actualizar-rcf`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: rcfFecha,
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

// Función para generar RCF automáticamente
async function generarRCFAutomatico() {
    const rcfNumeroInput = document.getElementById('rcf_numero');
    const rcfFechaInput = document.getElementById('rcf_fecha');
    const tipoJefaturaInput = document.getElementById('tipo_jefatura');
    const jefaturaFirmaInput = document.getElementById('jefatura_firma');
    
    // Si no hay número RCF, generarlo automáticamente
    if (!rcfNumeroInput.value || rcfNumeroInput.value.trim() === '') {
        // Asegurar que los campos tengan valores por defecto
        if (!rcfFechaInput.value) {
            const hoy = new Date();
            rcfFechaInput.value = hoy.toISOString().split('T')[0];
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
            const response = await fetch(`/ordenes-compra/${ocId}/generar-rcf`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: rcfFechaInput.value,
                    tipo_jefatura: tipoJefaturaInput.value,
                    jefatura_firma: jefaturaFirmaInput.value
                })
            });
            
            const data = await response.json();
            console.log('Respuesta del servidor (auto-generación):', data);
            if (data.success && data.rcf_numero) {
                rcfNumeroInput.value = data.rcf_numero;
                updatePrintFields();
                console.log('Número RCF generado automáticamente:', data.rcf_numero);
            } else {
                console.error('Error al generar RCF automáticamente:', data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error en generación automática de RCF:', error);
        }
    } else {
        updatePrintFields();
    }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function() {
    toggleJefatura();
    updatePrintFields();
    
    // Generar RCF automáticamente si no existe
    setTimeout(() => {
        generarRCFAutomatico();
    }, 500);
});
</script>
@endpush
@endsection
