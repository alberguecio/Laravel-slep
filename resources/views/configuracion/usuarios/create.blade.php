@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Agregar Usuario</h3>
                </div>
                <div class="card-body p-4">
                    <form action="/configuracion/usuarios" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label fw-bold">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-bold">Contraseña *</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <small class="form-text text-muted">Mínimo 6 caracteres</small>
                            </div>
                            <div class="col-md-6">
                                <label for="rol" class="form-label fw-bold">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="usuario" selected>Usuario</option>
                                    <option value="admin">Administrador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="profesional">Profesional</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="cargo" class="form-label fw-bold">Cargo</label>
                                <input type="text" class="form-control" id="cargo" name="cargo">
                                <small class="form-text text-muted">Cargo para pie de firma en formularios</small>
                            </div>
                            <div class="col-md-6">
                                <label for="estado" class="form-label fw-bold">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="activo" selected>Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="/configuracion" class="btn btn-link text-muted text-decoration-none me-3">
                                CANCELAR
                            </a>
                            <button type="submit" class="btn btn-primary">
                                CREAR USUARIO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

