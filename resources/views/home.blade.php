@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4 text-primary fw-bold">
                        <i class="bi bi-house-door"></i> Bienvenido
                    </h1>
                    <p class="text-center text-muted mb-5">SLEP Chiloé - Departamento de Infraestructura</p>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <a href="/requerimientos" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <i class="bi bi-clipboard-check me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">1. REQUERIMIENTOS</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="/ingreso-oc" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                <i class="bi bi-cart-plus me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">2. INGRESO DE OC (Órdenes de Compra)</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="/ingreso-ot" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333;">
                                <i class="bi bi-tools me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">3. INGRESO DE OT (Órdenes de Trabajo)</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="/ot-precios-unitarios" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                                <i class="bi bi-currency-dollar me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">4. OT con P.U. (Precios Unitarios)</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="/montos-saldos" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #333;">
                                <i class="bi bi-bar-chart me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">5. MONTOS Y SALDOS</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="/reportes" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #333;">
                                <i class="bi bi-file-earmark-bar-graph me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">6. VISUALIZACIÓN Y REPORTES</span>
                            </a>
                        </div>
                        <div class="col-12">
                            <a href="/configuracion" class="btn btn-lg w-100 d-flex align-items-center justify-content-start shadow-sm" style="height: 60px; background: linear-gradient(135deg, #303f9f 0%, #1976d2 100%); color: white;">
                                <i class="bi bi-gear me-3" style="font-size: 1.5rem;"></i>
                                <span class="fw-bold">7. CONFIGURACIÓN</span>
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
.btn:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}
</style>
@endsection
