<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Http\Requests\StorePrestamoRequest;
use Illuminate\Http\Request;

class PrestamoController extends Controller
{
        public function index()
    {
        $prestamos = Prestamo::with('cliente')
                            ->whereIn('estado', ['activo', 'vencido', 'refinanciado'])
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
    // Convertir periodos a meses según frecuencia
            $periodosMeses = match($datos['frecuencia_pago']) {
                'semanal'   => round($datos['numero_periodos'] / 4, 2),
                'quincenal' => round($datos['numero_periodos'] / 2, 2),
                'mensual'   => $datos['numero_periodos'],
            };

            $datos['interes_acumulado'] = round(
                $datos['monto_original'] * ($datos['interes_rate'] / 100) * $periodosMeses, 2
            );
            $datos['saldo_restante'] = $datos['monto_original'] + $datos['interes_acumulado'];
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
    public function buscarCliente(Request $request)
{
    $busqueda = $request->get('q');

    $clientes = Cliente::where('estado', 'activo')
        ->where(function($query) use ($busqueda) {
            $query->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('apellido', 'like', "%{$busqueda}%")
                  ->orWhere('telefono', 'like', "%{$busqueda}%");
        })
        ->with(['prestamosActivos'])
        ->limit(5)
        ->get()
        ->map(function($cliente) {
            return [
                'id'             => $cliente->id,
                'nombre'         => $cliente->nombre_completo,
                'telefono'       => $cliente->telefono ?? '—',
                'prestamos'      => $cliente->prestamosActivos->map(function($p) {
                    return [
                        'id'             => $p->id,
                        'tipo'           => ucfirst($p->tipo),
                        'saldo'          => number_format($p->saldo_restante, 2),
                        'mora'           => number_format($p->mora_acumulada, 2),
                        'url'            => route('prestamos.pagos.store', $p->id),
                    ];
                }),
            ];
        });

    return response()->json($clientes);
}
}