<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'rol' => 'required|string|in:admin,supervisor,profesional,usuario',
            'cargo' => 'nullable|string|max:255',
            'estado' => 'required|string|in:activo,inactivo',
        ]);

        $usuario = Usuario::create([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'rol' => $validated['rol'],
            'cargo' => $validated['cargo'] ?? null,
            'estado' => $validated['estado'],
        ]);

        return redirect()->route('configuracion.index', ['tab' => 'usuarios'])->with('success', 'Usuario creado exitosamente');
    }

    public function show($id)
    {
        $usuario = Usuario::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'cargo' => $usuario->cargo,
                'estado' => $usuario->estado,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . $id,
            'password' => 'nullable|string|min:6',
            'rol' => 'required|string|in:admin,supervisor,profesional,usuario',
            'cargo' => 'nullable|string|max:255',
            'estado' => 'required|string|in:activo,inactivo',
        ]);

        $usuario->nombre = $validated['nombre'];
        $usuario->email = $validated['email'];
        $usuario->rol = $validated['rol'];
        $usuario->cargo = $validated['cargo'] ?? null;
        $usuario->estado = $validated['estado'];

        if (!empty($validated['password'])) {
            $usuario->password_hash = Hash::make($validated['password']);
        }

        $usuario->save();

        return redirect()->route('configuracion.index', ['tab' => 'usuarios'])->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return redirect()->route('configuracion.index', ['tab' => 'usuarios'])->with('success', 'Usuario eliminado exitosamente');
    }
}

