@extends('layouts.app')

@section('title', 'Acta Recepción Conforme')

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
        
        .actividades-section {
            margin: 20px 0;
            font-size: 11px;
        }
        
        .actividades-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .actividades-list {
            margin-left: 20px;
        }
        
        .actividades-list li {
            margin-bottom: 5px;
        }
        
        .recepcion-section {
            margin: 20px 0;
            font-size: 11px;
        }
        
        .recepcion-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .checkbox-group {
            margin: 10px 0;
        }
        
        .checkbox-group label {
            margin-right: 20px;
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
                        <i class="bi bi-file-earmark-check"></i> Acta Recepción Conforme
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Información de la OT -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Información de la Orden de Trabajo</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2"><strong>N° OT:</strong> {{ $orden->numero_ot }}</div>
                                <div class="mb-2"><strong>Fecha OT:</strong> {{ $orden->fecha_ot ? $orden->fecha_ot->format('d-m-Y') : '-' }}</div>
                                <div class="mb-2"><strong>Contrato:</strong> {{ $orden->contrato ? $orden->contrato->nombre_contrato : '-' }}</div>
                                <div class="mb-2"><strong>Proyecto:</strong> {{ $orden->contrato && $orden->contrato->proyecto ? $orden->contrato->proyecto->nombre : '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2"><strong>Establecimiento:</strong> {{ $orden->establecimiento ? $orden->establecimiento->nombre : '-' }}</div>
                                <div class="mb-2"><strong>Comuna:</strong> {{ $orden->comuna ? $orden->comuna->nombre : ($orden->establecimiento && $orden->establecimiento->comuna ? $orden->establecimiento->comuna->nombre : '-') }}</div>
                                <div class="mb-2"><strong>RBD:</strong> {{ $orden->establecimiento ? ($orden->establecimiento->rbd ?? '-') : '-' }}</div>
                                <div class="mb-2"><strong>Monto Total:</strong> $ {{ number_format($orden->monto ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Presupuesto -->
                    @if($presupuestoItems && count($presupuestoItems) > 0)
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Presupuesto ({{ count($presupuestoItems) }} ítems)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 8%;">Ítem</th>
                                        <th style="width: 50%;">Partida</th>
                                        <th class="text-center" style="width: 10%;">Unidad</th>
                                        <th class="text-end" style="width: 12%;">Cantidad</th>
                                        <th class="text-end" style="width: 15%;">Precio Unit.</th>
                                        <th class="text-end" style="width: 15%;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($presupuestoItems as $item)
                                    <tr>
                                        <td>{{ $item['item'] }}</td>
                                        <td>{{ $item['numero_partida'] ? $item['numero_partida'] . ' - ' : '' }}{{ $item['partida'] }}</td>
                                        <td class="text-center">{{ $item['unidad'] }}</td>
                                        <td class="text-end">{{ number_format($item['cantidad'], 2, ',', '.') }}</td>
                                        <td class="text-end">$ {{ number_format($item['precio'], 0, ',', '.') }}</td>
                                        <td class="text-end">$ {{ number_format($item['total'], 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end">Valor Neto:</td>
                                        <td class="text-end">$ {{ number_format($totalNeto, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end">IVA (19%):</td>
                                        <td class="text-end">$ {{ number_format($iva, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end">Total IVA Incluido:</td>
                                        <td class="text-end">$ {{ number_format($totalConIva, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

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
                        <button type="button" class="btn btn-success" onclick="window.print()">
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
        </div>
    </div>

    <!-- Título -->
    <div class="title-print">
        RECEPCIÓN CONFORME
    </div>

    <!-- Información principal -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Contrato:</div>
            <div class="info-value">
                {{ $orden->contrato ? ($orden->contrato->nombre_contrato ?? '-') : '-' }}
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">N° de orden de trabajo:</div>
            <div class="info-value">{{ $orden->numero_ot ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Empresa:</div>
            <div class="info-value">{{ $orden->contrato ? ($orden->contrato->proveedor ?? '-') : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">RUT empresa:</div>
            <div class="info-value">{{ $rutProveedor ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Establecimiento:</div>
            <div class="info-value">{{ $orden->establecimiento ? $orden->establecimiento->nombre : '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Comuna:</div>
            <div class="info-value">{{ $orden->comuna ? $orden->comuna->nombre : ($orden->establecimiento && $orden->establecimiento->comuna ? $orden->establecimiento->comuna->nombre : '-') }}</div>
        </div>
    </div>

    <!-- Línea de separación -->
    <div style="border-top: 1px solid #000; margin: 20px 0;"></div>

    <!-- Actividades desarrolladas -->
    <div class="actividades-section">
        <div class="actividades-title">
            Actividades desarrolladas, indicar si corresponden a diagnóstico, mantención y/o reparación:
        </div>
        <ul class="actividades-list">
            @forelse($presupuestoItems as $item)
            <li>
                {{ $item['numero_partida'] ? $item['numero_partida'] . ' - ' : '' }}{{ $item['partida'] }}
                @if($item['cantidad'] > 0)
                    (Cantidad: {{ number_format($item['cantidad'], 2, ',', '.') }} {{ $item['unidad'] }})
                @endif
            </li>
            @empty
            <li>No hay actividades registradas en el presupuesto</li>
            @endforelse
        </ul>
    </div>

    <!-- Línea de separación -->
    <div style="border-top: 1px solid #000; margin: 20px 0;"></div>

    <!-- Recepción Conforme Establecimiento -->
    <div class="recepcion-section">
        <div class="recepcion-title" style="margin-top: 20px; font-weight: bold;">Recepción Conforme Establecimiento:</div>
        <div style="margin: 10px 0; font-size: 11px;">
            Declaro haber recibido los servicios indicados en este documento, conforme a lo solicitado.
        </div>
        
        <div style="margin-top: 15px; font-size: 11px;">
            Director _____&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inspector General _____&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Profesor Encargado _____
        </div>
        
        <div class="info-row" style="margin-top: 20px;">
            <div class="info-label">Fecha:</div>
            <div class="info-value" style="width: 200px;">_________________________________</div>
        </div>
        
        <div class="info-row" style="margin-top: 15px;">
            <div class="info-label">Nombre:</div>
            <div class="info-value" style="width: 600px;">________________________________________________________________________________</div>
        </div>
        
        <div class="info-row" style="margin-top: 15px;">
            <div class="info-label">Rut:</div>
            <div class="info-value" style="width: 200px;">_________________________________</div>
        </div>
        
        <div class="info-row" style="margin-top: 30px;">
            <div class="info-label">Firma/Timbre:</div>
            <div class="info-value" style="width: 300px;">_________________________________</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generarEImprimir() {
    window.print();
}
</script>
@endpush

@endsection

