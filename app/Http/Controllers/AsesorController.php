<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AsesorController extends Controller
{
    public function index()
    {
        $asesores = User::where('rol', 'asesor')->latest()->paginate(15);
        return view('asesores.index', compact('asesores'));
    }

    public function create()
    {
        return view('asesores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'rol'      => 'asesor',
        ]);

        return redirect()->route('asesores.index')
                         ->with('success', 'Asesor registrado correctamente.');
    }

    public function edit(User $asesor)
    {
        // Verificar que sea asesor
        if ($asesor->esAdmin()) abort(403);

        return view('asesores.edit', compact('asesor'));
    }

    public function update(Request $request, User $asesor)
    {
        if ($asesor->esAdmin()) abort(403);

        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email,' . $asesor->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $asesor->name  = $request->name;
        $asesor->email = $request->email;

        if ($request->filled('password')) {
            $asesor->password = Hash::make($request->password);
        }

        $asesor->save();

        return redirect()->route('asesores.index')
                         ->with('success', 'Asesor actualizado correctamente.');
    }

    public function destroy(User $asesor)
    {
        if ($asesor->esAdmin()) abort(403);

        $asesor->delete();

        return redirect()->route('asesores.index')
                         ->with('success', 'Asesor eliminado correctamente.');
    }
}