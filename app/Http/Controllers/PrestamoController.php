<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Http\Requests\StorePrestamoRequest;

class PrestamoController extends Controller
{
    public function index()
    {
        $prestamos = Prestamo::with('cliente')
                             ->latest()
                             ->paginate(15);

        return view('prestamos.index', compact('prestamos'));
    }

    public function create()
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $prestamo = new Prestamo();

        return view('prestamos.create', compact('clientes', 'prestamo'));
    }

    public function store(StorePrestamoRequest $request)
    {
        $datos = $request->validated();

        // Calcular saldo inicial e interés acumulado
        $datos['saldo_restante']    = $datos['monto_original'];
        $datos['mora_acumulada']    = 0;

        if ($datos['tipo'] === 'plazo' && isset($datos['numero_periodos'])) {
            $interes_total              = $datos['interes_rate'] * $datos['numero_periodos'];
            $datos['interes_acumulado'] = round($datos['monto_original'] * $interes_total / 100, 2);
            $datos['saldo_restante']    = $datos['monto_original'] + $datos['interes_acumulado'];
        }

        // Calcular fecha próximo pago
        $datos['fecha_proximo_pago'] = $this->calcularProximoPago(
            $datos['fecha_inicio'],
            $datos['frecuencia_pago']
        );

        Prestamo::create($datos);

        return redirect()->route('prestamos.index')
                         ->with('success', 'Préstamo registrado correctamente.');
    }

    public function show(Prestamo $prestamo)
    {
        $prestamo->load(['cliente', 'pagos']);

        return view('prestamos.show', compact('prestamo'));
    }

    public function edit(Prestamo $prestamo)
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();

        return view('prestamos.edit', compact('prestamo', 'clientes'));
    }

    public function update(StorePrestamoRequest $request, Prestamo $prestamo)
    {
        $prestamo->update($request->validated());

        return redirect()->route('prestamos.show', $prestamo)
                         ->with('success', 'Préstamo actualizado correctamente.');
    }

    public function destroy(Prestamo $prestamo)
    {
        $prestamo->delete();

        return redirect()->route('prestamos.index')
                         ->with('success', 'Préstamo eliminado correctamente.');
    }

    private function calcularProximoPago(string $fecha, string $frecuencia): string
    {
        $date = \Carbon\Carbon::parse($fecha);

        return match($frecuencia) {
            'semanal'   => $date->addWeek()->toDateString(),
            'quincenal' => $date->addDays(15)->toDateString(),
            'mensual'   => $date->addMonth()->toDateString(),
            default     => $date->addMonth()->toDateString(),
        };
    }
}