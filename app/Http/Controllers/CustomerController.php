<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\ScoreService;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(15);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $customer = new Customer();
        return view('customers.create', compact('customer'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());
        \App\Models\ActivityLog::log('create', 'customers', 'Creó cliente ' . $customer->full_name, $customer);

        $generatedPassword = null;

        if ($customer->phone && !\App\Models\User::where('phone', $customer->phone)->exists()) {
            $plainPassword = strtoupper(substr(str_replace(' ', '', $customer->first_name), 0, 3))
                           . rand(100, 999);

            \App\Models\User::create([
                'name'        => $customer->full_name,
                'email'       => $customer->phone . '@melpres.app',
                'phone'       => $customer->phone,
                'password'    => $plainPassword,
                'role'        => 'customer',
                'customer_id' => $customer->id,
            ]);

            $generatedPassword = $plainPassword;
        }

        if ($generatedPassword) {
            return redirect()->route('customers.show', $customer)
                             ->with('success', 'Cliente registrado correctamente.')
                             ->with('credentials', [
                                 'phone'    => $customer->phone,
                                 'password' => $generatedPassword,
                             ]);
        }

        return redirect()->route('customers.show', $customer)
                         ->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['activeLoans', 'user']);
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
        $user = $customer->user;

        if (!$user) {
            return back()->with('error', 'Este cliente no tiene acceso al sistema.');
        }

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