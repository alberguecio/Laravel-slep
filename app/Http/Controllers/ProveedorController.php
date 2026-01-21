<?php

namespace App\Http\Controllers;

use App\Models\Oferente;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Mostrar listado de proveedores
     */
    public function index()
    {
        $proveedores = Oferente::orderBy('nombre')->get();
        return view('configuracion.proveedores.index', compact('proveedores'));
    }

    /**
     * Normalizar formato de RUT (agregar guion si no lo tiene)
     */
    private function normalizarRUT($rut)
    {
        if (empty($rut)) {
            return null;
        }

        // Remover espacios y convertir a mayúsculas
        $rut = strtoupper(trim($rut));

        // Si ya tiene guion, retornar tal cual
        if (strpos($rut, '-') !== false) {
            return $rut;
        }

        // Remover todo excepto números y K
        $rutLimpio = preg_replace('/[^0-9K]/', '', $rut);

        if (strlen($rutLimpio) < 2) {
            return $rut; // RUT muy corto, retornar original
        }

        // Separar dígito verificador y números
        $dv = substr($rutLimpio, -1);
        $numeros = substr($rutLimpio, 0, -1);

        // Formatear números con puntos
        $numerosFormateados = number_format((int)$numeros, 0, '', '.');

        // Retornar con guion
        return $numerosFormateados . '-' . $dv;
    }

    /**
     * Crear un nuevo proveedor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'rut' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255'
        ]);

        // Normalizar RUT antes de guardar
        if (!empty($validated['rut'])) {
            $validated['rut'] = $this->normalizarRUT($validated['rut']);
        }

        Oferente::create($validated);

        return redirect()->route('configuracion.index', ['tab' => 'proveedores'])
            ->with('success', 'Proveedor creado exitosamente');
    }

    /**
     * Actualizar un proveedor
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'rut' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255'
        ]);

        // Normalizar RUT antes de guardar
        if (!empty($validated['rut'])) {
            $validated['rut'] = $this->normalizarRUT($validated['rut']);
        }

        $proveedor = Oferente::findOrFail($id);
        $proveedor->update($validated);

        return redirect()->route('configuracion.index', ['tab' => 'proveedores'])
            ->with('success', 'Proveedor actualizado exitosamente');
    }

    /**
     * Eliminar un proveedor
     */
    public function destroy($id)
    {
        $proveedor = Oferente::findOrFail($id);
        $proveedor->delete();

        return redirect()->route('configuracion.index', ['tab' => 'proveedores'])
            ->with('success', 'Proveedor eliminado exitosamente');
    }
}

