<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LibretaDireccionesController extends Controller
{
    /**
     * Mostrar página de libreta de direcciones
     */
    public function index()
    {
        return view('libreta-direcciones.index');
    }

    /**
     * Buscar establecimientos
     */
    public function buscar(Request $request)
    {
        $termino = $request->get('q', '');
        
        if (empty($termino)) {
            return response()->json([]);
        }
        
        try {
            // Verificar qué columnas existen en la tabla
            $hasDirector = Schema::hasColumn('establecimientos', 'director');
            $hasTelefono = Schema::hasColumn('establecimientos', 'telefono');
            $hasEmail = Schema::hasColumn('establecimientos', 'email');
            $hasMatricula = Schema::hasColumn('establecimientos', 'matricula');
            
            // Construir select dinámicamente
            $selectFields = ['id', 'nombre', 'rbd', 'ruralidad', 'comuna_id'];
            if ($hasMatricula) {
                $selectFields[] = 'matricula';
            }
            if ($hasDirector) {
                $selectFields[] = 'director';
            }
            if ($hasTelefono) {
                $selectFields[] = 'telefono';
            }
            if ($hasEmail) {
                $selectFields[] = 'email';
            }
            
            // Búsqueda en campos que definitivamente existen
            $establecimientos = Establecimiento::with('comuna')
                ->select($selectFields)
                ->where(function($query) use ($termino, $hasDirector, $hasTelefono, $hasEmail) {
                    $query->where('nombre', 'LIKE', '%' . $termino . '%')
                          ->orWhere('rbd', 'LIKE', '%' . $termino . '%')
                          ->orWhereHas('comuna', function($q) use ($termino) {
                              $q->where('nombre', 'LIKE', '%' . $termino . '%');
                          });
                    
                    // Agregar búsqueda en campos opcionales solo si existen
                    if ($hasDirector) {
                        $query->orWhere('director', 'LIKE', '%' . $termino . '%');
                    }
                    if ($hasTelefono) {
                        $query->orWhere('telefono', 'LIKE', '%' . $termino . '%');
                    }
                    if ($hasEmail) {
                        $query->orWhere('email', 'LIKE', '%' . $termino . '%');
                    }
                })
                ->orderBy('nombre')
                ->limit(100)
                ->get()
                ->map(function($est) use ($hasMatricula, $hasDirector, $hasTelefono, $hasEmail) {
                    // Obtener ruralidad: usar de BD o calcular desde el nombre (igual que en EstablecimientoController)
                    $ruralidad = $est->getAttribute('ruralidad');
                    
                    // Si no hay ruralidad en BD, calcularla desde el nombre
                    if (empty($ruralidad) || trim($ruralidad) === '') {
                        $nombre = strtoupper($est->nombre);
                        if (strpos($nombre, 'RURAL') !== false) {
                            // Verificar si es Insular o solo Rural según comuna
                            if ($est->comuna && in_array(strtoupper($est->comuna->nombre), ['QUINCHAO', 'CURACO DE VÉLEZ', 'PUQUELDÓN', 'QUEILÉN'])) {
                                $ruralidad = 'Insular/Rural';
                            } else {
                                $ruralidad = 'Rural';
                            }
                        } else {
                            $ruralidad = 'Urbano';
                        }
                    }
                    
                    return [
                        'id' => $est->id,
                        'nombre' => $est->nombre,
                        'establecimiento' => $est->nombre,
                        'comuna' => $est->comuna ? $est->comuna->nombre : 'N/A',
                        'rbd' => $est->rbd ?? 'N/A',
                        'matricula' => $hasMatricula ? ($est->matricula ?? null) : null,
                        'ruralidad' => $ruralidad,
                        'director' => $hasDirector ? ($est->director ?? null) : null,
                        'directora' => $hasDirector ? ($est->director ?? null) : null,
                        'telefono' => $hasTelefono ? ($est->telefono ?? null) : null,
                        'email' => $hasEmail ? ($est->email ?? null) : null,
                    ];
                });
            
            Log::info('Búsqueda libreta direcciones - Término: ' . $termino . ', Resultados: ' . $establecimientos->count());
            
            return response()->json($establecimientos);
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de libreta de direcciones: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error al realizar la búsqueda: ' . $e->getMessage()], 500);
        }
    }
}
