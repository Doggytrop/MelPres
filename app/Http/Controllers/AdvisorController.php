<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdvisorController extends Controller
{
    public function index(CompanyContext $companyContext)
    {
        $companyId = $companyContext->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $advisorsQuery = User::where('role', 'advisor')
            ->where('company_id', $companyId);

        $advisors = $advisorsQuery->latest()->paginate(15);

        return view('advisors.index', compact('advisors'));
    }

    public function create()
    {
        return view('advisors.create');
    }

    public function store(Request $request, CompanyContext $companyContext)
    {
        $companyId = $companyContext->getCompanyId();

        if (!$companyId || $companyContext->getCompany()?->status !== 'active') {
            return back()
                ->withInput()
                ->with('error', 'No hay una empresa activa asociada al usuario autenticado.');
        }

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
            'company_id' => $companyId,
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'      => 'advisor',
        ]);

        return redirect()->route('advisors.index')
                         ->with('success', 'advisor registrado correctamente.');
    }

    public function edit(User $advisor)
    {
        // Verificar que sea advisor
        if ($advisor->isAdmin()) abort(403);

        return view('advisors.edit', compact('advisor'));
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
            $advisor->password = $request->password;
        }

        $advisor->save();

        return redirect()->route('advisors.index')
                         ->with('success', 'advisor actualizado correctamente.');
    }

    public function destroy(User $advisor)
    {
        if ($advisor->isAdmin()) abort(403);

        $advisor->delete();

        return redirect()->route('advisors.index')
                         ->with('success', 'advisor eliminado correctamente.');
    }
}
