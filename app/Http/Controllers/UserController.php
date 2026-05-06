<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', '!=', 'customer')
                     ->latest()
                     ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role'     => ['required', 'in:admin,advisor,collector'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado.',
            'phone.unique'       => 'Este teléfono ya está registrado.',
            'role.required'      => 'El rol es obligatorio.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => $request->password,
            'role'     => $request->role,
        ]);

        return redirect()->route('users.index')
                         ->with('success', 'Usuario creado correctamente.');
    \App\Models\ActivityLog::log('create', 'users', 'Creó usuario ' . $request->name . ' con rol ' . $request->role);
    }

    public function edit(User $user)
    {
        if ($user->isSuperAdmin()) abort(403);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) abort(403);

        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'role' => ['required', 'in:admin,advisor,collector'], 
                       'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado.',
            'phone.unique'       => 'Este teléfono ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role  = $request->role;

        if ($request->filled('password')) {
            $user->password = $request->password;
        }
        \App\Models\ActivityLog::log('update', 'users', 'Actualizó al usuario ' . $user->name, $user);
        $user->save();

        return redirect()->route('users.index')
                         ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) abort(403);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();
        \App\Models\ActivityLog::log('delete', 'users', 'Eliminó al usuario ' . $user->name, $user);
        return redirect()->route('users.index')
                        ->with('success', 'Usuario eliminado correctamente.');
    }
}