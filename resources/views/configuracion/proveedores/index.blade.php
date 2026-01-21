<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="mb-0">Proveedores</h4>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
            <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">+ AGREGAR PROVEEDOR</span><span class="d-sm-none">AGREGAR</span>
        </button>
    </div>

    <!-- Tabla de Proveedores -->
    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>RUT</th>
                            <th>Teléfono</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proveedores as $proveedor)
                        <tr>
                            <td class="ps-3">
                                <span class="fw-medium">{{ $proveedor->nombre }}</span>
                            </td>
                            <td>{{ $proveedor->rut ?? '-' }}</td>
                            <td>{{ $proveedor->telefono ?? '-' }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editarProveedor({{ $proveedor->id }}, '{{ addslashes($proveedor->nombre) }}', '{{ $proveedor->rut ?? '' }}', '{{ $proveedor->telefono ?? '' }}', '{{ $proveedor->email ?? '' }}', '{{ addslashes($proveedor->direccion ?? '') }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="eliminarProveedor({{ $proveedor->id }}, '{{ addslashes($proveedor->nombre) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                No hay proveedores registrados. Crea uno nuevo para comenzar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Proveedor -->
<div class="modal fade" id="modalCrearProveedor" tabindex="-1" aria-labelledby="modalCrearProveedorLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalCrearProveedorLabel">Agregar Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formProveedor" method="POST" action="{{ route('proveedores.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="rut" class="form-label fw-bold">RUT</label>
                        <input type="text" class="form-control" id="rut" name="rut" placeholder="Ej: 77.309.116-1" maxlength="12">
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-bold">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ej: +56 9 1234 5678">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Ej: contacto@proveedor.cl">
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label fw-bold">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Dirección completa">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let editingProveedorId = null;

// Función para formatear RUT chileno
function formatearRUT(valor) {
    // Remover todo excepto números y la letra K
    let rut = valor.replace(/[^0-9kK]/g, '').toUpperCase();
    
    if (rut.length === 0) return '';
    
    // Separar el dígito verificador
    let dv = rut.slice(-1);
    let numeros = rut.slice(0, -1);
    
    // Si no hay dígito verificador, retornar sin formato
    if (numeros.length === 0) return rut;
    
    // Formatear números con puntos
    let formateado = numeros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    // Agregar guion y dígito verificador
    return formateado + '-' + dv;
}

// Función para normalizar RUT (aplicar formato si no lo tiene)
function normalizarRUT(valor) {
    if (!valor) return '';
    
    // Remover todo excepto números y K
    let rut = valor.replace(/[^0-9kK]/g, '').toUpperCase();
    
    if (rut.length === 0) return '';
    
    // Si ya tiene formato con guion, solo verificar
    if (valor.includes('-')) {
        return valor;
    }
    
    // Si no tiene guion, agregarlo
    if (rut.length > 1) {
        let dv = rut.slice(-1);
        let numeros = rut.slice(0, -1);
        let formateado = numeros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return formateado + '-' + dv;
    }
    
    return valor;
}

// Aplicar formato automático mientras se escribe
document.addEventListener('DOMContentLoaded', function() {
    const rutInput = document.getElementById('rut');
    
    if (rutInput) {
        rutInput.addEventListener('input', function(e) {
            let valor = e.target.value;
            let cursorPos = e.target.selectionStart;
            
            // Formatear
            let formateado = formatearRUT(valor);
            
            // Calcular nueva posición del cursor
            let diferencia = formateado.length - valor.length;
            let nuevaPos = cursorPos + diferencia;
            
            e.target.value = formateado;
            e.target.setSelectionRange(nuevaPos, nuevaPos);
        });
        
        // Normalizar al perder el foco (blur)
        rutInput.addEventListener('blur', function(e) {
            let valor = e.target.value;
            if (valor) {
                e.target.value = normalizarRUT(valor);
            }
        });
    }
});

function editarProveedor(id, nombre, rut, telefono, email, direccion) {
    editingProveedorId = id;
    
    // Actualizar formulario
    document.getElementById('nombre').value = nombre;
    // Normalizar RUT al cargar
    document.getElementById('rut').value = normalizarRUT(rut || '');
    document.getElementById('telefono').value = telefono || '';
    document.getElementById('email').value = email || '';
    document.getElementById('direccion').value = direccion || '';
    
    // Actualizar modal
    document.getElementById('modalCrearProveedorLabel').textContent = 'Editar Proveedor';
    document.getElementById('formProveedor').action = '{{ url("/configuracion/proveedores") }}/' + id;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCrearProveedor'));
    modal.show();
}

function eliminarProveedor(id, nombre) {
    if (confirm('¿Estás seguro de eliminar el proveedor "' + nombre + '"?')) {
        fetch('/configuracion/proveedores/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(() => location.reload())
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar el proveedor');
        });
    }
}

// Resetear modal al cerrar
document.getElementById('modalCrearProveedor').addEventListener('hidden.bs.modal', function () {
    editingProveedorId = null;
    document.getElementById('formProveedor').reset();
    document.getElementById('formProveedor').action = '{{ route("proveedores.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('modalCrearProveedorLabel').textContent = 'Agregar Proveedor';
});
</script>
@endpush

