@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Menu de Configuración -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <!-- Select para móviles -->
            <div class="d-md-none p-3">
                <select class="form-select form-select-lg" id="configSelectMobile" onchange="window.location.href=this.value">
                    <option value="{{ route('configuracion.index', ['tab' => 'proveedores']) }}" {{ request('tab', 'proveedores') == 'proveedores' ? 'selected' : '' }}>PROVEEDORES</option>
                    <option value="{{ route('configuracion.index', ['tab' => 'proyectos']) }}" {{ request('tab') == 'proyectos' ? 'selected' : '' }}>PROYECTOS</option>
                    <option value="{{ route('configuracion.index', ['tab' => 'contratos']) }}" {{ request('tab') == 'contratos' ? 'selected' : '' }}>CONTRATOS</option>
                    <option value="{{ route('configuracion.index', ['tab' => 'presupuestos']) }}" {{ request('tab') == 'presupuestos' ? 'selected' : '' }}>PRESUPUESTOS</option>
                    <option value="{{ route('configuracion.index', ['tab' => 'establecimientos']) }}" {{ request('tab') == 'establecimientos' ? 'selected' : '' }}>ESTABLECIMIENTOS</option>
                    <option value="{{ route('configuracion.index', ['tab' => 'usuarios']) }}" {{ request('tab') == 'usuarios' ? 'selected' : '' }}>USUARIOS</option>
                    <option value="{{ route('configuracion.index', ['tab' => 'auditoria']) }}" {{ request('tab') == 'auditoria' ? 'selected' : '' }}>AUDITORÍA</option>
                </select>
            </div>
            
            <!-- Tabs para desktop -->
            <ul class="nav nav-pills d-none d-md-flex" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'proveedores']) }}" class="nav-link {{ request('tab', 'proveedores') == 'proveedores' ? 'active' : '' }}">
                        PROVEEDORES
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'proyectos']) }}" class="nav-link {{ request('tab') == 'proyectos' ? 'active' : '' }}">
                        PROYECTOS
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'contratos']) }}" class="nav-link {{ request('tab') == 'contratos' ? 'active' : '' }}">
                        CONTRATOS
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'presupuestos']) }}" class="nav-link {{ request('tab') == 'presupuestos' ? 'active' : '' }}">
                        PRESUPUESTOS
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'establecimientos']) }}" class="nav-link {{ request('tab') == 'establecimientos' ? 'active' : '' }}">
                        ESTABLECIMIENTOS
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'usuarios']) }}" class="nav-link {{ request('tab') == 'usuarios' ? 'active' : '' }}">
                        USUARIOS
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('configuracion.index', ['tab' => 'auditoria']) }}" class="nav-link {{ request('tab') == 'auditoria' ? 'active' : '' }}">
                        AUDITORÍA
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Contenido de los tabs -->
    @php
        $activeTab = request('tab', 'proveedores');
    @endphp

    @if ($activeTab === 'proveedores')
        @include('configuracion.proveedores.index')
    @elseif ($activeTab === 'proyectos')
        @include('configuracion.proyectos.index')
    @elseif ($activeTab === 'contratos')
        @include('configuracion.contratos.index')
    @elseif ($activeTab === 'presupuestos')
        @include('configuracion.presupuestos.index')
    @elseif ($activeTab === 'establecimientos')
        @include('configuracion.establecimientos.index')
    @elseif ($activeTab === 'usuarios')
        @include('configuracion.usuarios.index')
    @elseif ($activeTab === 'auditoria')
        <div class="card shadow">
            <div class="card-body">
                <h5 class="mb-4">
                    <i class="bi bi-shield-check"></i> AUDITORÍA
                </h5>
                <p class="text-muted">Registro de auditoría y logs del sistema</p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Esta sección está en desarrollo.
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.nav-pills .nav-link {
    border-radius: 0;
    border-bottom: 3px solid transparent;
    color: #333;
    font-weight: 500;
    padding: 12px 20px;
    transition: all 0.3s;
}

.nav-pills .nav-link:not(.active):hover {
    background-color: #f8f9fa;
    border-bottom-color: #e0e0e0;
}

.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: white;
    border-bottom-color: #0a58ca;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tab-content {
    margin-top: 0;
}
</style>

@endsection

