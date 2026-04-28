<?php

namespace App\Http\Controllers;

use App\Models\payment;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CashRegisterController extends Controller
{
    public function index()
    {
        $fecha = request('fecha', Carbon::today()->toDateString());

        $query = payment::whereDate('payment_date', $fecha)
                     ->with(['loan.customer', 'registradoPor']);

        if (auth()->user()->esadvisor()) {
            $query->where('recorded_by', auth()->id());
        }

        $payments = $query->latest()->get();

        $poradvisor    = $payments->groupBy('recorded_by');
        $totalCobrado = $payments->sum('amount_paid');
        $totalCapital = $payments->sum('capital_payment');
        $totalinterest = $payments->sum('interestt_payment');
        $totalMora    = $payments->sum('penalty_payment');
        $advisores     = User::where('rol', 'advisor')->get();

        return view('corte-caja.index', compact(
            'payments',
            'poradvisor',
            'totalCobrado',
            'totalCapital',
            'totalinterest',
            'totalMora',
            'fecha',
            'advisores',
        ));
    }

    public function pdf()
    {
        $fecha = request('fecha', Carbon::today()->toDateString());

        $query = payment::whereDate('payment_date', $fecha)
                     ->with(['loan.customer', 'registradoPor']);

        if (auth()->user()->esadvisor()) {
            $query->where('recorded_by', auth()->id());
        }

        $payments = $query->latest()->get();

        $poradvisor    = $payments->groupBy('recorded_by');
        $totalCobrado = $payments->sum('amount_paid');
        $totalCapital = $payments->sum('capital_payment');
        $totalinterest = $payments->sum('interestt_payment');
        $totalMora    = $payments->sum('penalty_payment');

        $pdf = Pdf::loadView('corte-caja.pdf', compact(
            'payments',
            'poradvisor',
            'totalCobrado',
            'totalCapital',
            'totalinterest',
            'totalMora',
            'fecha',
        ))->setPaper('a4', 'portrait');

        return $pdf->stream("corte-caja-{$fecha}.pdf");
    }
}