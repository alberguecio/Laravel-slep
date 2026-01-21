<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Gestión de Usuarios</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
            <i class="bi bi-plus-circle"></i> + AGREGAR USUARIO
        </button>
    </div>

    <!-- Alerta de Error (oculta por defecto) -->
    <div class="alert alert-danger d-none" id="errorAlert">
        <i class="bi bi-exclamation-triangle"></i> Error de conexión al cargar usuarios
    </div>

    <!-- Tabla de Usuarios -->
    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Cargo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                        <tbody id="usuariosTableBody">
                            @if(isset($usuarios) && $usuarios->count() > 0)
                                @foreach($usuarios as $usuario)
                                <tr>
                                    <td>{{ str_replace(['\n', "\n"], ' ', $usuario->nombre) }}</td>
                                    <td>{{ $usuario->email }}</td>
                                    <td>{{ str_replace(['\n', "\n"], ' ', $usuario->cargo ?? '-') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($usuario->rol) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $usuario->estado === 'activo' ? 'success' : 'danger' }}">
                                            {{ ucfirst($usuario->estado) }}
                                        </span>
                                    </td>
                                    <td>{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario({{ $usuario->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario({{ $usuario->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        No hay usuarios registrados
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-labelledby="modalCrearUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalCrearUsuarioLabel">Agregar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/configuracion/usuarios" method="POST" id="formCrearUsuario">
                @csrf
                <div class="modal-body">
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
                    <div class="row mb-3">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary">CREAR USUARIO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="modalEditarUsuarioLabel">Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="formEditarUsuario">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre_edit" class="form-label fw-bold">Nombre *</label>
                            <input type="text" class="form-control" id="nombre_edit" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email_edit" class="form-label fw-bold">Email *</label>
                            <input type="email" class="form-control" id="email_edit" name="email" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password_edit" class="form-label fw-bold">Contraseña</label>
                            <input type="password" class="form-control" id="password_edit" name="password" minlength="6">
                            <small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual</small>
                        </div>
                        <div class="col-md-6">
                            <label for="rol_edit" class="form-label fw-bold">Rol</label>
                            <select class="form-select" id="rol_edit" name="rol">
                                <option value="usuario">Usuario</option>
                                <option value="admin">Administrador</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="profesional">Profesional</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cargo_edit" class="form-label fw-bold">Cargo</label>
                            <input type="text" class="form-control" id="cargo_edit" name="cargo">
                            <small class="form-text text-muted">Cargo para pie de firma en formularios</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estado_edit" class="form-label fw-bold">Estado</label>
                            <select class="form-select" id="estado_edit" name="estado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary">ACTUALIZAR USUARIO</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editarUsuario(id) {
    // Cargar datos del usuario
    fetch('/configuracion/usuarios/' + id, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const usuario = data.usuario;
            
            // Llenar el formulario
            document.getElementById('nombre_edit').value = usuario.nombre || '';
            document.getElementById('email_edit').value = usuario.email || '';
            document.getElementById('rol_edit').value = usuario.rol || 'usuario';
            document.getElementById('cargo_edit').value = usuario.cargo || '';
            document.getElementById('estado_edit').value = usuario.estado || 'activo';
            document.getElementById('password_edit').value = '';
            
            // Actualizar la acción del formulario
            document.getElementById('formEditarUsuario').action = '/configuracion/usuarios/' + id;
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
            modal.show();
        } else {
            alert('Error al cargar los datos del usuario');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error al cargar los datos del usuario');
    });
}

function eliminarUsuario(id) {
    if (confirm('¿Estás seguro de eliminar este usuario?')) {
        fetch('/configuracion/usuarios/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Error al eliminar el usuario');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al eliminar el usuario');
        });
    }
}

// Limpiar formulario al cerrar modal de crear
document.getElementById('modalCrearUsuario').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formCrearUsuario').reset();
});

// Limpiar formulario al cerrar modal de editar
document.getElementById('modalEditarUsuario').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formEditarUsuario').reset();
    document.getElementById('formEditarUsuario').action = '';
});
</script>
@endpush

