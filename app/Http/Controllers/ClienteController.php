<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Services\ScoreService;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::latest()->paginate(15);

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        $cliente = new Cliente();
        return view('clientes.create', compact('cliente'));
    }

    public function store(StoreClienteRequest $request)
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')
                         ->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente)
{
    $cliente->load('prestamosActivos');
    $scoreService = app(ScoreService::class);
    $scoreData    = $scoreService->etiqueta($cliente->score ?? 100);

    return view('clientes.show', compact('cliente', 'scoreData'));
}

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')
                        ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->prestamos()->exists()) {
            return redirect()->route('clientes.index')
                            ->with('error', 'No se puede eliminar un cliente con préstamos registrados.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')
                        ->with('success', 'Cliente eliminado correctamente.');
    }
}