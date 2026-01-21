<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Proyecto;
use App\Models\PrecioUnitario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $itemFiltro = $request->get('item_id');
        
        // Filtro por año (por defecto 2025 si no hay proyectos de 2026, guardar en sesión)
        // Verificar si hay proyectos de 2026, si no, usar 2025 como defecto
        $anioPorDefecto = Proyecto::where('anio_ejecucion', date('Y'))->exists() ? date('Y') : 2025;
        $anioFiltro = $request->get('anio', session('contratos_anio_filtro', $anioPorDefecto));
        session(['contratos_anio_filtro' => $anioFiltro]);
        
        // Obtener años disponibles desde proyectos (anio_ejecucion) y contratos
        $añosDisponibles = Proyecto::selectRaw('anio_ejecucion as año')
            ->whereNotNull('anio_ejecucion')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año')
            ->merge(
                Contrato::selectRaw('YEAR(fecha_inicio) as año')
                    ->whereNotNull('fecha_inicio')
                    ->distinct()
                    ->pluck('año')
            )
            ->merge(
                Contrato::selectRaw('YEAR(fecha_oc) as año')
                    ->whereNotNull('fecha_oc')
                    ->distinct()
                    ->pluck('año')
            )
            ->merge(
                Contrato::selectRaw('YEAR(created_at) as año')
                    ->distinct()
                    ->pluck('año')
            )
            ->unique()
            ->sortDesc()
            ->values();
        
        // Si no hay años, agregar 2025 como mínimo
        if ($añosDisponibles->isEmpty()) {
            $añosDisponibles = collect([2025]);
        }
        
        // Obtener todos los items para el filtro
        $items = \App\Models\Item::orderBy('nombre')->get();
        
        // Ordenar items según orden específico (igual que proyectos)
        $items = $items->sortBy(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            
            if (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'mantencion') !== false) {
                return '01_mantencion';
            }
            elseif (strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'suministro') !== false) {
                return '02_suministro';
            }
            elseif (strpos($nombreNormalizado, 'compra') !== false && strpos($nombreNormalizado, 'agil') !== false) {
                return '03_compra_agil';
            }
            elseif (strpos($nombreNormalizado, 'subtitulo') !== false && strpos($nombreNormalizado, '31') !== false) {
                return '04_subtitulo_31';
            }
            elseif (strpos($nombreNormalizado, 'emergencia') !== false) {
                return '05_emergencia';
            }
            elseif (strpos($nombreNormalizado, 'contingencia') !== false) {
                return '06_contingencia';
            }
            else {
                return '99_' . $nombreNormalizado;
            }
        })->values();

        // Obtener proyectos con saldo restante
        // Filtrar proyectos por año de ejecución
        $queryProyectos = Proyecto::with(['item']);
        
        // Si hay filtro de año, filtrar proyectos por año de ejecución
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $queryProyectos->where('anio_ejecucion', $anioFiltro);
        }
        
        $proyectos = $queryProyectos
            ->withSum(['contratos' => function($q) use ($anioFiltro) {
                // Solo contar contratos del año si hay filtro
                if ($anioFiltro && $anioFiltro !== 'todos') {
                    $q->where(function($q2) use ($anioFiltro) {
                        $q2->whereYear('fecha_inicio', $anioFiltro)
                          ->orWhereYear('fecha_oc', $anioFiltro)
                          ->orWhereYear('created_at', $anioFiltro);
                    });
                }
            }], 'monto_real')
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) use ($anioFiltro) {
                $p->monto_usado = (float) ($p->monto_usado ?? 0);
                
                // Si hay filtro de año, el monto restante se calcula solo con contratos del año
                // Si no hay filtro, usar el monto total asignado menos lo usado
                if ($anioFiltro && $anioFiltro !== 'todos') {
                    // Para proyectos filtrados por año, mostrar el saldo considerando solo contratos del año
                    $p->monto_restante = (float) $p->monto_asignado - $p->monto_usado;
                } else {
                    // Sin filtro, calcular normalmente
                    $p->monto_restante = (float) $p->monto_asignado - $p->monto_usado;
                }
                
                if ($p->monto_restante < 0) { $p->monto_restante = 0; }
                return $p;
            });

        // Obtener contratos con relación a proyecto e item
        $query = Contrato::with(['proyecto.item']);
        
        // Aplicar filtro por año si existe
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $query->where(function($q) use ($anioFiltro) {
                $q->whereYear('fecha_inicio', $anioFiltro)
                  ->orWhereYear('fecha_oc', $anioFiltro)
                  ->orWhereYear('created_at', $anioFiltro);
            });
        }
        
        // Aplicar filtro por item si existe (a través del proyecto)
        if ($itemFiltro) {
            $query->whereHas('proyecto', function($q) use ($itemFiltro) {
                $q->where('item_id', $itemFiltro);
            });
        }
        
        $contratos = $query->orderBy('created_at', 'desc')->get();
        
        // Agregar información de precios unitarios a cada contrato
        $contratos = $contratos->map(function($contrato) {
            try {
                $contrato->cantidad_precios = PrecioUnitario::where('contrato_id', $contrato->id)->count();
            } catch (\Exception $e) {
                $contrato->cantidad_precios = 0;
            }
            return $contrato;
        });
        
        // Agrupar contratos por item_id (a través del proyecto)
        $contratosPorItem = $contratos->groupBy(function($contrato) {
            return $contrato->proyecto->item_id ?? 0;
        });
        
        // Agrupar proyectos por item_id
        $proyectosPorItem = $proyectos->groupBy('item_id');

        // Calcular saldos globales (aplicar filtro de año si existe)
        $querySaldos = Contrato::query();
        if ($anioFiltro && $anioFiltro !== 'todos') {
            $querySaldos->where(function($q) use ($anioFiltro) {
                $q->whereYear('fecha_inicio', $anioFiltro)
                  ->orWhereYear('fecha_oc', $anioFiltro)
                  ->orWhereYear('created_at', $anioFiltro);
            });
        }
        
        // Total adjudicado = suma de todos los monto_real de contratos (filtrados por año)
        $montoTotalAdjudicado = $querySaldos->sum('monto_real') ?? 0;
        
        // Total disponible en proyectos = suma de monto_asignado de todos los proyectos
        // (Los proyectos no se filtran por año, son el presupuesto total)
        $montoTotalDisponible = Proyecto::sum('monto_asignado') ?? 0;
        
        // Saldo restante = Total disponible - Total adjudicado
        $saldoDisponible = $montoTotalDisponible - $montoTotalAdjudicado;
        
        // Total de contratos (filtrados por año)
        $totalContratos = $querySaldos->count();

        // Variables que otras pestañas esperan
        $usuarios = \App\Models\Usuario::select('id', 'nombre', 'email', 'rol', 'cargo', 'estado', 'created_at')->orderBy('created_at', 'desc')->get();
        $montos = \App\Models\MontoConfiguracion::orderBy('id')->get();
        $proveedores = \App\Models\Oferente::orderBy('nombre')->get();

        $establecimientos = collect();
        $comunas = collect();

        return view('configuracion.index', compact(
            'proyectos',
            'proyectosPorItem',
            'contratos',
            'contratosPorItem',
            'items',
            'itemFiltro',
            'montoTotalAdjudicado',
            'montoTotalDisponible',
            'saldoDisponible',
            'totalContratos',
            'usuarios',
            'montos',
            'proveedores',
            'establecimientos',
            'comunas',
            'anioFiltro',
            'añosDisponibles'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_contrato' => 'required|string|max:150',
            'proyecto_id' => 'required|exists:proyectos,id',
            'numero_contrato' => 'nullable|string|max:100',
            'id_licitacion' => 'nullable|string|max:100',
            'orden_compra' => 'nullable|string|max:50',
            'fecha_oc' => 'nullable|date',
            'proveedor' => 'nullable|string|max:150',
            'estado' => 'required|string|max:50',
            'monto_real' => 'required|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'duracion_dias' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
            'archivo_precios_unitarios' => 'nullable|file|mimes:csv,txt,xlsx,xls|max:10240',
            'archivo_contrato' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'archivo_bases' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'archivo_oferta' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        // Validar que el proyecto tenga saldo suficiente
        $proyecto = Proyecto::withSum('contratos as monto_usado', 'monto_real')->findOrFail($validated['proyecto_id']);
        $montoUsado = (float) ($proyecto->monto_usado ?? 0);
        $montoRestante = (float) $proyecto->monto_asignado - $montoUsado;
        if ($validated['monto_real'] > $montoRestante + 0.0001) {
            return back()->withErrors(['monto_real' => 'El monto supera el saldo disponible del proyecto ($'.number_format($montoRestante,0,',','.').').'])->withInput();
        }

        // Agregar el año al número de contrato si no lo tiene
        if (!empty($validated['numero_contrato'])) {
            $numeroContrato = trim($validated['numero_contrato']);
            $anioProyecto = $proyecto->anio_ejecucion ?? date('Y');
            
            // Verificar si el número ya tiene el año al final (formato: XXXX-YYYY)
            // Usar una expresión más estricta para evitar falsos positivos
            if (!preg_match('/-\d{4}$/', $numeroContrato)) {
                // Si no tiene el año, agregarlo
                $validated['numero_contrato'] = $numeroContrato . '-' . $anioProyecto;
            } else {
                // Si ya tiene año, verificar que coincida con el año del proyecto
                $anioEnNumero = substr($numeroContrato, -4);
                if ($anioEnNumero != $anioProyecto) {
                    // Si el año no coincide, actualizar al año del proyecto
                    $numeroSinAnio = preg_replace('/-\d{4}$/', '', $numeroContrato);
                    $validated['numero_contrato'] = $numeroSinAnio . '-' . $anioProyecto;
                }
            }
        }

        DB::beginTransaction();
        try {
            $contrato = Contrato::create($validated);
            
            // Guardar archivos adjuntos
            $archivosGuardados = $this->guardarArchivosAdjuntos($request, $contrato->id);
            if ($archivosGuardados) {
                $contrato->update($archivosGuardados);
            }
            
            // Procesar archivo de precios unitarios si existe
            $cantidadPrecios = 0;
            if ($request->hasFile('archivo_precios_unitarios')) {
                $cantidadPrecios = $this->procesarPreciosUnitarios($request->file('archivo_precios_unitarios'), $contrato->id);
            }
            
            DB::commit();
            $mensaje = 'Contrato creado exitosamente';
            if ($request->hasFile('archivo_precios_unitarios') && $cantidadPrecios > 0) {
                $mensaje .= ' y ' . $cantidadPrecios . ' precios unitarios cargados';
            } elseif ($request->hasFile('archivo_precios_unitarios')) {
                $mensaje .= ' (pero no se pudieron cargar precios unitarios)';
            }
            return redirect()->route('configuracion.index', ['tab' => 'contratos'])->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear el contrato: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Procesar archivo Excel/CSV de precios unitarios
     */
    private function procesarPreciosUnitarios($archivo, $contratoId)
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $rutaTemporal = $archivo->getRealPath();
        
        $precios = [];
        
        // Leer CSV
        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($rutaTemporal, 'r');
            if ($handle === false) {
                throw new \Exception('No se pudo leer el archivo CSV');
            }
            
            // Detectar el delimitador (puede ser coma, punto y coma, o tab)
            $primeraLinea = fgets($handle);
            rewind($handle); // Volver al inicio
            
            $delimitador = ',';
            if (strpos($primeraLinea, ';') !== false && substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',')) {
                $delimitador = ';';
            } elseif (strpos($primeraLinea, "\t") !== false) {
                $delimitador = "\t";
            }
            
            $encabezados = fgetcsv($handle, 1000, $delimitador);
            if (!$encabezados) {
                fclose($handle);
                throw new \Exception('El archivo CSV está vacío o no tiene formato válido');
            }
            
            // Normalizar encabezados (eliminar espacios extra y convertir a minúsculas)
            // Manejar codificación UTF-8 correctamente
            $encabezados = array_map(function($h) {
                // Limpiar BOM y espacios
                $h = trim($h);
                // Convertir a UTF-8 si es necesario
                if (!mb_check_encoding($h, 'UTF-8')) {
                    $h = mb_convert_encoding($h, 'UTF-8', 'ISO-8859-1');
                }
                // Normalizar caracteres especiales (á -> a, é -> e, etc.) para comparación
                $h = mb_strtolower($h, 'UTF-8');
                // Remover espacios múltiples
                $h = preg_replace('/\s+/', ' ', $h);
                return trim($h);
            }, $encabezados);
            
            // Buscar Número Partida primero (opcional)
            $numeroPartidaIndex = false;
            // Normalizar encabezados para comparación (minúsculas, sin espacios extra)
            $encabezadosNormalizados = array_map(function($e) {
                return strtolower(trim($e));
            }, $encabezados);
            
            $variantesNumeroPartida = [
                'numero partida', 'número partida', 'n° partida', 'nº partida',
                'numero_partida', 'número_partida', 'n°_partida', 'nº_partida',
                'n partida', 'num partida', 'núm. partida', 'num. partida',
                'n partida', 'numero de partida', 'número de partida',
                'n° partida', 'nº partida', 'numero partida', 'número partida'
            ];
            
            foreach ($variantesNumeroPartida as $variante) {
                $varianteNormalizado = strtolower(trim($variante));
                $numeroPartidaIndex = array_search($varianteNormalizado, $encabezadosNormalizados);
                if ($numeroPartidaIndex !== false) {
                    break;
                }
            }
            
            // Si no se encontró exactamente, buscar con búsqueda flexible
            if ($numeroPartidaIndex === false) {
                foreach ($encabezadosNormalizados as $idx => $encabezadoNorm) {
                    // Buscar si contiene "numero" o "número" o "n°" o "nº" Y "partida"
                    if ((stripos($encabezadoNorm, 'numero') !== false || 
                         stripos($encabezadoNorm, 'número') !== false ||
                         stripos($encabezadoNorm, 'n°') !== false ||
                         stripos($encabezadoNorm, 'nº') !== false ||
                         stripos($encabezadoNorm, 'num') !== false) && 
                        stripos($encabezadoNorm, 'partida') !== false) {
                        $numeroPartidaIndex = $idx;
                        break;
                    }
                }
            }
            
            // Log para depuración
            \Log::info('Búsqueda de columna Número Partida', [
                'numeroPartidaIndex' => $numeroPartidaIndex,
                'encabezados' => $encabezados,
                'encabezadosNormalizados' => $encabezadosNormalizados
            ]);
            
            // Buscar Título (opcional) - para agrupar partidas
            $tituloIndex = false;
            $variantesTitulo = ['titulo', 'título', 'categoria', 'categoría', 'titulo_partida', 'título_partida'];
            foreach ($variantesTitulo as $variante) {
                $tituloIndex = array_search($variante, $encabezados);
                if ($tituloIndex !== false) break;
            }
            
            // Columnas requeridas - buscar con múltiples variantes y búsqueda flexible
            $partidaIndex = false;
            $variantesPartida = ['partida', 'descripcion', 'descripción', 'item', 'partida_descripcion', 'descripcion_partida'];
            foreach ($variantesPartida as $variante) {
                $partidaIndex = array_search($variante, $encabezados);
                if ($partidaIndex !== false) break;
            }
            // Búsqueda flexible si no se encontró exactamente
            if ($partidaIndex === false) {
                foreach ($encabezados as $idx => $encabezado) {
                    if (stripos($encabezado, 'partida') !== false && stripos($encabezado, 'numero') === false && stripos($encabezado, 'precio') === false) {
                        $partidaIndex = $idx;
                        break;
                    }
                }
            }
            
            $unidadIndex = false;
            $variantesUnidad = ['unidad', 'ud', 'u', 'unidad_medida', 'umedida'];
            foreach ($variantesUnidad as $variante) {
                $unidadIndex = array_search($variante, $encabezados);
                if ($unidadIndex !== false) break;
            }
            // Búsqueda flexible si no se encontró exactamente
            if ($unidadIndex === false) {
                foreach ($encabezados as $idx => $encabezado) {
                    if (stripos($encabezado, 'unidad') !== false || stripos($encabezado, 'ud') !== false || stripos($encabezado, 'umedida') !== false) {
                        $unidadIndex = $idx;
                        break;
                    }
                }
            }
            
            $precioIndex = false;
            $variantesPrecio = ['precio unitario', 'precio_unitario', 'precio', 'precio unit', 'precio_unidad', 'precio unit.', 'precio/unitario', 'precio por unidad', 'unitario'];
            foreach ($variantesPrecio as $variante) {
                $precioIndex = array_search($variante, $encabezados);
                if ($precioIndex !== false) break;
            }
            // Búsqueda flexible si no se encontró exactamente
            if ($precioIndex === false) {
                foreach ($encabezados as $idx => $encabezado) {
                    if ((stripos($encabezado, 'precio') !== false && stripos($encabezado, 'unitario') !== false) || 
                        (stripos($encabezado, 'precio') !== false && stripos($encabezado, 'unit') !== false) ||
                        (stripos($encabezado, 'precio') !== false && count($encabezados) <= 4)) {
                        $precioIndex = $idx;
                        break;
                    }
                }
            }
            
            // Si no se encontraron las columnas requeridas, mostrar información detallada
            if ($partidaIndex === false || $unidadIndex === false || $precioIndex === false) {
                fclose($handle);
                $encabezadosEncontrados = implode(', ', $encabezados);
                $faltantes = [];
                if ($partidaIndex === false) $faltantes[] = 'Partida';
                if ($unidadIndex === false) $faltantes[] = 'Unidad';
                if ($precioIndex === false) $faltantes[] = 'Precio Unitario';
                throw new \Exception(
                    'El archivo debe contener las columnas: ' . implode(', ', $faltantes) . 
                    '. Columnas encontradas en el archivo: ' . $encabezadosEncontrados . 
                    '. Asegúrate de que la primera fila contenga los encabezados correctos.'
                );
            }
            
            $tituloActual = null; // Para mantener el título actual cuando hay filas vacías en partida
            $contadorFilas = 0;
            
            while (($fila = fgetcsv($handle, 1000, $delimitador)) !== false) {
                $contadorFilas++;
                if (count($fila) < 2) continue; // Mínimo 2 columnas
                
                // Convertir toda la fila a UTF-8 si es necesario
                $fila = array_map(function($valor) {
                    if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                        // Intentar convertir desde ISO-8859-1 (Latin-1) o Windows-1252
                        $valor = mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
                    }
                    return $valor;
                }, $fila);
                
                // Asegurar que el array tenga suficientes elementos
                while (count($fila) < max($numeroPartidaIndex !== false ? $numeroPartidaIndex + 1 : 0, $tituloIndex !== false ? $tituloIndex + 1 : 0, $partidaIndex + 1, $unidadIndex + 1, $precioIndex + 1)) {
                    $fila[] = '';
                }
                
                // Obtener título si existe
                $titulo = $tituloIndex !== false ? trim($fila[$tituloIndex] ?? '') : null;
                if (!empty($titulo)) {
                    $tituloActual = $titulo;
                }
                
                // Limpiar y convertir a UTF-8 cada campo
                $partida = isset($fila[$partidaIndex]) ? $fila[$partidaIndex] : '';
                if (!mb_check_encoding($partida, 'UTF-8')) {
                    $partida = mb_convert_encoding($partida, 'UTF-8', 'ISO-8859-1');
                }
                $partida = trim($partida);
                
                $unidad = isset($fila[$unidadIndex]) ? $fila[$unidadIndex] : '';
                if (!mb_check_encoding($unidad, 'UTF-8')) {
                    $unidad = mb_convert_encoding($unidad, 'UTF-8', 'ISO-8859-1');
                }
                $unidad = trim($unidad);
                
                $precio = isset($fila[$precioIndex]) ? $fila[$precioIndex] : '';
                if (!mb_check_encoding($precio, 'UTF-8')) {
                    $precio = mb_convert_encoding($precio, 'UTF-8', 'ISO-8859-1');
                }
                $precio = trim($precio);
                
                // Obtener numero_partida - asegurar que se lea correctamente
                $numeroPartida = null;
                if ($numeroPartidaIndex !== false && isset($fila[$numeroPartidaIndex])) {
                    $numeroPartida = $fila[$numeroPartidaIndex];
                    // Convertir a UTF-8 si es necesario
                    if (!mb_check_encoding($numeroPartida, 'UTF-8')) {
                        $numeroPartida = mb_convert_encoding($numeroPartida, 'UTF-8', 'ISO-8859-1');
                    }
                    $numeroPartida = trim($numeroPartida);
                    // Si está vacío, establecer como null
                    if ($numeroPartida === '') {
                        $numeroPartida = null;
                    } else {
                        // Log para los primeros 5 registros
                        if ($contadorFilas <= 5) {
                            \Log::info('Número Partida leído', [
                                'fila' => $contadorFilas,
                                'numeroPartidaIndex' => $numeroPartidaIndex,
                                'valor_raw' => $fila[$numeroPartidaIndex] ?? 'N/A',
                                'valor_trimmed' => $numeroPartida
                            ]);
                        }
                    }
                } elseif ($contadorFilas <= 5) {
                    \Log::info('Número Partida NO encontrado', [
                        'fila' => $contadorFilas,
                        'numeroPartidaIndex' => $numeroPartidaIndex,
                        'fila_completa' => $fila
                    ]);
                }
                
                // Determinar si es un título/categoría
                // Es título si:
                // 1. Tiene título pero no partida
                // 2. Partida es solo números/puntos (ej: "1.", "2.0")
                // 3. Tiene partida pero no tiene unidad válida Y no tiene precio válido
                $esTitulo = false;
                
                if (empty($partida)) {
                    // No tiene partida - saltar esta fila (a menos que tenga título)
                    if (!empty($titulo)) {
                        $esTitulo = true;
                        $partida = $titulo; // Usar el título como partida
                    } else {
                        continue;
                    }
                } elseif (!empty($partida) && preg_match('/^[\d\.]+$/', trim($partida))) {
                    // Partida es solo números/puntos - es un título
                    $esTitulo = true;
                } elseif (!empty($titulo) && empty($partida)) {
                    // Tiene título pero no partida - es un título
                    $esTitulo = true;
                    $partida = $titulo; // Usar el título como partida
                }
                
                // Limpiar precio (quitar símbolos de moneda)
                $precioLimpio = 0;
                if (!empty($precio)) {
                    $precioLimpio = str_replace(['$', '.', ' '], '', $precio);
                    $precioLimpio = str_replace(',', '.', $precioLimpio);
                    $precioLimpio = (float) $precioLimpio;
                }
                
                // Verificar si la unidad es válida (no vacía y no solo números/puntos)
                $unidadValida = !empty($unidad) && !preg_match('/^[\d\.]+$/', trim($unidad));
                
                // Si no se detectó como título antes, verificar si lo es por falta de datos válidos
                if (!$esTitulo) {
                    // Si tiene partida pero NO tiene unidad válida Y NO tiene precio válido, es un título
                    if (!empty($partida) && !$unidadValida && $precioLimpio <= 0) {
                        $esTitulo = true;
                    }
                }
                
                // Si es título, permitir guardarlo aunque no tenga precio o unidad válidos
                if ($esTitulo) {
                    // Para títulos, usar precio 0 y unidad vacía
                    $unidad = '';
                    $precio = 0;
                } else {
                    // Si no es título, validar que tenga unidad y precio válidos
                    if (!$unidadValida) {
                        continue; // No es título y no tiene unidad válida - saltar
                    }
                    if ($precioLimpio <= 0) {
                        continue; // No es título y no tiene precio válido - saltar
                    }
                    $precio = $precioLimpio;
                }
                
                // Guardar título en el campo partida con prefijo si existe, o solo partida
                $partidaCompleta = $partida;
                if ($tituloActual && !empty($tituloActual)) {
                    // Convertir título a UTF-8 si es necesario
                    if (!mb_check_encoding($tituloActual, 'UTF-8')) {
                        $tituloActual = mb_convert_encoding($tituloActual, 'UTF-8', 'ISO-8859-1');
                    }
                    // Si el título no está ya en la partida, agregarlo
                    if (strpos($partida, $tituloActual) === false) {
                        $partidaCompleta = $tituloActual . ' - ' . $partida;
                    }
                }
                
                // Asegurar que todos los valores estén en UTF-8 y sean válidos
                // Limpiar caracteres inválidos de UTF-8
                $partidaCompleta = mb_convert_encoding($partidaCompleta, 'UTF-8', 'UTF-8');
                // Remover caracteres que no son válidos en UTF-8
                $partidaCompleta = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $partidaCompleta);
                // Validar y limpiar UTF-8
                if (!mb_check_encoding($partidaCompleta, 'UTF-8')) {
                    $partidaCompleta = mb_convert_encoding($partidaCompleta, 'UTF-8', 'UTF-8');
                }
                
                $unidad = mb_convert_encoding($unidad, 'UTF-8', 'UTF-8');
                $unidad = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $unidad);
                if (!mb_check_encoding($unidad, 'UTF-8')) {
                    $unidad = mb_convert_encoding($unidad, 'UTF-8', 'UTF-8');
                }
                
                // Solo agregar si tiene datos válidos
                $precioItem = [
                    'contrato_id' => $contratoId,
                    'numero_partida' => $numeroPartida, // Puede ser null si no existe
                    'partida' => $partidaCompleta,
                    'unidad' => $unidad,
                    'precio' => $precio,
                ];
                
                // Log para los primeros 5 registros para verificar que se está guardando numero_partida
                if (count($precios) < 5) {
                    \Log::info('Precio a insertar', [
                        'fila' => $contadorFilas,
                        'numero_partida' => $numeroPartida,
                        'partida' => $partidaCompleta,
                        'unidad' => $unidad,
                        'precio' => $precio
                    ]);
                }
                
                $precios[] = $precioItem;
            }
            
            fclose($handle);
        } else {
            // Para Excel, usar PhpSpreadsheet si está disponible
            // Por ahora, solo CSV
            throw new \Exception('Por el momento solo se aceptan archivos CSV. Para Excel, instala PhpSpreadsheet o convierte el archivo a CSV.');
        }
        
        // Insertar precios unitarios
        if (!empty($precios)) {
            try {
                // Verificar que la tabla existe
                if (!Schema::hasTable('partidas_precios_unitarios_prueba')) {
                    throw new \Exception('La tabla partidas_precios_unitarios_prueba no existe en la base de datos. Por favor, crea la tabla primero.');
                }
                
                // Verificar que los primeros 5 precios tengan numero_partida antes de insertar
                if (count($precios) > 0) {
                    \Log::info('Resumen antes de insertar', [
                        'total_precios' => count($precios),
                        'con_numero_partida' => count(array_filter($precios, function($p) { return !empty($p['numero_partida']); })),
                        'primeros_3' => array_slice($precios, 0, 3)
                    ]);
                }
                
                PrecioUnitario::insert($precios);
                
                // Verificar que se insertaron correctamente
                $insertados = PrecioUnitario::where('contrato_id', $contratoId)
                    ->whereNotNull('numero_partida')
                    ->where('numero_partida', '!=', '')
                    ->count();
                \Log::info('Después de insertar', [
                    'total_insertados' => count($precios),
                    'con_numero_partida_en_bd' => $insertados
                ]);
                
                return count($precios); // Retornar cantidad de precios insertados
            } catch (\Exception $e) {
                \Log::error('Error al insertar precios unitarios: ' . $e->getMessage());
                throw new \Exception('Error al insertar precios unitarios: ' . $e->getMessage());
            }
        }
        return 0;
    }

    public function show($id)
    {
        $contrato = Contrato::with('proyecto')->findOrFail($id);
        
        // Contar precios unitarios existentes
        try {
            $cantidadPrecios = PrecioUnitario::where('contrato_id', $id)->count();
        } catch (\Exception $e) {
            $cantidadPrecios = 0;
        }
        
        // Formatear fechas para el formulario (YYYY-MM-DD)
        $contratoData = $contrato->toArray();
        $contratoData['fecha_inicio'] = $contrato->fecha_inicio ? $contrato->fecha_inicio->format('Y-m-d') : null;
        $contratoData['fecha_fin'] = $contrato->fecha_fin ? $contrato->fecha_fin->format('Y-m-d') : null;
        $contratoData['fecha_oc'] = $contrato->fecha_oc ? $contrato->fecha_oc->format('Y-m-d') : null;
        // Asegurar que duracion_dias esté presente (puede ser null)
        $contratoData['duracion_dias'] = $contrato->duracion_dias ?? null;
        
        return response()->json([
            'success' => true, 
            'contrato' => $contratoData,
            'cantidad_precios' => $cantidadPrecios
        ]);
    }

    /**
     * Obtener proyectos disponibles para un contrato (incluyendo el actual si se está editando)
     */
    public function getProyectosDisponibles(Request $request)
    {
        $contratoId = $request->query('contrato_id');
        
        // Obtener proyectos con saldo restante
        $proyectos = Proyecto::with(['item'])
            ->withSum('contratos as monto_usado', 'monto_real')
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                $p->monto_usado = (float) ($p->monto_usado ?? 0);
                $p->monto_restante = (float) $p->monto_asignado - $p->monto_usado;
                if ($p->monto_restante < 0) { $p->monto_restante = 0; }
                return $p;
            });
        
        // Si se está editando un contrato, incluir siempre su proyecto actual
        if ($contratoId) {
            $contratoActual = Contrato::find($contratoId);
            if ($contratoActual && $contratoActual->proyecto_id) {
                // Encontrar el proyecto actual en la lista
                $proyectoActual = $proyectos->firstWhere('id', $contratoActual->proyecto_id);
                if ($proyectoActual && $proyectoActual->monto_restante <= 0) {
                    // Si el proyecto no tiene saldo aparente, calcularlo considerando el contrato actual
                    $montoUsadoOtros = $proyectoActual->monto_usado - (float) $contratoActual->monto_real;
                    if ($montoUsadoOtros < 0) { $montoUsadoOtros = 0; }
                    $proyectoActual->monto_restante = (float) $proyectoActual->monto_asignado - $montoUsadoOtros;
                    if ($proyectoActual->monto_restante < 0) { $proyectoActual->monto_restante = 0; }
                }
            }
        }
        
        // Filtrar proyectos con saldo disponible o que sean el proyecto actual del contrato
        $proyectosDisponibles = $proyectos->filter(function ($p) use ($contratoId) {
            if ($contratoId) {
                $contratoActual = Contrato::find($contratoId);
                // Incluir si tiene saldo o es el proyecto actual del contrato
                return $p->monto_restante > 0 || ($contratoActual && $p->id == $contratoActual->proyecto_id);
            }
            return $p->monto_restante > 0;
        })->map(function ($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'item' => $p->item->nombre ?? '',
                'saldo' => (int) $p->monto_restante
            ];
        })->values();
        
        return response()->json([
            'success' => true,
            'proyectos' => $proyectosDisponibles
        ]);
    }

    public function update(Request $request, $id)
    {
        $contrato = Contrato::findOrFail($id);
        
        // Advertencia si se está editando un contrato terminado
        $estadoActual = trim($contrato->estado ?? '');
        $esTerminado = $estadoActual === 'Terminado';
        
        $validated = $request->validate([
            'nombre_contrato' => 'required|string|max:150',
            'proyecto_id' => 'required|exists:proyectos,id',
            'numero_contrato' => 'nullable|string|max:100',
            'id_licitacion' => 'nullable|string|max:100',
            'orden_compra' => 'nullable|string|max:50',
            'fecha_oc' => 'nullable|date',
            'proveedor' => 'nullable|string|max:150',
            'estado' => 'required|string|max:50',
            'monto_real' => 'required|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'duracion_dias' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
            'archivo_precios_unitarios' => 'nullable|file|mimes:csv,txt,xlsx,xls|max:10240',
            'archivo_contrato' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'archivo_bases' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'archivo_oferta' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        // Validar saldo considerando el contrato actual (permitir mantener o bajar / subir hasta saldo)
        $proyecto = Proyecto::withSum('contratos as monto_usado', 'monto_real')->findOrFail($validated['proyecto_id']);
        
        // Agregar el año al número de contrato si no lo tiene
        if (!empty($validated['numero_contrato'])) {
            $numeroContrato = trim($validated['numero_contrato']);
            $anioProyecto = $proyecto->anio_ejecucion ?? date('Y');
            
            // Verificar si el número ya tiene el año al final (formato: XXXX-YYYY)
            // Usar una expresión más estricta para evitar falsos positivos
            if (!preg_match('/-\d{4}$/', $numeroContrato)) {
                // Si no tiene el año, agregarlo
                $validated['numero_contrato'] = $numeroContrato . '-' . $anioProyecto;
            } else {
                // Si ya tiene año, verificar que coincida con el año del proyecto
                $anioEnNumero = substr($numeroContrato, -4);
                if ($anioEnNumero != $anioProyecto) {
                    // Si el año no coincide, actualizar al año del proyecto
                    $numeroSinAnio = preg_replace('/-\d{4}$/', '', $numeroContrato);
                    $validated['numero_contrato'] = $numeroSinAnio . '-' . $anioProyecto;
                }
            }
        }
        
        $montoUsadoOtros = (float) ($proyecto->monto_usado ?? 0) - (float) $contrato->monto_real;
        if ($montoUsadoOtros < 0) { $montoUsadoOtros = 0; }
        $montoRestante = (float) $proyecto->monto_asignado - $montoUsadoOtros;
        if ($validated['monto_real'] > $montoRestante + 0.0001) {
            return back()->withErrors(['monto_real' => 'El monto supera el saldo disponible del proyecto ($'.number_format($montoRestante,0,',','.').').'])->withInput();
        }

        DB::beginTransaction();
        try {
            $contrato->update($validated);
            
            // Guardar archivos adjuntos
            $archivosGuardados = $this->guardarArchivosAdjuntos($request, $contrato->id, $contrato);
            if ($archivosGuardados) {
                $contrato->update($archivosGuardados);
            }
            
            // Procesar archivo de precios unitarios si existe (reemplazar los existentes)
            $cantidadPrecios = 0;
            if ($request->hasFile('archivo_precios_unitarios')) {
                // Eliminar precios unitarios existentes antes de cargar los nuevos
                PrecioUnitario::where('contrato_id', $contrato->id)->delete();
                
                // Procesar el nuevo archivo
                $cantidadPrecios = $this->procesarPreciosUnitarios($request->file('archivo_precios_unitarios'), $contrato->id);
            }
            
            DB::commit();
            $mensaje = 'Contrato actualizado exitosamente';
            if ($request->hasFile('archivo_precios_unitarios') && $cantidadPrecios > 0) {
                $mensaje .= ' y ' . $cantidadPrecios . ' precios unitarios cargados';
            } elseif ($request->hasFile('archivo_precios_unitarios')) {
                $mensaje .= ' (pero no se pudieron cargar precios unitarios)';
            }
            
            // Advertencia si se editó un contrato terminado
            if ($esTerminado) {
                $mensaje .= ' ⚠️ Nota: Este contrato estaba terminado. Los cambios pueden afectar reportes históricos.';
            }
            
            return redirect()->route('configuracion.index', ['tab' => 'contratos'])->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar el contrato: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        $contrato = Contrato::findOrFail($id);
        $contrato->delete();
        return redirect()->route('configuracion.index', ['tab' => 'contratos'])->with('success', 'Contrato eliminado');
    }

    /**
     * Obtener precios unitarios de un contrato (AJAX)
     */
    public function getPreciosUnitarios($id)
    {
        try {
            $precios = \App\Models\PrecioUnitario::where('contrato_id', $id)
                ->get()
                ->map(function ($precio) {
                    // Obtener numero_partida directamente
                    $numeroPartida = $precio->numero_partida ?? null;
                    if ($numeroPartida === '' || $numeroPartida === null) {
                        $numeroPartida = null;
                    } else {
                        $numeroPartida = trim((string) $numeroPartida);
                    }
                    
                    // Determinar si es un título: verificar si no tiene unidad válida o precio
                    $partida = trim($precio->partida ?? '');
                    $unidad = trim($precio->unidad ?? '');
                    $precio_valor = (float) $precio->precio;
                    
                    // Es un título si: no tiene unidad, unidad es solo números/puntos, o precio es 0
                    $esTitulo = empty($unidad) || 
                               preg_match('/^[\d\.]+$/', $unidad) || 
                               $precio_valor <= 0 ||
                               (empty($unidad) && !empty($partida));
                    
                    return [
                        'id' => $precio->id,
                        'contrato_id' => $precio->contrato_id,
                        'numero_partida' => $numeroPartida,
                        'partida' => $partida,
                        'unidad' => $unidad,
                        'precio' => $precio_valor,
                        'precio_unitario' => $precio_valor, // Alias para compatibilidad con JavaScript
                        'es_titulo' => $esTitulo, // Indicador de si es título
                    ];
                })
                ->unique(function ($precio) {
                    // Crear clave única normalizada: numero_partida + partida (en minúsculas sin espacios extra)
                    $num = strtolower(trim($precio['numero_partida'] ?? ''));
                    $part = strtolower(trim($precio['partida'] ?? ''));
                    return $num . '|' . $part;
                })
                ->values()
                ->sortBy(function ($precio) {
                    // Ordenar: primero los que tienen numero_partida, luego por numero_partida numéricamente, luego por partida
                    $num = $precio['numero_partida'];
                    if (empty($num)) {
                        return '999999|' . $precio['partida'];
                    }
                    // Intentar convertir a número para ordenamiento numérico
                    $numVal = is_numeric($num) ? (float)$num : 999999;
                    return sprintf('%06.2f|%s', $numVal, $precio['partida']);
                })
                ->values();
            
            return response()->json([
                'success' => true,
                'precios' => $precios
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'precios' => [],
                'error' => 'No se pudieron cargar los precios unitarios: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Guardar archivos adjuntos del contrato
     */
    private function guardarArchivosAdjuntos(Request $request, $contratoId, $contrato = null)
    {
        $archivos = [];
        $directorio = 'contratos/' . $contratoId;

        // Crear directorio si no existe
        if (!Storage::exists($directorio)) {
            Storage::makeDirectory($directorio);
        }

        // Procesar archivo_contrato
        if ($request->hasFile('archivo_contrato')) {
            // Eliminar archivo anterior si existe
            if ($contrato && $contrato->archivo_contrato) {
                Storage::delete($contrato->archivo_contrato);
            }
            
            $archivo = $request->file('archivo_contrato');
            $nombreArchivo = 'contrato_' . time() . '_' . $archivo->getClientOriginalName();
            $ruta = $archivo->storeAs($directorio, $nombreArchivo);
            $archivos['archivo_contrato'] = $ruta;
        }

        // Procesar archivo_bases
        if ($request->hasFile('archivo_bases')) {
            // Eliminar archivo anterior si existe
            if ($contrato && $contrato->archivo_bases) {
                Storage::delete($contrato->archivo_bases);
            }
            
            $archivo = $request->file('archivo_bases');
            $nombreArchivo = 'bases_' . time() . '_' . $archivo->getClientOriginalName();
            $ruta = $archivo->storeAs($directorio, $nombreArchivo);
            $archivos['archivo_bases'] = $ruta;
        }

        // Procesar archivo_oferta
        if ($request->hasFile('archivo_oferta')) {
            // Eliminar archivo anterior si existe
            if ($contrato && $contrato->archivo_oferta) {
                Storage::delete($contrato->archivo_oferta);
            }
            
            $archivo = $request->file('archivo_oferta');
            $nombreArchivo = 'oferta_' . time() . '_' . $archivo->getClientOriginalName();
            $ruta = $archivo->storeAs($directorio, $nombreArchivo);
            $archivos['archivo_oferta'] = $ruta;
        }

        return !empty($archivos) ? $archivos : null;
    }

    /**
     * Descargar archivo adjunto del contrato
     */
    public function descargarAdjunto($id, $tipo)
    {
        $contrato = Contrato::findOrFail($id);
        
        $campoArchivo = 'archivo_' . $tipo; // archivo_contrato, archivo_bases, archivo_oferta
        
        if (!in_array($tipo, ['contrato', 'bases', 'oferta'])) {
            abort(404);
        }
        
        if (!$contrato->$campoArchivo || !Storage::exists($contrato->$campoArchivo)) {
            abort(404, 'Archivo no encontrado');
        }
        
        return Storage::download($contrato->$campoArchivo);
    }
}


