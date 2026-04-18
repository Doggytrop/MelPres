<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use Barryvdh\DomPDF\Facade\Pdf;
class HistorialController extends Controller
{
    public function index()
    {
        $prestamos = Prestamo::with('cliente')
                             ->where('estado', 'pagado')
                             ->latest('updated_at')
                             ->paginate(15);

        return view('historial.index', compact('prestamos'));
    }

    public function show(Prestamo $prestamo)
    {
        // Solo permitir ver préstamos pagados
        if ($prestamo->estado !== 'pagado') {
            return redirect()->route('prestamos.show', $prestamo);
        }

        $prestamo->load(['cliente', 'pagos']);

        return view('historial.show', compact('prestamo'));
    }
    

public function pdf(Prestamo $prestamo)
{
    if ($prestamo->estado !== 'pagado') {
        return redirect()->route('historial.index');
    }

    $prestamo->load(['cliente', 'pagos']);

    $totalPagado  = $prestamo->pagos->sum('monto_pagado');
    $totalInteres = $prestamo->pagos->sum('abono_interes');
    $totalMora    = $prestamo->pagos->sum('abono_mora');
    $totalCapital = $prestamo->pagos->sum('abono_capital');

    $pdf = Pdf::loadView('historial.pdf', compact(
        'prestamo',
        'totalPagado',
        'totalInteres',
        'totalMora',
        'totalCapital'
    ))->setPaper('a4', 'portrait');

    return $pdf->stream("prestamo-{$prestamo->id}.pdf");
}
}