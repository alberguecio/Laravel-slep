@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 position-relative">
    <!-- Botón Libreta de Direcciones -->
    <div class="address-book-button-container">
        <a href="{{ route('libreta-direcciones.index') }}" class="btn address-book-btn" title="Libreta de Direcciones">
            <i class="bi bi-journal-bookmark"></i>
        </a>
    </div>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card shadow-lg border-0 position-relative">
                <div class="card-body p-4">
                    <!-- Logo de fondo con transparencia solo en la parte superior -->
                    <div class="home-logo-background">
                        <img src="{{ asset('Logo.png') }}" alt="Logo SLEP Chiloé" class="home-logo-bg">
                    </div>
                    <div class="text-center mb-4 home-title-section position-relative">
                        <h1 class="text-primary fw-bold mb-2 position-relative">
                            <i class="bi bi-house-door"></i> Bienvenido
                        </h1>
                        <p class="text-muted mb-0 position-relative">SLEP Chiloé - Departamento de Infraestructura</p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <a href="/requerimientos" class="btn btn-lg w-100 d-flex align-items-center justify-content-center shadow-sm home-btn position-relative" style="height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                                <div class="text-center">
                                    <i class="bi bi-clipboard-check d-block mb-2" style="font-size: 1.8rem;"></i>
                                    <span class="fw-bold">REQUERIMIENTOS</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="/ordenes-compra" class="btn btn-lg w-100 d-flex align-items-center justify-content-center shadow-sm home-btn" style="height: 80px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;">
                                <div class="text-center">
                                    <i class="bi bi-cart-plus d-block mb-2" style="font-size: 1.8rem;"></i>
                                    <span class="fw-bold">ÓRDENES DE COMPRA</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="/ordenes-trabajo" class="btn btn-lg w-100 d-flex align-items-center justify-content-center shadow-sm home-btn" style="height: 80px; background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; border: none;">
                                <div class="text-center">
                                    <i class="bi bi-tools d-block mb-2" style="font-size: 1.8rem;"></i>
                                    <span class="fw-bold">ÓRDENES DE TRABAJO</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="/saldos" class="btn btn-lg w-100 d-flex align-items-center justify-content-center shadow-sm home-btn" style="height: 80px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none;">
                                <div class="text-center">
                                    <i class="bi bi-calculator d-block mb-2" style="font-size: 1.8rem;"></i>
                                    <span class="fw-bold">SALDOS</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="/reportes" class="btn btn-lg w-100 d-flex align-items-center justify-content-center shadow-sm home-btn" style="height: 80px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #333; border: none;">
                                <div class="text-center">
                                    <i class="bi bi-file-earmark-bar-graph d-block mb-2" style="font-size: 1.8rem;"></i>
                                    <span class="fw-bold">REPORTES</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-md-6">
                            <a href="/configuracion" class="btn btn-lg w-100 d-flex align-items-center justify-content-center shadow-sm home-btn" style="height: 80px; background: linear-gradient(135deg, #303f9f 0%, #1976d2 100%); color: white; border: none;">
                                <div class="text-center">
                                    <i class="bi bi-gear d-block mb-2" style="font-size: 1.8rem;"></i>
                                    <span class="fw-bold">CONFIGURACIÓN</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.home-btn {
    transition: all 0.3s ease;
    border-radius: 10px;
}

.home-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2) !important;
}

.home-btn:active {
    transform: translateY(-1px);
}

/* Logo de fondo con transparencia solo en la parte superior */
.home-logo-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 20px;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}

.home-logo-bg {
    height: 64.8%;
    width: auto;
    object-fit: contain;
    opacity: 1;
    max-height: 64.8%;
}

/* Aumentar espacio superior del título */
.home-title-section {
    padding-top: 40px;
    min-height: 180px;
}

/* Botón Libreta de Direcciones */
.address-book-button-container {
    position: absolute;
    top: 10px;
    right: 20px;
    z-index: 10;
}

.address-book-btn {
    border-radius: 8px;
    width: 55px;
    height: 55px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    border: none;
    color: white;
    box-shadow: 0 4px 10px rgba(238, 90, 111, 0.3);
    transition: all 0.3s ease;
}

.address-book-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(238, 90, 111, 0.4);
    background: linear-gradient(135deg, #ff5252 0%, #e53935 100%);
}

.address-book-btn:active {
    transform: translateY(-1px);
}

.address-book-btn i {
    font-size: 1.5rem;
}

@media (max-width: 768px) {
    .home-btn {
        height: 70px !important;
    }
    
    .home-btn i {
        font-size: 1.5rem !important;
    }
    
    .home-btn span {
        font-size: 0.9rem;
    }
    
    .home-logo {
        max-width: 150px;
        max-height: 150px;
    }
    
    .address-book-button-container {
        right: 10px;
        top: 5px;
    }
    
    .address-book-btn {
        width: 50px;
        height: 50px;
    }
    
    .address-book-btn i {
        font-size: 1.3rem;
    }
}
</style>
@endsection
