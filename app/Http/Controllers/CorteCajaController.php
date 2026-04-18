<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CorteCajaController extends Controller
{
    public function index()
    {
        $fecha = request('fecha', Carbon::today()->toDateString());

        $query = Pago::whereDate('fecha_pago', $fecha)
                     ->with(['prestamo.cliente', 'registradoPor']);

        if (auth()->user()->esAsesor()) {
            $query->where('registrado_por', auth()->id());
        }

        $pagos = $query->latest()->get();

        $porAsesor    = $pagos->groupBy('registrado_por');
        $totalCobrado = $pagos->sum('monto_pagado');
        $totalCapital = $pagos->sum('abono_capital');
        $totalInteres = $pagos->sum('abono_interes');
        $totalMora    = $pagos->sum('abono_mora');
        $asesores     = User::where('rol', 'asesor')->get();

        return view('corte-caja.index', compact(
            'pagos',
            'porAsesor',
            'totalCobrado',
            'totalCapital',
            'totalInteres',
            'totalMora',
            'fecha',
            'asesores',
        ));
    }

    public function pdf()
    {
        $fecha = request('fecha', Carbon::today()->toDateString());

        $query = Pago::whereDate('fecha_pago', $fecha)
                     ->with(['prestamo.cliente', 'registradoPor']);

        if (auth()->user()->esAsesor()) {
            $query->where('registrado_por', auth()->id());
        }

        $pagos = $query->latest()->get();

        $porAsesor    = $pagos->groupBy('registrado_por');
        $totalCobrado = $pagos->sum('monto_pagado');
        $totalCapital = $pagos->sum('abono_capital');
        $totalInteres = $pagos->sum('abono_interes');
        $totalMora    = $pagos->sum('abono_mora');

        $pdf = Pdf::loadView('corte-caja.pdf', compact(
            'pagos',
            'porAsesor',
            'totalCobrado',
            'totalCapital',
            'totalInteres',
            'totalMora',
            'fecha',
        ))->setPaper('a4', 'portrait');

        return $pdf->stream("corte-caja-{$fecha}.pdf");
    }
}