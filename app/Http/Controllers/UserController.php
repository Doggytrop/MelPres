<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(CompanyContext $companyContext)
    {
        $companyId = $this->activeCompanyId($companyContext);

        $usersQuery = User::where('company_id', $companyId)
            ->whereIn('role', ['admin', 'advisor', 'collector']);

        $users = $usersQuery->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(CompanyContext $companyContext)
    {
        $this->activeCompanyId($companyContext);

        return view('users.create');
    }

    public function store(Request $request, CompanyContext $companyContext)
    {
        $companyId = $this->activeCompanyId($companyContext);

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

        $user = User::create([
            'company_id' => $companyId,
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => $request->password,
            'role'       => $request->role,
        ]);

        \App\Models\ActivityLog::log(
            'create',
            'users',
            'Creó usuario ' . $user->name . ' con rol ' . $user->role,
            $user
        );

        return redirect()->route('users.index')
                         ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user, CompanyContext $companyContext)
    {
        $companyId = $this->activeCompanyId($companyContext);
        $user = $this->resolveManagedUser($user, $companyId);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user, CompanyContext $companyContext)
    {
        $companyId = $this->activeCompanyId($companyContext);
        $user = $this->resolveManagedUser($user, $companyId);

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

        if ($user->role === 'admin'
            && $request->role !== 'admin'
            && ! User::where('company_id', $companyId)
                ->where('role', 'admin')
                ->where('id', '!=', $user->id)
                ->exists()) {
            return back()
                ->withInput()
                ->with('error', 'La empresa debe conservar al menos un usuario administrador.');
        }

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role  = $request->role;

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->save();

        \App\Models\ActivityLog::log('update', 'users', 'Actualizó al usuario ' . $user->name, $user);

        return redirect()->route('users.index')
                         ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user, CompanyContext $companyContext)
    {
        $companyId = $this->activeCompanyId($companyContext);
        $user = $this->resolveManagedUser($user, $companyId);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        if ($user->role === 'admin'
            && ! User::where('company_id', $companyId)
                ->where('role', 'admin')
                ->where('id', '!=', $user->id)
                ->exists()) {
            return back()->with('error', 'No puedes eliminar al último administrador de la empresa.');
        }

        $user->delete();
        \App\Models\ActivityLog::log('delete', 'users', 'Eliminó al usuario ' . $user->name, $user);

        return redirect()->route('users.index')
                        ->with('success', 'Usuario eliminado correctamente.');
    }

    private function activeCompanyId(CompanyContext $companyContext): int
    {
        $authenticatedUser = auth()->user();

        if ($authenticatedUser?->role !== 'admin') {
            abort(403, 'No tienes permiso para administrar usuarios.');
        }

        $company = $companyContext->getCompany();

        if (! $company
            || $company->status !== 'active'
            || ! $authenticatedUser->company_id
            || (int) $authenticatedUser->company_id !== (int) $company->id) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        return (int) $company->id;
    }

    private function resolveManagedUser(User $user, int $companyId): User
    {
        return User::where('users.id', $user->getKey())
            ->where('users.company_id', $companyId)
            ->whereIn('users.role', ['admin', 'advisor', 'collector'])
            ->firstOrFail();
    }
}
