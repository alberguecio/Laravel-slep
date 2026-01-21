<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Gestión Presupuestaria SLEP Chiloé' }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    @stack('styles')
    <style>
        body {
            padding-top: 0;
            margin: 0;
        }
        .navbar {
            position: relative;
            top: 0;
            z-index: 1000;
        }
        .navbar-logo {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        .navbar-collapse {
            flex-wrap: wrap;
        }
        #navbarNav .d-flex.flex-column {
            width: 100%;
            max-width: fit-content;
        }
        #navbarNav .d-flex.flex-column > .d-flex.flex-row {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
        }
        #navbarNav .d-flex.flex-column > .d-flex.flex-row > * {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                <img src="{{ asset('Logo Inicio.png') }}" alt="Logo SLEP Chiloé" class="navbar-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="/">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('requerimientos*') ? 'active' : '' }}" href="/requerimientos">
                            <i class="bi bi-clipboard-check"></i> Requerimientos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('ordenes-compra*') ? 'active' : '' }}" href="/ordenes-compra">
                            <i class="bi bi-cart-plus"></i> Ordenes de Compra
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('ordenes-trabajo*') ? 'active' : '' }}" href="/ordenes-trabajo">
                            <i class="bi bi-tools"></i> Ordenes de Trabajo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('saldos*') ? 'active' : '' }}" href="/saldos">
                            <i class="bi bi-calculator"></i> Saldos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}" href="/reportes">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                        </a>
                    </li>
                </ul>
                <div class="d-flex flex-column align-items-start ms-auto">
                    <div class="d-flex flex-row align-items-center" style="flex-wrap: nowrap !important; display: flex !important;">
                        <a class="text-white {{ request()->is('configuracion*') ? 'active' : '' }}" href="/configuracion" style="padding: 0.5rem 1rem; text-decoration: none; display: inline-block; white-space: nowrap;">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                        <form action="/logout" method="POST" style="display: inline-block; margin: 0;">
                            @csrf
                            <button type="submit" class="text-white border-0" style="background: none; cursor: pointer; text-decoration: none; padding: 0.5rem 1rem; white-space: nowrap;">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </button>
                        </form>
                    </div>
                    @php
                        $userSession = session('user');
                        // Si no hay sesión, intentar obtener desde el token JWT
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
                    @endphp
                    @if($userSession && is_array($userSession))
                    <div class="text-start" style="font-size: 0.85rem; color: white; line-height: 1.2; padding: 0.25rem 1rem;">
                        <i class="bi bi-person-circle"></i> {{ $userSession['nombre'] ?? $userSession['email'] ?? 'Usuario' }}
                    </div>
                    @elseif($userSession)
                    <div class="text-start" style="font-size: 0.85rem; color: white; line-height: 1.2; padding: 0.25rem 1rem;">
                        <i class="bi bi-person-circle"></i> {{ is_object($userSession) && isset($userSession->nombre) ? $userSession->nombre : (is_object($userSession) && isset($userSession->email) ? $userSession->email : 'Usuario') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {!! session('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {!! session('warning') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-3 bg-light text-center">
        <div class="container">
            <span class="text-muted">
                Sistema de Gestión Presupuestaria SLEP Chiloé © {{ date('Y') }}
            </span>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>

