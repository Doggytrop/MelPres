<?php

namespace App\Http\Controllers;

use App\Models\customer;
use App\Http\Requests\StorecustomerRequest;
use App\Http\Requests\UpdatecustomerRequest;
use App\Services\ScoreService;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = customer::latest()->paginate(15);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $customer = new customer();
        return view('customers.create', compact('customer'));
       
    }
public function store(StoreCustomerRequest $request)
{
    $customer = Customer::create($request->validated());
    \App\Models\ActivityLog::log('create', 'customers', 'Creó cliente ' . $customer->full_name, $customer);

    
    $generatedPassword = null;

    if ($customer->phone && !\App\Models\User::where('phone', $customer->phone)->exists()) {
        $plainPassword = strtoupper(substr(str_replace(' ', '', $customer->first_name), 0, 3))
                       . rand(1000, 9999);

        $user = \App\Models\User::create([
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

    public function show(customer $customer)
{
    $customer->load('activeLoans');
    $scoreService = app(ScoreService::class);
    $scoreData    = $scoreService->etiqueta($customer->score ?? 100);

    return view('customers.show', compact('customer', 'scoreData'));
}

    public function edit(customer $customer)
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

    public function destroy(customer $customer)
    {
        \App\Models\ActivityLog::log('delete', 'customers', 'Eliminó al cliente ' . $customer->full_name, $customer);

        if ($customer->loans()->exists()) {
            return redirect()->route('customers.index')
                            ->with('error', 'No se puede eliminar un customer con préstamos registrados.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
                        ->with('success', 'customer eliminado correctamente.');
    }
}