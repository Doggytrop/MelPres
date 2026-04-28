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

    public function store(StorecustomerRequest $request)
    {
        customer::create($request->validated());

        return redirect()->route('customers.index')
                         ->with('success', 'customer registrado correctamente.');
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

    public function update(UpdatecustomerRequest $request, customer $customer)
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')
                        ->with('success', 'customer actualizado correctamente.');
    }

    public function destroy(customer $customer)
    {
        if ($customer->loans()->exists()) {
            return redirect()->route('customers.index')
                            ->with('error', 'No se puede eliminar un customer con préstamos registrados.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
                        ->with('success', 'customer eliminado correctamente.');
    }
}