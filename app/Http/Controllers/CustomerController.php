<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CompanyContext;
use App\Services\ScoreService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $customers = Customer::query()
            ->where('customers.company_id', $companyId)
            ->with([
                'profilePhoto' => fn ($query) => $query->where('customer_documents.company_id', $companyId),
            ])
            ->latest()
            ->paginate(15);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $customer = new Customer();
        return view('customers.create', compact('customer'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $company = app(CompanyContext::class)->getCompany();

        if (! $company || $company->status !== 'active') {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        [$customer, $generatedPassword, $accessEmail] = DB::transaction(function () use ($request, $company) {
            $data = $request->validated();
            $data['company_id'] = $company->id;

            $customer = Customer::create($data);

            if (! $customer->phone) {
                throw ValidationException::withMessages([
                    'phone' => 'El teléfono es obligatorio para crear el acceso del cliente.',
                ]);
            }

            $accessEmail = $customer->phone . '@melpres.app';

            if (User::where('phone', $customer->phone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'Este teléfono ya está asociado a un usuario del sistema.',
                ]);
            }

            if (User::where('email', $accessEmail)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'El correo de acceso generado para este teléfono ya está registrado.',
                ]);
            }

            $plainPassword = strtoupper(substr(str_replace(' ', '', $customer->first_name), 0, 3))
                           . rand(100, 999);

            $user = User::create([
                'company_id' => $customer->company_id,
                'name'        => $customer->full_name,
                'email'       => $accessEmail,
                'phone'       => $customer->phone,
                'password'    => $plainPassword,
                'role'        => 'customer',
                'customer_id' => $customer->id,
            ]);

            if (! $user->exists) {
                throw ValidationException::withMessages([
                    'phone' => 'No fue posible crear el acceso del cliente.',
                ]);
            }

            \App\Models\ActivityLog::log(
                'create',
                'customers',
                'Creó cliente ' . $customer->full_name,
                $customer
            );

            return [$customer, $plainPassword, $accessEmail];
        });

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Cliente registrado correctamente.')
                         ->with('credentials', [
                             'email'    => $accessEmail,
                             'phone'    => $customer->phone,
                             'password' => $generatedPassword,
                         ]);
    }

    public function show(Customer $customer)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $customer = Customer::where('customers.id', $customer->getKey())
            ->where('customers.company_id', $companyId)
            ->with([
                'activeLoans' => fn ($query) => $query->where('loans.company_id', $companyId),
                'activeLoans.payments' => fn ($query) => $query->where('payments.company_id', $companyId),
                'loans' => fn ($query) => $query->where('loans.company_id', $companyId),
                'loans.payments' => fn ($query) => $query->where('payments.company_id', $companyId),
                'user' => fn ($query) => $query->where('users.company_id', $companyId),
                'documents' => fn ($query) => $query->where('customer_documents.company_id', $companyId),
                'profilePhoto' => fn ($query) => $query->where('customer_documents.company_id', $companyId),
            ])
            ->firstOrFail();

        $scoreService = app(ScoreService::class);
        $scoreData    = $scoreService->etiqueta($customer->score ?? 100);
        return view('customers.show', compact('customer', 'scoreData'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());
        \App\Models\ActivityLog::log('update', 'customers', 'Actualizó al cliente ' . $customer->full_name, $customer);

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer)
    {
        \App\Models\ActivityLog::log('delete', 'customers', 'Eliminó al cliente ' . $customer->full_name, $customer);

        if ($customer->loans()->exists()) {
            return redirect()->route('customers.index')
                             ->with('error', 'No se puede eliminar un cliente con préstamos registrados.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
                         ->with('success', 'Cliente eliminado correctamente.');
    }

    public function resetPassword(Customer $customer)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        abort_if((int) $customer->company_id !== (int) $companyId, 404);

        $user = User::where('customer_id', $customer->getKey())
            ->where('company_id', $companyId)
            ->firstOrFail();

        $plainPassword = strtoupper(substr(str_replace(' ', '', $customer->first_name), 0, 3))
                       . rand(100, 999);

        $user->update(['password' => $plainPassword]);

        \App\Models\ActivityLog::log('update', 'customers', 'Reseteó contraseña de acceso del cliente ' . $customer->full_name, $customer);

        return back()
            ->with('success', 'Contraseña reseteada correctamente.')
            ->with('credentials', [
                'phone'    => $customer->phone,
                'password' => $plainPassword,
            ]);
    }
}
