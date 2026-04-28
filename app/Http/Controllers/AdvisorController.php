<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdvisorController extends Controller
{
    public function index()
    {
        $advisores = User::where('rol', 'advisor')->latest()->paginate(15);
        return view('advisores.index', compact('advisores'));
    }

    public function create()
    {
        return view('advisores.create');
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
            'rol'      => 'advisor',
        ]);

        return redirect()->route('advisores.index')
                         ->with('success', 'advisor registrado correctamente.');
    }

    public function edit(User $advisor)
    {
        // Verificar que sea advisor
        if ($advisor->isAdmin()) abort(403);

        return view('advisores.edit', compact('advisor'));
    }

    public function update(Request $request, User $advisor)
    {
        if ($advisor->isAdmin()) abort(403);

        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email,' . $advisor->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $advisor->name  = $request->name;
        $advisor->email = $request->email;

        if ($request->filled('password')) {
            $advisor->password = Hash::make($request->password);
        }

        $advisor->save();

        return redirect()->route('advisores.index')
                         ->with('success', 'advisor actualizado correctamente.');
    }

    public function destroy(User $advisor)
    {
        if ($advisor->isAdmin()) abort(403);

        $advisor->delete();

        return redirect()->route('advisores.index')
                         ->with('success', 'advisor eliminado correctamente.');
    }
}