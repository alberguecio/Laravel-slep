<div class="container-fluid">
    <!-- Mensajes de éxito/error -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Gestión de Presupuestos</h4>
        <a href="{{ route('presupuestos.montos.ver') }}" class="btn btn-outline-secondary" title="Ver historial de cambios">
            <i class="bi bi-clock-history"></i> Ver Historial
        </a>
    </div>

    <!-- Resumen de Montos -->
    <div class="row g-3 mb-4" style="display: flex; flex-wrap: wrap;">
        @foreach($montos->sortBy('orden') as $monto)
        <div class="col-md-4 col-lg-auto" style="order: {{ $monto->orden ?? 999 }}; flex: 0 0 auto;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <h6 class="text-muted small mb-2">{{ $monto->nombre }}</h6>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">$</span>
                        <input type="text" 
                               class="form-control text-end fw-bold monto-editable" 
                               id="monto_input_{{ $monto->id }}"
                               data-monto-id="{{ $monto->id }}"
                               value="{{ number_format($monto->monto ?? 0, 0, ',', '.') }}" 
                               style="font-size: 1rem; cursor: text;"
                               title="Haz clic para editar">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-pencil-square text-muted" style="font-size: 0.75rem;"></i>
                        </span>
                    </div>
                    <small class="text-success mt-1" id="saved_{{ $monto->id }}" style="display: none;">
                        <i class="bi bi-check-circle"></i> Guardado
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Tabla de Items -->
    <div class="card shadow">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edición de Ítems</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearItem">
                <i class="bi bi-plus-circle"></i> + AGREGAR ÍTEM
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Fuentes de Financiamiento</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td class="ps-3">
                                <span class="fw-medium">{{ $item->nombre }}</span>
                            </td>
                            <td>
                                @if($item->montosConfiguracion->count() > 0)
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($item->montosConfiguracion as $monto)
                                        <span class="badge bg-primary">
                                            {{ $monto->nombre }}
                                        </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">Sin montos asignados</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editarItem({{ $item->id }}, {{ json_encode($item->nombre) }}, [{{ $item->montosConfiguracion->pluck('id')->join(',') }}])">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="eliminarItem({{ $item->id }}, '{{ $item->nombre }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-5">
                                No hay ítems registrados. Crea uno nuevo para comenzar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Fuentes de Financiamiento -->
<div class="card shadow mt-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edición Fuentes de Financiamiento</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearFuente">
            <i class="bi bi-plus-circle"></i> + AGREGAR FUENTE
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Monto</th>
                            <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($montos->sortBy('orden') as $monto)
                    <tr>
                        <td class="ps-3">
                            <span class="fw-medium">{{ $monto->nombre }}</span>
                        </td>
                        <td>
                            <span class="fw-bold">$ {{ number_format($monto->monto ?? 0, 0, ',', '.') }}</span>
                        </td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="editarFuente({{ $monto->id }}, {{ json_encode($monto->nombre) }}, '{{ $monto->codigo }}', {{ $monto->orden ?? 999 }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="eliminarFuente({{ $monto->id }}, {{ json_encode($monto->nombre) }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            No hay fuentes de financiamiento registradas. Crea una nueva para comenzar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Fuente de Financiamiento -->
<div class="modal fade" id="modalCrearFuente" tabindex="-1" aria-labelledby="modalCrearFuenteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalCrearFuenteLabel">Agregar Fuente de Financiamiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formFuente" method="POST" action="{{ route('presupuestos.montos.store') }}">
                @csrf
                <div id="methodFieldFuente"></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_fuente" class="form-label fw-bold">Nombre *</label>
                        <input type="text" class="form-control" id="nombre_fuente" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="monto_fuente" class="form-label fw-bold">Monto Inicial</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control text-end" id="monto_fuente" name="monto" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <small><i class="bi bi-info-circle"></i> El código y el orden se generan automáticamente.</small>
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

<!-- Modal Crear/Editar Item -->
<div class="modal fade" id="modalCrearItem" tabindex="-1" aria-labelledby="modalCrearItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalCrearItemLabel">Agregar Ítem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formItem" method="POST" action="{{ route('presupuestos.items.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fuentes de Financiamiento</label>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            @foreach($montos as $monto)
                            <div class="form-check mb-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="montos[]" 
                                       value="{{ $monto->id }}" 
                                       id="monto_{{ $monto->id }}">
                                <label class="form-check-label" for="monto_{{ $monto->id }}">
                                    {{ $monto->nombre }} <small class="text-muted">({{ $monto->codigo }})</small>
                                </label>
                            </div>
                            @endforeach
                        </div>
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
let editingItemId = null;

// Función para formatear número con separadores de miles
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Función para convertir string formateado a número
function parseFormattedNumber(str) {
    return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
}

// Agregar evento a todos los campos de monto editable
document.addEventListener('DOMContentLoaded', function() {
    const montoInputs = document.querySelectorAll('.monto-editable');
    
    montoInputs.forEach(input => {
        // Guardar valor original al entrar al campo
        input.addEventListener('focus', function() {
            this.dataset.originalValue = this.value;
            this.style.borderColor = '#0d6efd';
        });

        // Formatear mientras se escribe (solo números)
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = formatNumber(value);
            } else {
                e.target.value = '0';
            }
        });

        // Guardar al salir del campo
        input.addEventListener('blur', function() {
            const montoId = this.dataset.montoId;
            const numericValue = parseFormattedNumber(this.value);
            const savedMessage = document.getElementById('saved_' + montoId);
            
            // Ocultar mensaje de guardado
            if (savedMessage) savedMessage.style.display = 'none';
            this.style.borderColor = '';
            
            // Si el valor cambió, guardar
            if (this.dataset.originalValue !== this.value) {
                // Mostrar indicador de guardando
                const inputGroup = this.closest('.input-group');
                const icon = inputGroup.querySelector('.bi-pencil-square');
                if (icon) {
                    icon.className = 'spinner-border spinner-border-sm text-primary';
                }
                
                fetch('/configuracion/presupuestos/montos/' + montoId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ monto: numericValue })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de guardado
                        if (savedMessage) {
                            savedMessage.style.display = 'block';
                            setTimeout(() => {
                                savedMessage.style.display = 'none';
                            }, 2000);
                        }
                        
                        // Restaurar icono
                        if (icon) {
                            icon.className = 'bi bi-pencil-square text-muted';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al guardar el monto');
                    // Restaurar valor original en caso de error
                    this.value = this.dataset.originalValue || '0';
                    if (icon) {
                        icon.className = 'bi bi-pencil-square text-muted';
                    }
                });
            }
        });

        // Permitir Enter para guardar
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });
    });
});

