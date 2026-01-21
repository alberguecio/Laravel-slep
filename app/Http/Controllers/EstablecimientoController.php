<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Comuna;
use App\Models\MontoConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EstablecimientoController extends Controller
{
    /**
     * Mostrar listado de establecimientos
     */
    public function index(Request $request)
    {
        // Obtener montos de configuración para el resumen
        $montoSubvencionMant = MontoConfiguracion::where('codigo', 'subvencion_mantenimiento')->first();
        $montoSubvencionGeneral = MontoConfiguracion::where('codigo', 'subvencion_general')->first();
        $montoVTF = MontoConfiguracion::where('codigo', 'mantencion_vtf')->first();

        // Calcular totales desde establecimientos
        $totalSubvencionMant = Establecimiento::sum('subvencion_mantenimiento');
        
        // Calcular suma de montos de establecimientos regulares (sin VTF)
        // Primero identificar cuáles son regulares
        $establecimientosRegularesIds = Establecimiento::where(function($query) {
            $query->where('tipo', 'Regular')
                  ->orWhere(function($q) {
                      $q->whereNull('tipo')
                        ->where(function($subQ) {
                            $subQ->where('nombre', 'NOT LIKE', '%VTF%')
                                 ->where('nombre', 'NOT LIKE', '%Jardín%')
                                 ->where('nombre', 'NOT LIKE', '%JARDIN%')
                                 ->where('nombre', 'NOT LIKE', '%jardín%');
                        });
                  });
        })->pluck('id');
        
        $sumaMontosRegulares = Establecimiento::whereIn('id', $establecimientosRegularesIds)
            ->sum('subvencion_mantenimiento');
        
        // Comparar con el monto configurado en Presupuestos
        $diferenciaMontos = null;
        $hayDiferencia = false;
        if ($montoSubvencionMant && $sumaMontosRegulares) {
            $diferenciaMontos = $montoSubvencionMant->monto - $sumaMontosRegulares;
            // Considerar diferencia si es mayor a $0.01 (cualquier diferencia)
            $hayDiferencia = abs($diferenciaMontos) > 0.01;
        } elseif ($montoSubvencionMant) {
            // Si hay monto configurado pero no hay suma de regulares
            $diferenciaMontos = $montoSubvencionMant->monto;
            $hayDiferencia = true;
        }
        
        // Contar establecimientos VTF (usando campo tipo de BD o nombre como fallback)
        $establecimientosVTF = Establecimiento::where(function($query) {
            $query->where('tipo', 'VTF')
                  ->orWhere(function($q) {
                      $q->whereNull('tipo')
                        ->where(function($subQ) {
                            $subQ->where('nombre', 'LIKE', '%VTF%')
                                 ->orWhere('nombre', 'LIKE', '%Jardín%')
                                 ->orWhere('nombre', 'LIKE', '%JARDIN%')
                                 ->orWhere('nombre', 'LIKE', '%jardín%');
                        });
                  });
        })->count();
        
        // Contar establecimientos regulares (no VTF)
        $establecimientosRegulares = Establecimiento::where(function($query) {
            $query->where('nombre', 'NOT LIKE', '%VTF%')
                  ->where('nombre', 'NOT LIKE', '%Jardín%')
                  ->where('nombre', 'NOT LIKE', '%JARDIN%')
                  ->where('nombre', 'NOT LIKE', '%jardín%');
        })->count();
        
        $totalMantenimientoRegulares = Establecimiento::where(function($query) {
            $query->where('nombre', 'NOT LIKE', '%VTF%')
                  ->where('nombre', 'NOT LIKE', '%Jardín%')
                  ->where('nombre', 'NOT LIKE', '%JARDIN%')
                  ->where('nombre', 'NOT LIKE', '%jardín%');
        })->sum('subvencion_mantenimiento');
        
        // Calcular aporte por establecimiento (Subvención General repartida entre los 210 establecimientos)
        $totalEstablecimientos = Establecimiento::count();
        $aportePorEstablecimiento = 0;
        if ($totalEstablecimientos > 0 && $montoSubvencionGeneral) {
            $aportePorEstablecimiento = $montoSubvencionGeneral->monto / $totalEstablecimientos;
        }
        
        // Calcular mantención VTF por establecimiento (repartida entre establecimientos tipo VTF)
        $mantencionVTFPorEstablecimiento = 0;
        if ($establecimientosVTF > 0 && $montoVTF) {
            $mantencionVTFPorEstablecimiento = $montoVTF->monto / $establecimientosVTF;
        }

        // Obtener comunas
        $comunas = Comuna::orderBy('nombre')->get();

        // Verificar qué columnas de contacto existen
        $hasMatricula = Schema::hasColumn('establecimientos', 'matricula');
        $hasDirector = Schema::hasColumn('establecimientos', 'director');
        $hasTelefono = Schema::hasColumn('establecimientos', 'telefono');
        $hasEmail = Schema::hasColumn('establecimientos', 'email');
        
        // Filtrar por comuna si se seleccionó
        $query = Establecimiento::with('comuna');
        
        if ($request->has('comuna_id') && $request->comuna_id != '') {
            $query->where('comuna_id', $request->comuna_id);
        }

        // Obtener todos los establecimientos (get() carga todos los campos por defecto)
        // Forzar que se carguen todos los atributos explícitamente
        $establecimientos = $query->get()->each(function($est) {
            // Asegurar que los atributos estén disponibles
            $est->makeVisible(['matricula', 'director', 'telefono', 'email']);
        });
        
        // Ordenar manualmente por comuna y nombre
        $establecimientos = $establecimientos->sortBy(function($est) {
            $comunaNombre = $est->comuna ? $est->comuna->nombre : 'ZZZ';
            return $comunaNombre . '|' . $est->nombre;
        })->values();

        // Agregar tipo y ruralidad calculados o usar los de la BD
        foreach ($establecimientos as $establecimiento) {
            // Los campos ya están disponibles desde el map anterior
            // Usar tipo de BD o calcularlo
            if (!$establecimiento->tipo) {
                $nombre = strtoupper($establecimiento->nombre);
                if (strpos($nombre, 'VTF') !== false || strpos($nombre, 'JARDÍN') !== false || strpos($nombre, 'JARDIN') !== false || strpos($nombre, 'JARDIN INFANTIL') !== false) {
                    $establecimiento->tipo_calculado = 'VTF';
                } else {
                    $establecimiento->tipo_calculado = 'Regular';
                }
            } else {
                $establecimiento->tipo_calculado = $establecimiento->tipo;
            }
            
            // Aporte se reparte entre los 210 establecimientos (todos)
            $establecimiento->aporte_calculado = $aportePorEstablecimiento;
            
            // Para VTF: Subvención Mantenimiento = Mantención VTF repartida entre VTF
            // Para Regular: Subvención Mantenimiento = valor de BD
            if ($establecimiento->tipo_calculado == 'VTF') {
                $establecimiento->subvencion_mantenimiento_calculada = $mantencionVTFPorEstablecimiento;
            } else {
                $establecimiento->subvencion_mantenimiento_calculada = $establecimiento->subvencion_mantenimiento;
            }

            // Usar ruralidad de BD o calcularla desde el nombre
            if (!$establecimiento->ruralidad) {
                $nombre = strtoupper($establecimiento->nombre);
                if (strpos($nombre, 'RURAL') !== false) {
                    // Verificar si es Insular o solo Rural según comuna
                    if ($establecimiento->comuna && in_array(strtoupper($establecimiento->comuna->nombre), ['QUINCHAO', 'CURACO DE VÉLEZ', 'PUQUELDÓN', 'QUEILÉN'])) {
                        $establecimiento->ruralidad_calculada = 'Insular/Rural';
                    } else {
                        $establecimiento->ruralidad_calculada = 'Rural';
                    }
                } else {
                    $establecimiento->ruralidad_calculada = 'Urbano';
                }
            } else {
                $establecimiento->ruralidad_calculada = $establecimiento->ruralidad;
            }
        }

        // Datos para otras pestañas también
        $usuarios = \App\Models\Usuario::select('id', 'nombre', 'email', 'rol', 'cargo', 'estado', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        $montos = \App\Models\MontoConfiguracion::orderBy('id')->get();
        $items = \App\Models\Item::with('montosConfiguracion')->orderBy('nombre')->get();
        $proveedores = \App\Models\Oferente::orderBy('nombre')->get();

        return view('configuracion.index', compact(
            'establecimientos',
            'comunas',
            'montoSubvencionMant',
            'montoSubvencionGeneral',
            'montoVTF',
            'totalSubvencionMant',
            'establecimientosVTF',
            'totalMantenimientoRegulares',
            'establecimientosRegulares',
            'aportePorEstablecimiento',
            'sumaMontosRegulares',
            'diferenciaMontos',
            'hayDiferencia',
            'usuarios',
            'montos',
            'items',
            'proveedores'
        ));
    }

    /**
     * Actualizar establecimiento
     */
    public function update(Request $request, $id)
    {
        try {
            $establecimiento = Establecimiento::findOrFail($id);
            $esVTF = $establecimiento->tipo === 'VTF';
            
            // Validación base
            $rules = [
                'nombre' => 'required|string|max:150',
                'comuna_id' => 'nullable|exists:comunas,id',
                'rbd' => 'required|string|max:20',
                'tipo' => 'required|string|in:Regular,VTF',
                'ruralidad' => 'required|string|in:Rural,Urbano,Insular/Rural',
                'matricula' => 'nullable|integer|min:0',
                'director' => 'nullable|string|max:150',
                'telefono' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:150',
            ];
            
            // Para VTF, los montos no se editan (son automáticos)
            // Para Regular, solo Subvención Mantenimiento es requerida
            // Aporte siempre se calcula automáticamente
            if ($request->tipo === 'VTF' || $esVTF) {
                $rules['subvencion_mantenimiento'] = 'nullable|numeric|min:0';
            } else {
                $rules['subvencion_mantenimiento'] = 'required|numeric|min:0';
            }
            // Aporte nunca se requiere (siempre se calcula automáticamente)
            $rules['aporte_subvencion_general'] = 'nullable|numeric|min:0';
            
            $validated = $request->validate($rules);
            
            // Para VTF, no actualizar montos (se calculan automáticamente)
            if ($request->tipo === 'VTF' || $esVTF) {
                unset($validated['subvencion_mantenimiento']);
            }
            // Aporte nunca se actualiza (siempre se calcula automáticamente para todos)
            unset($validated['aporte_subvencion_general']);
            
            // Convertir comuna_id vacío a null
            if (isset($validated['comuna_id']) && $validated['comuna_id'] === '') {
                $validated['comuna_id'] = null;
            }
            
            // Convertir campos vacíos a null para los campos de contacto
            if (isset($validated['matricula']) && $validated['matricula'] === '') {
                $validated['matricula'] = null;
            }
            if (isset($validated['director']) && trim($validated['director']) === '') {
                $validated['director'] = null;
            }
            if (isset($validated['telefono']) && trim($validated['telefono']) === '') {
                $validated['telefono'] = null;
            }
            if (isset($validated['email']) && trim($validated['email']) === '') {
                $validated['email'] = null;
            }

            $establecimiento->update($validated);

            return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                ->with('success', 'Establecimiento actualizado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si es una petición AJAX, devolver JSON con errores
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'errors' => $e->errors(),
                    'message' => 'Error de validación'
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            // Si es una petición AJAX, devolver JSON con el error
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Crear establecimiento
     */
    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'comuna_id' => 'nullable|exists:comunas,id',
            'rbd' => 'required|string|max:20',
            'tipo' => 'required|string|in:Regular,VTF',
            'ruralidad' => 'required|string|in:Rural,Urbano,Insular/Rural',
            'matricula' => 'nullable|integer|min:0',
            'director' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
        ];
        
        // Solo Subvención Mantenimiento es requerida (y solo para Regular)
        if ($request->tipo === 'Regular') {
            $rules['subvencion_mantenimiento'] = 'required|numeric|min:0';
        } else {
            $rules['subvencion_mantenimiento'] = 'nullable|numeric|min:0';
        }
        // Aporte nunca se requiere (se calcula automáticamente)
        $rules['aporte_subvencion_general'] = 'nullable|numeric|min:0';
        
        $validated = $request->validate($rules);
        
        // Si es VTF, no guardar subvencion_mantenimiento (se calculará después)
        if ($request->tipo === 'VTF') {
            unset($validated['subvencion_mantenimiento']);
        }
        // Nunca guardar aporte_subvencion_general (siempre se calcula automáticamente)
        unset($validated['aporte_subvencion_general']);
        
        // Convertir campos vacíos a null para los campos de contacto
        if (isset($validated['matricula']) && $validated['matricula'] === '') {
            $validated['matricula'] = null;
        }
        if (isset($validated['director']) && trim($validated['director']) === '') {
            $validated['director'] = null;
        }
        if (isset($validated['telefono']) && trim($validated['telefono']) === '') {
            $validated['telefono'] = null;
        }
        if (isset($validated['email']) && trim($validated['email']) === '') {
            $validated['email'] = null;
        }

        Establecimiento::create($validated);

        return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
            ->with('success', 'Establecimiento creado exitosamente');
    }

    /**
     * Importar montos de subvención mantenimiento desde archivo
     */
    public function importarMontos(Request $request)
    {
        $request->validate([
            'archivo_montos' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240'
        ]);

        $archivo = $request->file('archivo_montos');
        $extension = $archivo->getClientOriginalExtension();
        $rutaTemporal = $archivo->getRealPath();

        $exitosos = 0;
        $errores = 0;
        $mensajesError = [];

        try {
            $datos = [];

            // Leer CSV
            if (in_array(strtolower($extension), ['csv', 'txt'])) {
                $handle = fopen($rutaTemporal, 'r');
                if ($handle === false) {
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'No se pudo leer el archivo CSV');
                }

                $encabezados = fgetcsv($handle, 1000, ',');
                if (!$encabezados) {
                    fclose($handle);
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'El archivo CSV está vacío o no tiene formato válido');
                }

                // Normalizar encabezados
                $encabezados = array_map('trim', array_map('strtolower', $encabezados));
                $rbdIndex = array_search('rbd', $encabezados);
                $nombreIndex = array_search('nombre', $encabezados);
                $montoIndex = array_search('monto', $encabezados);

                if ($montoIndex === false) {
                    fclose($handle);
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'El archivo debe contener una columna "Monto"');
                }

                if ($rbdIndex === false && $nombreIndex === false) {
                    fclose($handle);
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'El archivo debe contener una columna "RBD" o "Nombre"');
                }

                while (($fila = fgetcsv($handle, 1000, ',')) !== false) {
                    if (count($fila) < 2) continue;

                    $monto = trim($fila[$montoIndex] ?? '');
                    $monto = str_replace(['$', '.', ','], '', $monto);
                    $monto = (float) $monto;

                    if ($monto <= 0) continue;

                    if ($rbdIndex !== false) {
                        $rbd = trim($fila[$rbdIndex] ?? '');
                        if ($rbd) {
                            $datos[] = ['tipo' => 'rbd', 'valor' => $rbd, 'monto' => $monto];
                        }
                    } elseif ($nombreIndex !== false) {
                        $nombre = trim($fila[$nombreIndex] ?? '');
                        if ($nombre) {
                            $datos[] = ['tipo' => 'nombre', 'valor' => $nombre, 'monto' => $monto];
                        }
                    }
                }

                fclose($handle);
            } else {
                // Para Excel necesitaríamos una librería como PhpSpreadsheet
                // Por ahora solo CSV
                return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                    ->with('error', 'Por el momento solo se aceptan archivos CSV. La funcionalidad Excel estará disponible pronto.');
            }

            // Obtener monto configurado de Presupuestos
            $montoSubvencionMant = MontoConfiguracion::where('codigo', 'subvencion_mantenimiento')->first();
            $montoTotalEsperado = $montoSubvencionMant ? $montoSubvencionMant->monto : 0;
            
            // Calcular suma de montos a importar
            $sumaMontosImportar = array_sum(array_column($datos, 'monto'));
            
            // Verificar coincidencia con el monto configurado
            $diferencia = abs($montoTotalEsperado - $sumaMontosImportar);
            $coincidencia = $diferencia <= 100; // Permitir pequeñas diferencias por redondeo
            
            // Procesar y actualizar montos
            foreach ($datos as $dato) {
                try {
                    $query = Establecimiento::where('tipo', 'Regular');

                    if ($dato['tipo'] === 'rbd') {
                        $establecimiento = $query->where('rbd', $dato['valor'])->first();
                    } else {
                        $establecimiento = $query->where('nombre', $dato['valor'])->first();
                    }

                    if ($establecimiento) {
                        $establecimiento->subvencion_mantenimiento = $dato['monto'];
                        $establecimiento->save();
                        $exitosos++;
                    } else {
                        $errores++;
                        $mensajesError[] = "No se encontró establecimiento con " . ($dato['tipo'] === 'rbd' ? 'RBD' : 'nombre') . ": " . $dato['valor'];
                    }
                } catch (\Exception $e) {
                    $errores++;
                    $mensajesError[] = "Error al procesar: " . ($dato['tipo'] === 'rbd' ? 'RBD' : 'nombre') . " " . $dato['valor'] . " - " . $e->getMessage();
                }
            }
            
            // Calcular suma final después de importar
            $sumaFinal = Establecimiento::where('tipo', 'Regular')
                ->orWhere(function($query) {
                    $query->whereNull('tipo')
                          ->where(function($subQ) {
                              $subQ->where('nombre', 'NOT LIKE', '%VTF%')
                                   ->where('nombre', 'NOT LIKE', '%Jardín%')
                                   ->where('nombre', 'NOT LIKE', '%JARDIN%')
                                   ->where('nombre', 'NOT LIKE', '%jardín%');
                          });
                })
                ->sum('subvencion_mantenimiento');
            
            $diferenciaFinal = abs($montoTotalEsperado - $sumaFinal);

            $mensaje = "Importación completada: {$exitosos} establecimientos actualizados";
            
            // Aviso sobre coincidencia ANTES de importar
            if (!$coincidencia && $montoTotalEsperado > 0) {
                $mensaje = "⚠️ ADVERTENCIA: La suma de montos en el archivo ($" . number_format($sumaMontosImportar, 0, ',', '.') . ") ";
                if ($sumaMontosImportar < $montoTotalEsperado) {
                    $mensaje .= "es menor al monto configurado en Presupuestos ($" . number_format($montoTotalEsperado, 0, ',', '.') . "). ";
                    $mensaje .= "Diferencia: <strong>$" . number_format($diferencia, 0, ',', '.') . "</strong>";
                } else {
                    $mensaje .= "es mayor al monto configurado en Presupuestos ($" . number_format($montoTotalEsperado, 0, ',', '.') . "). ";
                    $mensaje .= "Diferencia: <strong>$" . number_format($diferencia, 0, ',', '.') . "</strong>";
                }
                $mensaje .= "<br><br>Se importaron {$exitosos} establecimientos igualmente.";
            } else if ($coincidencia && $montoTotalEsperado > 0) {
                $mensaje = "✅ Importación exitosa: {$exitosos} establecimientos actualizados.<br>✅ La suma de montos coincide con el monto configurado en Presupuestos.";
            }
            
            if ($errores > 0) {
                $mensaje .= "<br><br>Errores: {$errores}";
                if (count($mensajesError) > 0 && count($mensajesError) <= 10) {
                    $mensaje .= "<br>" . implode("<br>", array_slice($mensajesError, 0, 10));
                }
            }
            
            // Mensaje adicional sobre diferencia final después de importar
            if (abs($diferenciaFinal) > 100 && $montoTotalEsperado > 0) {
                $mensaje .= "<br><br><strong>📊 Estado después de la importación:</strong><br>";
                $mensaje .= "Suma total de montos: $" . number_format($sumaFinal, 0, ',', '.') . "<br>";
                $mensaje .= "Monto configurado en Presupuestos: $" . number_format($montoTotalEsperado, 0, ',', '.') . "<br>";
                $mensaje .= "Diferencia: <strong>$" . number_format($diferenciaFinal, 0, ',', '.') . "</strong>";
            } else if ($montoTotalEsperado > 0 && abs($diferenciaFinal) <= 100) {
                $mensaje .= "<br><br>✅ La suma final coincide con el monto configurado.";
            }

            return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                ->with($errores > 0 || !$coincidencia || abs($diferenciaFinal) > 100 ? 'warning' : 'success', $mensaje);

        } catch (\Exception $e) {
        return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
            ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Importar datos de contacto desde archivo Excel/CSV
     */
    public function importarDatosContacto(Request $request)
    {
        $request->validate([
            'archivo_contacto' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240'
        ]);

        $archivo = $request->file('archivo_contacto');
        $extension = $archivo->getClientOriginalExtension();
        $rutaTemporal = $archivo->getRealPath();

        $exitosos = 0;
        $errores = 0;
        $mensajesError = [];
        $noEncontrados = [];

        try {
            $datos = [];

            // Leer CSV o Excel
            if (in_array(strtolower($extension), ['csv', 'txt'])) {
                $handle = fopen($rutaTemporal, 'r');
                if ($handle === false) {
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'No se pudo leer el archivo CSV');
                }

                // Detectar delimitador (priorizar punto y coma si existe)
                $delimitador = ',';
                $primerLinea = fgets($handle);
                rewind($handle);
                // Contar ocurrencias de cada delimitador
                $countComa = substr_count($primerLinea, ',');
                $countPuntoComa = substr_count($primerLinea, ';');
                $countTab = substr_count($primerLinea, "\t");
                
                // Usar el delimitador más común
                if ($countPuntoComa > $countComa && $countPuntoComa > $countTab) {
                    $delimitador = ';';
                } elseif ($countTab > $countComa) {
                    $delimitador = "\t";
                } else {
                    $delimitador = ',';
                }

                $encabezados = fgetcsv($handle, 1000, $delimitador);
                if (!$encabezados) {
                    fclose($handle);
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'El archivo está vacío o no tiene formato válido');
                }

                // Función para normalizar texto (manejar codificación)
                $normalizarTexto = function($texto) {
                    if (empty($texto)) return '';
                    $texto = trim($texto);
                    // Intentar detectar y convertir codificación
                    if (!mb_check_encoding($texto, 'UTF-8')) {
                        $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
                    }
                    return $texto;
                };

                // Normalizar encabezados (trim, lowercase, sin acentos)
                $encabezados = array_map(function($h) use ($normalizarTexto) {
                    $h = $normalizarTexto($h);
                    $h = mb_strtolower($h, 'UTF-8');
                    // Normalizar variantes
                    $h = str_replace(['correo', 'e-mail', 'email'], 'email', $h);
                    $h = str_replace(['teléfono', 'telefono', 'fono'], 'telefono', $h);
                    $h = str_replace(['matrícula', 'matricula'], 'matricula', $h);
                    return $h;
                }, $encabezados);

                // Buscar índices de columnas
                $nombreIndex = false;
                $rbdIndex = false;
                $matriculaIndex = false;
                $comunaIndex = false;
                $telefonoIndex = false;
                $directorIndex = false;
                $emailIndex = false;

                foreach ($encabezados as $i => $h) {
                    if (strpos($h, 'nombre') !== false) $nombreIndex = $i;
                    if (strpos($h, 'rbd') !== false) $rbdIndex = $i;
                    if (strpos($h, 'matricula') !== false) $matriculaIndex = $i;
                    if (strpos($h, 'comuna') !== false) $comunaIndex = $i;
                    if (strpos($h, 'telefono') !== false) $telefonoIndex = $i;
                    if (strpos($h, 'director') !== false) $directorIndex = $i;
                    if (strpos($h, 'email') !== false) $emailIndex = $i;
                }

                if ($nombreIndex === false && $rbdIndex === false) {
                    fclose($handle);
                    return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                        ->with('error', 'El archivo debe contener una columna "Nombre" o "RBD" para identificar los establecimientos');
                }

                // Crear mapa de comunas por nombre
                $comunasMap = Comuna::all()->mapWithKeys(function($comuna) {
                    return [mb_strtolower(trim($comuna->nombre), 'UTF-8') => $comuna->id];
                });

                while (($fila = fgetcsv($handle, 1000, $delimitador)) !== false) {
                    if (count($fila) < 2) continue;

                    // Función para limpiar y normalizar texto
                    $limpiarTexto = function($texto) {
                        if (empty($texto)) return '';
                        $texto = trim($texto);
                        // Intentar detectar y convertir codificación
                        if (!mb_check_encoding($texto, 'UTF-8')) {
                            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
                        }
                        // Limpiar caracteres de control
                        $texto = preg_replace('/[\x00-\x1F\x7F]/', '', $texto);
                        return $texto;
                    };

                    $nombre = $nombreIndex !== false ? $limpiarTexto($fila[$nombreIndex] ?? '') : '';
                    $rbd = $rbdIndex !== false ? $limpiarTexto($fila[$rbdIndex] ?? '') : '';
                    
                    if (empty($nombre) && empty($rbd)) continue;

                    $matriculaRaw = $matriculaIndex !== false ? ($fila[$matriculaIndex] ?? '') : '';
                    $comunaNombreRaw = $comunaIndex !== false ? ($fila[$comunaIndex] ?? '') : '';
                    $telefonoRaw = $telefonoIndex !== false ? ($fila[$telefonoIndex] ?? '') : '';
                    $directorRaw = $directorIndex !== false ? ($fila[$directorIndex] ?? '') : '';
                    $emailRaw = $emailIndex !== false ? ($fila[$emailIndex] ?? '') : '';

                    // Limpiar y normalizar, convertir string vacío a null
                    $matricula = !empty($matriculaRaw) ? $limpiarTexto($matriculaRaw) : null;
                    $comunaNombre = !empty($comunaNombreRaw) ? $limpiarTexto($comunaNombreRaw) : null;
                    $telefono = !empty($telefonoRaw) ? $limpiarTexto($telefonoRaw) : null;
                    $director = !empty($directorRaw) ? $limpiarTexto($directorRaw) : null;
                    $email = !empty($emailRaw) ? $limpiarTexto($emailRaw) : null;

                    // Limpiar matrícula (solo números)
                    if ($matricula) {
                        $matricula = preg_replace('/[^0-9]/', '', $matricula);
                        $matricula = !empty($matricula) ? (int)$matricula : null;
                    }

                    // Mapear comuna
                    $comunaId = null;
                    if ($comunaNombre) {
                        $comunaNombreLower = mb_strtolower(trim($comunaNombre), 'UTF-8');
                        $comunaId = $comunasMap->get($comunaNombreLower);
                        if (!$comunaId) {
                            // Buscar coincidencia parcial
                            foreach ($comunasMap as $nombreComuna => $id) {
                                if (strpos($nombreComuna, $comunaNombreLower) !== false || 
                                    strpos($comunaNombreLower, $nombreComuna) !== false) {
                                    $comunaId = $id;
                                    break;
                                }
                            }
                        }
                    }

                    $datos[] = [
                        'nombre' => $nombre,
                        'rbd' => $rbd,
                        'matricula' => $matricula,
                        'comuna_id' => $comunaId,
                        'telefono' => $telefono,
                        'director' => $director,
                        'email' => $email,
                        'comuna_nombre' => $comunaNombre, // Para mensajes de error
                    ];
                }

                fclose($handle);
            } else {
                // Para Excel, por ahora redirigir a mensaje
                return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                    ->with('error', 'Por favor, guarda el archivo Excel como CSV (Delimitado por comas) e intenta nuevamente. La funcionalidad Excel estará disponible pronto.');
            }

            // Procesar y actualizar datos
            foreach ($datos as $dato) {
                try {
                    // Buscar establecimiento por nombre o RBD
                    $establecimiento = null;
                    
                    // Primero intentar por RBD (más preciso)
                    if (!empty($dato['rbd'])) {
                        $establecimiento = Establecimiento::where('rbd', trim($dato['rbd']))->first();
                    }
                    
                    // Si no se encuentra por RBD, buscar por nombre
                    if (!$establecimiento && !empty($dato['nombre'])) {
                        $nombreBuscar = trim($dato['nombre']);
                        
                        // Búsqueda exacta
                        $establecimiento = Establecimiento::where('nombre', $nombreBuscar)->first();
                        
                        // Si no se encuentra exacto, buscar sin distinguir mayúsculas/minúsculas
                        if (!$establecimiento) {
                            $establecimiento = Establecimiento::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreBuscar, 'UTF-8')])->first();
                        }
                        
                        // Si aún no se encuentra, buscar coincidencia parcial (contiene)
                        if (!$establecimiento) {
                            $establecimiento = Establecimiento::whereRaw('LOWER(nombre) LIKE ?', ['%' . mb_strtolower($nombreBuscar, 'UTF-8') . '%'])->first();
                        }
                        
                        // Si aún no se encuentra, buscar al revés (el nombre del Excel contiene el nombre de BD)
                        if (!$establecimiento) {
                            $establecimiento = Establecimiento::whereRaw('? LIKE CONCAT("%", LOWER(nombre), "%")', [mb_strtolower($nombreBuscar, 'UTF-8')])->first();
                        }
                    }

                    if ($establecimiento) {
                        $actualizado = false;
                        
                        // Actualizar campos (incluso si están vacíos, para limpiar datos antiguos)
                        if (Schema::hasColumn('establecimientos', 'matricula')) {
                            $establecimiento->matricula = $dato['matricula'];
                            $actualizado = true;
                        }
                        if (Schema::hasColumn('establecimientos', 'director')) {
                            $establecimiento->director = $dato['director'];
                            $actualizado = true;
                        }
                        if (Schema::hasColumn('establecimientos', 'telefono')) {
                            $establecimiento->telefono = $dato['telefono'];
                            $actualizado = true;
                        }
                        if (Schema::hasColumn('establecimientos', 'email')) {
                            $establecimiento->email = $dato['email'];
                            $actualizado = true;
                        }
                        if ($dato['comuna_id'] !== null) {
                            $establecimiento->comuna_id = $dato['comuna_id'];
                            $actualizado = true;
                        }

                        // Guardar siempre que haya algún cambio, incluso si solo es comuna
                        if ($actualizado) {
                            try {
                                $establecimiento->save();
                                // Log temporal para debug
                                \Log::info('Establecimiento actualizado', [
                                    'id' => $establecimiento->id,
                                    'nombre' => $establecimiento->nombre,
                                    'matricula' => $establecimiento->matricula,
                                    'director' => $establecimiento->director,
                                    'telefono' => $establecimiento->telefono,
                                    'email' => $establecimiento->email,
                                ]);
                                $exitosos++;
                            } catch (\Exception $e) {
                                \Log::error('Error al guardar establecimiento: ' . $e->getMessage());
                                $errores++;
                                $mensajesError[] = "Error al guardar: " . ($dato['nombre'] ?: $dato['rbd']) . " - " . $e->getMessage();
                            }
                        } else {
                            // Si no hay cambios pero el establecimiento existe, contar como exitoso
                            $exitosos++;
                        }
                    } else {
                        $noEncontrados[] = ($dato['nombre'] ?: $dato['rbd']) . 
                            ($dato['comuna_nombre'] ? ' (Comuna: ' . $dato['comuna_nombre'] . ')' : '');
                        $errores++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    $mensajesError[] = "Error al procesar: " . ($dato['nombre'] ?: $dato['rbd']) . " - " . $e->getMessage();
                }
            }

            // Construir mensaje de respuesta
            $mensaje = "✅ Importación completada: {$exitosos} establecimientos actualizados.";
            
            if (count($noEncontrados) > 0) {
                $mensaje .= "<br><br>⚠️ No se encontraron " . count($noEncontrados) . " establecimientos:";
                $mensaje .= "<br>" . implode("<br>", array_slice($noEncontrados, 0, 20));
                if (count($noEncontrados) > 20) {
                    $mensaje .= "<br>... y " . (count($noEncontrados) - 20) . " más.";
                }
            }
            
            if (count($mensajesError) > 0) {
                $mensaje .= "<br><br>❌ Errores: " . count($mensajesError);
                if (count($mensajesError) <= 10) {
                    $mensaje .= "<br>" . implode("<br>", $mensajesError);
                }
            }

            return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                ->with($errores > 0 ? 'warning' : 'success', $mensaje);

        } catch (\Exception $e) {
            return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar establecimiento
     */
    public function destroy($id)
    {
        try {
            $establecimiento = Establecimiento::findOrFail($id);
            $nombre = $establecimiento->nombre;
            $establecimiento->delete();

            // Si es una petición AJAX, devolver JSON
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Establecimiento '{$nombre}' eliminado exitosamente"
                ]);
            }

            return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                ->with('success', "Establecimiento '{$nombre}' eliminado exitosamente");
        } catch (\Exception $e) {
            // Si es una petición AJAX, devolver JSON con error
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el establecimiento: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->route('configuracion.index', ['tab' => 'establecimientos'])
                ->with('error', 'Error al eliminar el establecimiento: ' . $e->getMessage());
        }
    }
}

