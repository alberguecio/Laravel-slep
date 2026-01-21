<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MontoConfiguracion;
use Illuminate\Http\Request;

class PresupuestoController extends Controller
{
    /**
     * Mostrar la vista de gestión de presupuestos
     */
    public function index()
    {
        // Obtener todos los montos de configuración ordenados por importancia
        $montos = MontoConfiguracion::orderBy('id')->get();
        
        // Obtener todos los items con sus montos asociados
        $items = Item::with('montosConfiguracion')->orderBy('nombre')->get();
        
        return view('configuracion.presupuestos.index', compact('montos', 'items'));
    }

    /**
     * Crear un nuevo item
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'montos' => 'nullable|array',
            'montos.*' => 'exists:montos_configuracion,id'
        ]);

        $item = Item::create([
            'nombre' => $validated['nombre']
        ]);

        // Sincronizar montos asociados
        if (isset($validated['montos'])) {
            $item->montosConfiguracion()->sync($validated['montos']);
        }

        return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
            ->with('success', 'Item creado exitosamente');
    }

    /**
     * Actualizar un item existente
     */
    public function updateItem(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'montos' => 'nullable|array',
            'montos.*' => 'exists:montos_configuracion,id'
        ]);

        $item = Item::findOrFail($id);
        
        $item->update([
            'nombre' => $validated['nombre']
        ]);

        // Sincronizar montos asociados
        $item->montosConfiguracion()->sync($validated['montos'] ?? []);

        return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
            ->with('success', 'Item actualizado exitosamente');
    }

    /**
     * Eliminar un item
     */
    public function destroyItem($id)
    {
        $item = Item::findOrFail($id);
        
        // Verificar si hay proyectos asociados
        $proyectosAsociados = $item->proyectos()->count();
        
        if ($proyectosAsociados > 0) {
            return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
                ->with('error', "No se puede eliminar el ítem porque tiene {$proyectosAsociados} proyecto(s) asociado(s)");
        }
        
        $item->montosConfiguracion()->detach();
        $item->delete();

        return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
            ->with('success', 'Item eliminado exitosamente');
    }

    /**
     * Actualizar monto de configuración
     */
    public function updateMonto(Request $request, $id)
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0'
        ]);

        $monto = MontoConfiguracion::findOrFail($id);
        $monto->update(['monto' => $validated['monto']]);

        return response()->json([
            'success' => true,
            'message' => 'Monto actualizado exitosamente',
            'monto' => $monto
        ]);
    }

    /**
     * Crear un nuevo monto de configuración
     */
    public function storeMonto(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'monto' => 'nullable|numeric|min:0'
        ]);

        // Generar código automáticamente desde el nombre
        $codigo = $this->generarCodigoDesdeNombre($validated['nombre']);
        
        // Verificar si el código ya existe, si es así, agregar un número
        $codigoOriginal = $codigo;
        $contador = 1;
        while (MontoConfiguracion::where('codigo', $codigo)->exists()) {
            $codigo = $codigoOriginal . '_' . $contador;
            $contador++;
        }

        // Generar orden automáticamente: siguiente número disponible
        $ultimoOrden = MontoConfiguracion::max('orden') ?? 0;
        $orden = $ultimoOrden + 1;

        $monto = MontoConfiguracion::create([
            'nombre' => $validated['nombre'],
            'codigo' => $codigo,
            'monto' => $validated['monto'] ?? 0,
            'orden' => $orden
        ]);

        return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
            ->with('success', 'Fuente de financiamiento creada exitosamente');
    }

    /**
     * Actualizar un monto de configuración (nombre y código automático)
     */
    public function updateMontoInfo(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150'
        ]);

        $monto = MontoConfiguracion::findOrFail($id);
        
        // Si cambió el nombre, regenerar el código automáticamente
        $codigo = $monto->codigo;
        if ($monto->nombre !== $validated['nombre']) {
            $codigo = $this->generarCodigoDesdeNombre($validated['nombre']);
            
            // Verificar si el código ya existe (excepto el actual)
            $codigoOriginal = $codigo;
            $contador = 1;
            while (MontoConfiguracion::where('codigo', $codigo)->where('id', '!=', $id)->exists()) {
                $codigo = $codigoOriginal . '_' . $contador;
                $contador++;
            }
        }
        
        // El orden no se actualiza, se mantiene automático
        $monto->update([
            'nombre' => $validated['nombre'],
            'codigo' => $codigo
        ]);

        return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
            ->with('success', 'Fuente de financiamiento actualizada exitosamente');
    }

    /**
     * Eliminar un monto de configuración
     */
    public function destroyMonto($id)
    {
        $monto = MontoConfiguracion::findOrFail($id);
        
        // Verificar si hay items asociados
        $itemsAsociados = $monto->items()->count();
        
        if ($itemsAsociados > 0) {
            return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
                ->with('error', "No se puede eliminar porque hay {$itemsAsociados} ítem(s) asociado(s)");
        }

        $monto->delete();

        return redirect()->route('configuracion.index', ['tab' => 'presupuestos'])
            ->with('success', 'Fuente de financiamiento eliminada exitosamente');
    }

    /**
     * Generar código automáticamente desde el nombre
     * Convierte: "Subvención Mantenimiento" -> "subvencion_mantenimiento"
     */
    private function generarCodigoDesdeNombre($nombre)
    {
        // Convertir a minúsculas
        $codigo = mb_strtolower($nombre, 'UTF-8');
        
        // Reemplazar acentos
        $codigo = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $codigo
        );
        
        // Reemplazar espacios y caracteres especiales por guiones bajos
        $codigo = preg_replace('/[^a-z0-9]+/', '_', $codigo);
        
        // Eliminar guiones bajos al inicio y final
        $codigo = trim($codigo, '_');
        
        // Limitar longitud
        return substr($codigo, 0, 50);
    }
}

