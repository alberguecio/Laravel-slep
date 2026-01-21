@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-folder"></i> {{ $title ?? 'Proyectos' }}</h2>
        <p class="text-muted">Gestión de Proyectos</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
    </div>
</div>

<!-- Tabla de Proyectos -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" id="searchInput" placeholder="Buscar proyectos...">
            </div>
            <div class="col-md-6 text-end">
                <select class="form-select d-inline-block" style="width: auto;">
                    <option>Todos los estados</option>
                    <option>Activo</option>
                    <option>En proceso</option>
                    <option>Finalizado</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Item</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Código IDI</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="proyectosTable">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Obtener token del localStorage (simulado, en producción vendría del login)
let token = localStorage.getItem('token') || 'demo-token';

// Cargar proyectos desde API
fetch('/api/proyectos', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
})
.then(response => {
    if (response.status === 401) {
        // Redirigir al login si no hay token válido
        window.location.href = '/login';
        return;
    }
    return response.json();
})
.then(data => {
    console.log('Datos recibidos:', data);
    const tbody = document.getElementById('proyectosTable');
    
    if (data.success && data.data && data.data.length > 0) {
        tbody.innerHTML = data.data.map(proyecto => `
            <tr>
                <td>${proyecto.nombre || '-'}</td>
                <td>${proyecto.item?.nombre || '-'}</td>
                <td>$${Number(proyecto.monto_asignado || 0).toLocaleString('es-CL')}</td>
                <td>
                    <span class="badge bg-${getStatusColor(proyecto.estado)}">
                        ${proyecto.estado || '-'}
                    </span>
                </td>
                <td>${proyecto.codigo_idi || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" title="Ver">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i><br>
                    No hay proyectos disponibles
                </td>
            </tr>
        `;
    }
})
.catch(error => {
    console.error('Error cargando proyectos:', error);
    document.getElementById('proyectosTable').innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-danger py-4">
                <i class="bi bi-exclamation-triangle"></i><br>
                Error al cargar proyectos<br>
                <small>${error.message}</small>
            </td>
        </tr>
    `;
});

function getStatusColor(estado) {
    const colores = {
        'activo': 'success',
        'en_proceso': 'warning',
        'finalizado': 'secondary',
        'cancelado': 'danger'
    };
    return colores[estado?.toLowerCase()] || 'secondary';
}

// Buscar proyectos
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#proyectosTable tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>
@endpush
@endsection

