<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

// Ruta de prueba para verificar acceso desde red local
Route::get('/test-access', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Servidor accesible desde red local',
        'ip' => request()->ip(),
        'server_time' => now()->format('Y-m-d H:i:s')
    ]);
})->withoutMiddleware([\App\Http\Middleware\CheckAuth::class]);

// Login
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->withoutMiddleware([\App\Http\Middleware\CheckAuth::class]);

Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware([\App\Http\Middleware\CheckAuth::class]);

Route::post('/logout', function () {
    // Limpiar la sesión
    session()->forget(['user', 'token']);
    session()->flush();
    
    // Redirigir al login
    return redirect('/login');
})->name('logout');

Route::get('/logout', function () {
    // También permitir logout por GET para el enlace del menú
    session()->forget(['user', 'token']);
    session()->flush();
    
    return redirect('/login');
});

// Dashboard Home
Route::get('/', function () {
    return view('pages.home');
})->name('home');

// Rutas principales
Route::get('/proyectos', function () {
    return view('proyectos.index', ['title' => 'Proyectos']);
})->name('proyectos.index');

Route::get('/contratos', function () {
    return view('proyectos.index', ['title' => 'Contratos']);
})->name('contratos.index');

Route::get('/requerimientos', function () {
    return view('proyectos.index', ['title' => 'Requerimientos']);
})->name('requerimientos.index');

Route::get('/ordenes', function () {
    return view('proyectos.index', ['title' => 'Órdenes de Trabajo']);
})->name('ordenes.index');

// Ruta de reportes movida más abajo para usar el ReporteController
// Route::get('/reportes', function () {
//     return view('proyectos.index', ['title' => 'Reportes']);
// })->name('reportes.index');