function editarItem(id, nombre, montosIds) {
    editingItemId = id;
    
    // Actualizar formulario
    document.getElementById('nombre').value = nombre;
    
    // Desmarcar todos los checkboxes
    document.querySelectorAll('input[name="montos[]"]').forEach(cb => cb.checked = false);
    
    // Marcar los montos asociados
    montosIds.forEach(montoId => {
        const checkbox = document.getElementById('monto_' + montoId);
        if (checkbox) checkbox.checked = true;
    });
    
    // Actualizar modal
    document.getElementById('modalCrearItemLabel').textContent = 'Editar Ítem';
    document.getElementById('formItem').action = '{{ url("/configuracion/presupuestos/items") }}/' + id;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCrearItem'));
    modal.show();
}

function eliminarItem(id, nombre) {
    if (confirm('¿Estás seguro de eliminar el ítem "' + nombre + '"?\n\nNota: No se puede eliminar si tiene proyectos asociados.')) {
        // Crear un formulario temporal para enviar la petición DELETE
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/configuracion/presupuestos/items/' + id;
        
        // Agregar CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);
        
        // Agregar method spoofing para DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Agregar al body y enviar
        document.body.appendChild(form);
        form.submit();
    }
}

// Resetear modal al cerrar
document.getElementById('modalCrearItem').addEventListener('hidden.bs.modal', function () {
    editingItemId = null;
    document.getElementById('formItem').reset();
    document.getElementById('formItem').action = '{{ route("presupuestos.items.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('modalCrearItemLabel').textContent = 'Agregar Ítem';
    document.querySelectorAll('input[name="montos[]"]').forEach(cb => cb.checked = false);
});

function editarFuente(id, nombre, codigo, orden) {
    // Actualizar formulario
    document.getElementById('nombre_fuente').value = nombre;
    
    // Deshabilitar campo monto para que no se envíe
    const montoInput = document.getElementById('monto_fuente');
    if (montoInput) {
        montoInput.disabled = true;
        montoInput.name = ''; // Remover el name para que no se envíe
    }
    
    // Actualizar modal
    document.getElementById('modalCrearFuenteLabel').textContent = 'Editar Fuente de Financiamiento';
    document.getElementById('formFuente').action = '{{ url("/configuracion/presupuestos/montos") }}/' + id + '/info';
    document.getElementById('methodFieldFuente').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    // Ocultar campo monto al editar (solo se edita desde arriba)
    const montoField = document.getElementById('monto_fuente').closest('.mb-3');
    if (montoField) montoField.style.display = 'none';
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCrearFuente'));
    modal.show();
}

function eliminarFuente(id, nombre) {
    if (confirm('¿Estás seguro de eliminar la fuente de financiamiento "' + nombre + '"?\n\nNota: No se puede eliminar si tiene ítems asociados.')) {
        fetch('/configuracion/presupuestos/montos/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(() => location.reload())
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar la fuente de financiamiento');
        });
    }
}

// Resetear modal de fuente al cerrar
document.getElementById('modalCrearFuente').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formFuente').reset();
    document.getElementById('formFuente').action = '{{ route("presupuestos.montos.store") }}';
    document.getElementById('methodFieldFuente').innerHTML = '';
    document.getElementById('modalCrearFuenteLabel').textContent = 'Agregar Fuente de Financiamiento';
    
    // Restaurar campo monto
    const montoInput = document.getElementById('monto_fuente');
    if (montoInput) {
        montoInput.disabled = false;
        montoInput.name = 'monto'; // Restaurar el name
    }
    
    // Mostrar campo monto nuevamente
    const montoField = document.getElementById('monto_fuente').closest('.mb-3');
    if (montoField) montoField.style.display = 'block';
});
</script>
@endpush

