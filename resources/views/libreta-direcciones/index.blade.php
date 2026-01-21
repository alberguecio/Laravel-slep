@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h1 class="text-primary fw-bold mb-2">
                            <i class="bi bi-journal-bookmark"></i> Libreta de Direcciones
                        </h1>
                    </div>
                    
                    <!-- Buscador -->
                    <div class="mb-4">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-primary text-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="buscador-direcciones" placeholder="Buscar establecimiento, director/a, teléfono o email..." autofocus>
                            <button class="btn btn-primary" type="button" id="btn-buscar">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Resultados de búsqueda (oculto por ahora) -->
                    <div id="resultados-busqueda" class="mt-4" style="display: none;">
                        <h5 class="mb-4">
                            <i class="bi bi-list-ul"></i> Resultados de búsqueda
                            <span class="badge bg-primary ms-2" id="contador-resultados"></span>
                        </h5>
                        <div id="lista-resultados" class="row g-3">
                            <!-- Los resultados se mostrarán aquí en formato dashboard -->
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

.input-group-text {
    border-radius: 8px 0 0 8px;
}

#buscador-direcciones {
    border-radius: 0;
}

#btn-buscar {
    border-radius: 0 8px 8px 0;
}

.alert {
    border-radius: 10px;
}

.alert ul {
    padding-left: 20px;
}

.alert code {
    background-color: rgba(0, 0, 0, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.9em;
}

/* Estilos para el dashboard de resultados */
.resultado-card {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: white;
}

.resultado-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.resultado-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 10px 10px 0 0;
}

.resultado-body {
    padding: 1.5rem;
}

.info-item {
    display: flex;
    align-items: start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-icon {
    font-size: 1.2rem;
    color: #667eea;
    margin-right: 0.75rem;
    margin-top: 0.2rem;
    min-width: 24px;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 1rem;
    color: #212529;
    font-weight: 500;
}

.info-value a {
    color: #667eea;
    text-decoration: none;
}

.info-value a:hover {
    text-decoration: underline;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscador-direcciones');
    const btnBuscar = document.getElementById('btn-buscar');
    const resultados = document.getElementById('resultados-busqueda');
    const listaResultados = document.getElementById('lista-resultados');
    
    // Función para renderizar un resultado en formato dashboard
    function renderizarResultado(establecimiento) {
        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="resultado-card">
                    <div class="resultado-header">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-building"></i> ${establecimiento.nombre || establecimiento.establecimiento || 'N/A'}
                        </h6>
                    </div>
                    <div class="resultado-body">
                        <div class="info-item">
                            <i class="bi bi-geo-alt-fill info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Comuna</div>
                                <div class="info-value">${establecimiento.comuna || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-hash info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">RBD</div>
                                <div class="info-value">${establecimiento.rbd || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-people-fill info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Matrícula</div>
                                <div class="info-value">${establecimiento.matricula || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-geo info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Ruralidad</div>
                                <div class="info-value">${establecimiento.ruralidad || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-person-badge-fill info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Director(a)</div>
                                <div class="info-value">${establecimiento.director || establecimiento.directora || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-telephone-fill info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value">
                                    ${establecimiento.telefono ? `<a href="tel:${establecimiento.telefono}">${establecimiento.telefono}</a>` : 'N/A'}
                                </div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-envelope-fill info-icon"></i>
                            <div class="info-content">
                                <div class="info-label">Email</div>
                                <div class="info-value">
                                    ${establecimiento.email ? `<a href="mailto:${establecimiento.email}">${establecimiento.email}</a>` : 'N/A'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    function realizarBusqueda() {
        const termino = buscador.value.trim();
        
        if (termino === '') {
            resultados.style.display = 'none';
            return;
        }
        
        // Mostrar indicador de carga
        listaResultados.innerHTML = `
            <div class="col-12">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <p class="mt-2 text-muted">Buscando establecimientos...</p>
                </div>
            </div>
        `;
        resultados.style.display = 'block';
        
        // Realizar búsqueda AJAX
        fetch('/libreta-direcciones/buscar?q=' + encodeURIComponent(termino), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            // Verificar si hay error en la respuesta
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Verificar que data sea un array
            if (!Array.isArray(data)) {
                console.error('La respuesta no es un array:', data);
                throw new Error('Formato de respuesta inválido');
            }
            
            if (data.length === 0) {
                listaResultados.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No se encontraron resultados para "${termino}".
                        </div>
                    </div>
                `;
            } else {
                listaResultados.innerHTML = data.map(est => renderizarResultado(est)).join('');
            }
            document.getElementById('contador-resultados').textContent = data.length;
        })
        .catch(error => {
            console.error('Error en la búsqueda:', error);
            listaResultados.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Error al realizar la búsqueda: ${error.message}
                        <br><small>Revisa la consola del navegador para más detalles.</small>
                    </div>
                </div>
            `;
            document.getElementById('contador-resultados').textContent = '0';
        });
    }
    
    // Búsqueda en tiempo real mientras escribe
    let timeoutBusqueda;
    buscador.addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        const termino = buscador.value.trim();
        
        // Si está vacío, ocultar resultados
        if (termino === '') {
            resultados.style.display = 'none';
            return;
        }
        
        // Esperar 300ms después de que el usuario deje de escribir
        timeoutBusqueda = setTimeout(function() {
            realizarBusqueda();
        }, 300);
    });
    
    btnBuscar.addEventListener('click', realizarBusqueda);
    
    buscador.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(timeoutBusqueda);
            realizarBusqueda();
        }
    });
});
</script>
@endsection