Route::get('/configuracion', function (Illuminate\Http\Request $request) {
    $tab = $request->get('tab', 'proveedores');
    
    // Si es la pestaña de establecimientos, usar el controlador específico
    if ($tab === 'establecimientos') {
        return app(App\Http\Controllers\EstablecimientoController::class)->index($request);
    }
    
    // Si es la pestaña de proyectos, usar el controlador específico
    if ($tab === 'proyectos') {
        return app(App\Http\Controllers\ProyectoController::class)->index($request);
    }
    
    // Si es la pestaña de contratos, usar el controlador específico
    if ($tab === 'contratos') {
        return app(App\Http\Controllers\ContratoController::class)->index($request);
    }
    
    // Para las demás pestañas, datos básicos
    try {
        $usuarios = \App\Models\Usuario::select('id', 'nombre', 'email', 'rol', 'cargo', 'estado', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    } catch (\Exception $e) {
        $usuarios = collect();
    }
    
    try {
        $montos = \App\Models\MontoConfiguracion::orderBy('id')->get();
    } catch (\Exception $e) {
        $montos = collect();
    }
    
    try {
        $items = \App\Models\Item::with('montosConfiguracion')->orderBy('nombre')->get();
    } catch (\Exception $e) {
        $items = collect();
    }
    
    try {
        $proveedores = \App\Models\Oferente::orderBy('nombre')->get();
    } catch (\Exception $e) {
        $proveedores = collect();
    }
    
    // Inicializar variables de establecimientos como vacías para evitar errores
    $establecimientos = collect();
    $comunas = collect();
    $montoSubvencionMant = null;
    $montoSubvencionGeneral = null;
    $montoVTF = null;
    $totalMantenimientoRegulares = 0;
    $establecimientosVTF = 0;
    $sumaMontosRegulares = 0;
    $diferenciaMontos = null;
    $hayDiferencia = false;
    
    // Inicializar variables de proyectos como vacías para evitar errores
    $proyectos = collect();
    $itemFiltro = null;
    $montoTotal = 0;
    $montoAsignado = 0;
    $saldoDisponible = 0;
    $totalProyectos = 0;
    
    return view('configuracion.index', [
        'usuarios' => $usuarios,
        'montos' => $montos,
        'items' => $items,
        'proveedores' => $proveedores,
        'establecimientos' => $establecimientos,
        'comunas' => $comunas,
        'montoSubvencionMant' => $montoSubvencionMant,
        'montoSubvencionGeneral' => $montoSubvencionGeneral,
        'montoVTF' => $montoVTF,
        'totalMantenimientoRegulares' => $totalMantenimientoRegulares,
        'establecimientosVTF' => $establecimientosVTF,
        'sumaMontosRegulares' => $sumaMontosRegulares,
        'diferenciaMontos' => $diferenciaMontos,
        'hayDiferencia' => $hayDiferencia,
        'proyectos' => $proyectos,
        'itemFiltro' => $itemFiltro,
        'montoTotal' => $montoTotal,
        'montoAsignado' => $montoAsignado,
        'saldoDisponible' => $saldoDisponible,
        'totalProyectos' => $totalProyectos
    ]);
})->name('configuracion.index');

// Nuevas rutas (actualizadas)
Route::get('/requerimientos', [App\Http\Controllers\RequerimientoController::class, 'index'])->name('requerimientos.index');
Route::post('/requerimientos', [App\Http\Controllers\RequerimientoController::class, 'store'])->name('requerimientos.store');
Route::get('/requerimientos/buscar-establecimientos', [App\Http\Controllers\RequerimientoController::class, 'buscarEstablecimientos'])->name('requerimientos.buscar-establecimientos');
Route::get('/requerimientos/{id}', [App\Http\Controllers\RequerimientoController::class, 'show'])->name('requerimientos.show');
Route::get('/requerimientos/{id}/edit', [App\Http\Controllers\RequerimientoController::class, 'edit'])->name('requerimientos.edit');
Route::put('/requerimientos/{id}', [App\Http\Controllers\RequerimientoController::class, 'update'])->name('requerimientos.update');
Route::get('/requerimientos/{id}/comentarios', [App\Http\Controllers\RequerimientoController::class, 'obtenerComentarios'])->name('requerimientos.comentarios');
Route::post('/requerimientos/{id}/comentarios', [App\Http\Controllers\RequerimientoController::class, 'agregarComentario'])->name('requerimientos.agregar-comentario');
Route::get('/requerimientos/{id}/crear-ot', [App\Http\Controllers\RequerimientoController::class, 'iniciarCreacionOT'])->name('requerimientos.crear-ot');
Route::post('/requerimientos/{id}/finalizar', [App\Http\Controllers\RequerimientoController::class, 'finalizar'])->name('requerimientos.finalizar');
Route::delete('/requerimientos/{id}', [App\Http\Controllers\RequerimientoController::class, 'destroy'])->name('requerimientos.destroy');

Route::get('/ordenes-compra', [App\Http\Controllers\OrdenCompraController::class, 'index'])->name('ordenes-compra.index');
Route::get('/ordenes-compra/contratos-disponibles', [App\Http\Controllers\OrdenCompraController::class, 'getContratosDisponibles'])->name('ordenes-compra.contratos-disponibles');
Route::get('/ordenes-compra/ordenes-trabajo/{contratoId}', [App\Http\Controllers\OrdenCompraController::class, 'getOrdenesTrabajoSinOC'])->name('ordenes-compra.ordenes-trabajo');
Route::get('/ordenes-compra/{id}', [App\Http\Controllers\OrdenCompraController::class, 'show'])->name('ordenes-compra.show');
Route::get('/ordenes-compra/{id}/formulario-recepcion-servicios', [App\Http\Controllers\OrdenCompraController::class, 'formularioRecepcionServicios'])->name('ordenes-compra.formulario-recepcion-servicios');
Route::post('/ordenes-compra/{id}/generar-rcs', [App\Http\Controllers\OrdenCompraController::class, 'generarRCS'])->name('ordenes-compra.generar-rcs');
Route::put('/ordenes-compra/{id}/actualizar-rcs', [App\Http\Controllers\OrdenCompraController::class, 'actualizarRCS'])->name('ordenes-compra.actualizar-rcs');
Route::get('/ordenes-compra/{id}/formulario-recepcion-factura', [App\Http\Controllers\OrdenCompraController::class, 'formularioRecepcionFactura'])->name('ordenes-compra.formulario-recepcion-factura');
Route::post('/ordenes-compra/{id}/generar-rcf', [App\Http\Controllers\OrdenCompraController::class, 'generarRCF'])->name('ordenes-compra.generar-rcf');
Route::put('/ordenes-compra/{id}/actualizar-rcf', [App\Http\Controllers\OrdenCompraController::class, 'actualizarRCF'])->name('ordenes-compra.actualizar-rcf');
Route::post('/ordenes-compra', [App\Http\Controllers\OrdenCompraController::class, 'store'])->name('ordenes-compra.store');
Route::put('/ordenes-compra/{id}', [App\Http\Controllers\OrdenCompraController::class, 'update'])->name('ordenes-compra.update');
Route::delete('/ordenes-compra/{id}', [App\Http\Controllers\OrdenCompraController::class, 'destroy'])->name('ordenes-compra.destroy');

Route::get('/ordenes-trabajo', [App\Http\Controllers\OrdenTrabajoController::class, 'index'])->name('ordenes-trabajo.index');
Route::post('/ordenes-trabajo', [App\Http\Controllers\OrdenTrabajoController::class, 'store'])->name('ordenes-trabajo.store');
Route::post('/ordenes-trabajo/masiva', [App\Http\Controllers\OrdenTrabajoController::class, 'storeMasiva'])->name('ordenes-trabajo.store-masiva');
Route::get('/ordenes-trabajo/{id}', [App\Http\Controllers\OrdenTrabajoController::class, 'show'])->name('ordenes-trabajo.show');
Route::get('/ordenes-trabajo/{id}/presupuesto', [App\Http\Controllers\OrdenTrabajoController::class, 'getPresupuesto'])->name('ordenes-trabajo.presupuesto');
Route::get('/ordenes-trabajo/{id}/acta-recepcion-conforme', [App\Http\Controllers\OrdenTrabajoController::class, 'actaRecepcionConforme'])->name('ordenes-trabajo.acta-recepcion-conforme');
Route::put('/ordenes-trabajo/{id}', [App\Http\Controllers\OrdenTrabajoController::class, 'update'])->name('ordenes-trabajo.update');
Route::delete('/ordenes-trabajo/{id}', [App\Http\Controllers\OrdenTrabajoController::class, 'destroy'])->name('ordenes-trabajo.destroy');
Route::get('/contratos/{id}/precios-unitarios', [App\Http\Controllers\ContratoController::class, 'getPreciosUnitarios'])->name('contratos.precios-unitarios');
Route::get('/configuracion/contratos/proyectos-disponibles', [App\Http\Controllers\ContratoController::class, 'getProyectosDisponibles'])->name('contratos.proyectos-disponibles');
Route::get('/contratos/{id}/descargar-adjunto/{tipo}', [App\Http\Controllers\ContratoController::class, 'descargarAdjunto'])->name('contratos.descargar-adjunto');

Route::get('/saldos', [App\Http\Controllers\SaldoController::class, 'index'])->name('saldos.index');
Route::get('/saldos/buscar-comunas', [App\Http\Controllers\SaldoController::class, 'buscarComunas'])->name('saldos.buscar-comunas');
Route::get('/saldos/buscar-establecimientos', [App\Http\Controllers\SaldoController::class, 'buscarEstablecimientos'])->name('saldos.buscar-establecimientos');
Route::get('/saldos/obtener-detalle', [App\Http\Controllers\SaldoController::class, 'obtenerSaldosDetalle'])->name('saldos.obtener-detalle');
Route::get('/saldos/contrato/{id}/detalle', [App\Http\Controllers\SaldoController::class, 'obtenerDetalleContrato'])->name('saldos.contrato.detalle');

Route::get('/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');
Route::get('/reportes/gasto-establecimientos', [App\Http\Controllers\ReporteController::class, 'obtenerGastoPorEstablecimientoFiltrado'])->name('reportes.gasto-establecimientos');

// Rutas de Configuración - Usuarios
Route::get('/configuracion/usuarios/crear', function () {
    return view('configuracion.usuarios.create');
})->name('usuarios.create');

Route::post('/configuracion/usuarios', [App\Http\Controllers\UsuarioController::class, 'store'])->name('usuarios.store');
Route::get('/configuracion/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'show'])->name('usuarios.show');
Route::put('/configuracion/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'update'])->name('usuarios.update');
Route::delete('/configuracion/usuarios/{id}', [App\Http\Controllers\UsuarioController::class, 'destroy'])->name('usuarios.destroy');

// Rutas de Configuración - Presupuestos
Route::post('/configuracion/presupuestos/items', [App\Http\Controllers\PresupuestoController::class, 'storeItem'])->name('presupuestos.items.store');
Route::put('/configuracion/presupuestos/items/{id}', [App\Http\Controllers\PresupuestoController::class, 'updateItem'])->name('presupuestos.items.update');
Route::delete('/configuracion/presupuestos/items/{id}', [App\Http\Controllers\PresupuestoController::class, 'destroyItem'])->name('presupuestos.items.destroy');
Route::post('/configuracion/presupuestos/montos', [App\Http\Controllers\PresupuestoController::class, 'storeMonto'])->name('presupuestos.montos.store');
Route::put('/configuracion/presupuestos/montos/{id}', [App\Http\Controllers\PresupuestoController::class, 'updateMonto'])->name('presupuestos.montos.update');
Route::put('/configuracion/presupuestos/montos/{id}/info', [App\Http\Controllers\PresupuestoController::class, 'updateMontoInfo'])->name('presupuestos.montos.update-info');
Route::delete('/configuracion/presupuestos/montos/{id}', [App\Http\Controllers\PresupuestoController::class, 'destroyMonto'])->name('presupuestos.montos.destroy');

// Rutas de Configuración - Proveedores
Route::post('/configuracion/proveedores', [App\Http\Controllers\ProveedorController::class, 'store'])->name('proveedores.store');
Route::put('/configuracion/proveedores/{id}', [App\Http\Controllers\ProveedorController::class, 'update'])->name('proveedores.update');
Route::delete('/configuracion/proveedores/{id}', [App\Http\Controllers\ProveedorController::class, 'destroy'])->name('proveedores.destroy');

// Rutas de Configuración - Proyectos
Route::post('/configuracion/proyectos', [App\Http\Controllers\ProyectoController::class, 'store'])->name('proyectos.store');
Route::get('/configuracion/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'show'])->name('proyectos.show');
Route::put('/configuracion/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'update'])->name('proyectos.update');
Route::delete('/configuracion/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'destroy'])->name('proyectos.destroy');

// Rutas de Configuración - Contratos
Route::post('/configuracion/contratos', [App\Http\Controllers\ContratoController::class, 'store'])->name('contratos.store');
Route::get('/configuracion/contratos/{id}', [App\Http\Controllers\ContratoController::class, 'show'])->name('contratos.show');
Route::put('/configuracion/contratos/{id}', [App\Http\Controllers\ContratoController::class, 'update'])->name('contratos.update');
Route::delete('/configuracion/contratos/{id}', [App\Http\Controllers\ContratoController::class, 'destroy'])->name('contratos.destroy');

// Rutas de Configuración - Establecimientos
Route::post('/configuracion/establecimientos', [App\Http\Controllers\EstablecimientoController::class, 'store'])->name('establecimientos.store');
Route::put('/configuracion/establecimientos/{id}', [App\Http\Controllers\EstablecimientoController::class, 'update'])->name('establecimientos.update');
Route::delete('/configuracion/establecimientos/{id}', [App\Http\Controllers\EstablecimientoController::class, 'destroy'])->name('establecimientos.destroy');
Route::post('/configuracion/establecimientos/importar-montos', [App\Http\Controllers\EstablecimientoController::class, 'importarMontos'])->name('establecimientos.importar-montos');
Route::post('/configuracion/establecimientos/importar-contacto', [App\Http\Controllers\EstablecimientoController::class, 'importarDatosContacto'])->name('establecimientos.importar-contacto');

// Ruta para ver montos (debug)
Route::get('/configuracion/presupuestos/montos', function () {
    $montos = \App\Models\MontoConfiguracion::orderBy('orden')->get();
    return view('configuracion.presupuestos.ver-montos', compact('montos'));
})->name('presupuestos.montos.ver');

// Libreta de Direcciones
Route::get('/libreta-direcciones', [App\Http\Controllers\LibretaDireccionesController::class, 'index'])->name('libreta-direcciones.index');
Route::get('/libreta-direcciones/buscar', [App\Http\Controllers\LibretaDireccionesController::class, 'buscar'])->name('libreta-direcciones.buscar');

// Ruta temporal para verificar ruralidad en BD
Route::get('/verificar-ruralidad', function () {
    // Primero, verificar todos los establecimientos (no solo los que tienen ruralidad)
    $establecimientos = \App\Models\Establecimiento::select('id', 'nombre', 'rbd', 'ruralidad', 'comuna_id')
        ->with('comuna')
        ->limit(50)
        ->get();
    
    // Contar cuántos tienen ruralidad
    $conRuralidad = $establecimientos->whereNotNull('ruralidad')->where('ruralidad', '!=', '')->count();
    
    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verificación Ruralidad</title>";
    $html .= "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style></head><body>";
    
    $html .= "<h2>Verificación de Ruralidad en Base de Datos</h2>";
    $html .= "<p>Total de establecimientos verificados: <strong>" . $establecimientos->count() . "</strong></p>";
    $html .= "<p>Establecimientos con ruralidad: <strong>" . $conRuralidad . "</strong></p>";
    $html .= "<p>Establecimientos sin ruralidad (NULL): <strong>" . ($establecimientos->count() - $conRuralidad) . "</strong></p>";
    
    $html .= "<table>";
    $html .= "<tr><th>ID</th><th>Nombre</th><th>RBD</th><th>Ruralidad (BD)</th><th>Ruralidad (getAttribute)</th><th>Comuna</th></tr>";
    
    foreach ($establecimientos as $est) {
        $ruralidadBD = $est->getAttribute('ruralidad');
        $ruralidadRaw = $est->getRawOriginal('ruralidad') ?? 'NULL';
        
        $html .= "<tr>";
        $html .= "<td>" . $est->id . "</td>";
        $html .= "<td>" . htmlspecialchars($est->nombre) . "</td>";
        $html .= "<td>" . ($est->rbd ?? 'N/A') . "</td>";
        $html .= "<td><strong>" . htmlspecialchars($ruralidadBD ?? 'NULL') . "</strong></td>";
        $html .= "<td>" . htmlspecialchars($ruralidadRaw) . "</td>";
        $html .= "<td>" . ($est->comuna ? htmlspecialchars($est->comuna->nombre) : 'N/A') . "</td>";
        $html .= "</tr>";
    }
    
    $html .= "</table>";
    
    // Verificar algunos establecimientos específicos de la búsqueda
    $html .= "<h3 style='margin-top: 30px;'>Verificación de Establecimientos de la Búsqueda</h3>";
    $terminos = ['alla', 'ines', 'blanchard'];
    
    foreach ($terminos as $termino) {
        $est = \App\Models\Establecimiento::select('id', 'nombre', 'rbd', 'ruralidad', 'comuna_id')
            ->with('comuna')
            ->where('nombre', 'LIKE', '%' . $termino . '%')
            ->first();
        
        if ($est) {
            $html .= "<div style='padding: 10px; margin: 10px 0; background-color: #e7f3ff; border-left: 4px solid #2196F3;'>";
            $html .= "<strong>Búsqueda: '$termino'</strong><br>";
            $html .= "ID: " . $est->id . "<br>";
            $html .= "Nombre: " . htmlspecialchars($est->nombre) . "<br>";
            $html .= "Ruralidad (getAttribute): <strong>" . htmlspecialchars($est->getAttribute('ruralidad') ?? 'NULL') . "</strong><br>";
            $html .= "Ruralidad (raw): " . htmlspecialchars($est->getRawOriginal('ruralidad') ?? 'NULL') . "<br>";
            $html .= "</div>";
        }
    }
    
    $html .= "</body></html>";
    
    return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
})->name('verificar-ruralidad');

// Ruta temporal para verificar datos de establecimientos
Route::get('/verificar-datos-establecimientos', function () {
    $schema = \Illuminate\Support\Facades\Schema::class;
    
    // Verificar qué columnas existen
    $hasMatricula = \Illuminate\Support\Facades\Schema::hasColumn('establecimientos', 'matricula');
    $hasDirector = \Illuminate\Support\Facades\Schema::hasColumn('establecimientos', 'director');
    $hasTelefono = \Illuminate\Support\Facades\Schema::hasColumn('establecimientos', 'telefono');
    $hasEmail = \Illuminate\Support\Facades\Schema::hasColumn('establecimientos', 'email');
    
    // Construir select dinámicamente
    $selectFields = ['id', 'nombre'];
    if ($hasMatricula) $selectFields[] = 'matricula';
    if ($hasDirector) $selectFields[] = 'director';
    if ($hasTelefono) $selectFields[] = 'telefono';
    if ($hasEmail) $selectFields[] = 'email';
    
    $establecimientos = \App\Models\Establecimiento::select($selectFields)
        ->orderBy('nombre')
        ->limit(50)
        ->get();
    
    $conMatricula = $hasMatricula ? $establecimientos->whereNotNull('matricula')->where('matricula', '!=', '')->count() : 0;
    $conDirector = $hasDirector ? $establecimientos->whereNotNull('director')->where('director', '!=', '')->count() : 0;
    $conTelefono = $hasTelefono ? $establecimientos->whereNotNull('telefono')->where('telefono', '!=', '')->count() : 0;
    $conEmail = $hasEmail ? $establecimientos->whereNotNull('email')->where('email', '!=', '')->count() : 0;
    
    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verificación de Datos</title>";
    $html .= "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .con-datos { background-color: #d4edda; }
        .sin-datos { background-color: #f8d7da; }
    </style></head><body>";
    
    $html .= "<h2>Verificación de Datos de Contacto en Establecimientos</h2>";
    $html .= "<h3>Total de establecimientos verificados: " . $establecimientos->count() . "</h3>";
    
    $html .= "<div style='margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #2196F3;'>";
    $html .= "<strong>Resumen:</strong><br>";
    $html .= "Con Matrícula: <strong>$conMatricula</strong><br>";
    $html .= "Con Director: <strong>$conDirector</strong><br>";
    $html .= "Con Teléfono: <strong>$conTelefono</strong><br>";
    $html .= "Con Email: <strong>$conEmail</strong><br>";
    $html .= "</div>";
    
    $html .= "<table>";
    $html .= "<tr><th>ID</th><th>Nombre</th>";
    if ($hasMatricula) $html .= "<th>Matrícula</th>";
    if ($hasDirector) $html .= "<th>Director</th>";
    if ($hasTelefono) $html .= "<th>Teléfono</th>";
    if ($hasEmail) $html .= "<th>Email</th>";
    $html .= "</tr>";
    
    foreach ($establecimientos as $est) {
        $tieneDatos = false;
        if ($hasMatricula && !empty($est->matricula)) $tieneDatos = true;
        if ($hasDirector && !empty($est->director)) $tieneDatos = true;
        if ($hasTelefono && !empty($est->telefono)) $tieneDatos = true;
        if ($hasEmail && !empty($est->email)) $tieneDatos = true;
        
        $clase = $tieneDatos ? 'con-datos' : 'sin-datos';
        
        $html .= "<tr class='$clase'>";
        $html .= "<td>" . $est->id . "</td>";
        $html .= "<td>" . htmlspecialchars($est->nombre) . "</td>";
        if ($hasMatricula) {
            $html .= "<td>" . ($est->matricula ?? '<em>NULL</em>') . "</td>";
        }
        if ($hasDirector) {
            $html .= "<td>" . ($est->director ? htmlspecialchars($est->director) : '<em>NULL</em>') . "</td>";
        }
        if ($hasTelefono) {
            $html .= "<td>" . ($est->telefono ? htmlspecialchars($est->telefono) : '<em>NULL</em>') . "</td>";
        }
        if ($hasEmail) {
            $html .= "<td>" . ($est->email ? htmlspecialchars($est->email) : '<em>NULL</em>') . "</td>";
        }
        $html .= "</tr>";
    }
    
    $html .= "</table>";
    
    $html .= "<h3 style='margin-top: 30px;'>Verificación de Columnas en la Base de Datos</h3>";
    $html .= "<div style='padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;'>";
    
    try {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('establecimientos');
        $columnasEsperadas = ['matricula', 'director', 'telefono', 'email'];
        
        foreach ($columnasEsperadas as $col) {
            $existe = in_array($col, $columns);
            $icono = $existe ? '✅' : '❌';
            $html .= "$icono Columna <strong>$col</strong>: " . ($existe ? 'EXISTE' : 'NO EXISTE') . "<br>";
        }
    } catch (\Exception $e) {
        $html .= "Error al verificar columnas: " . $e->getMessage();
    }
    
    $html .= "</div></body></html>";
    
    return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
})->name('verificar-datos-establecimientos');

