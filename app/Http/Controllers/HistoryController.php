<?php

namespace App\Http\Controllers;

use App\Models\loan;
use Barryvdh\DomPDF\Facade\Pdf;
class HistoryController extends Controller
{
    public function index()
    {
        $loans = loan::with('customer')
                             ->where('status', 'paid')
                             ->latest('updated_at')
                             ->paginate(15);

        return view('history.index', compact('loans'));
    }

    public function show(loan $loan)
    {
        // Solo permitir ver préstamos paids
        if ($loan->status !== 'paid') {
            return redirect()->route('loans.show', $loan);
        }

        $loan->load(['customer', 'payments']);

        return view('history.show', compact('loan'));
    }
    

public function pdf(loan $loan)
{
    if ($loan->status !== 'paid') {
        return redirect()->route('history.index');
    }

    $loan->load(['customer', 'payments']);

    $totalpaid  = $loan->payments->sum('amount_paid');
    $totalinterest = $loan->payments->sum('interest_payment');
    $totalMora    = $loan->payments->sum('penalty_payment');
    $totalCapital = $loan->payments->sum('capital_payment');

    $pdf = Pdf::loadView('history.pdf', compact(
        'loan',
        'totalpaid',
        'totalinterest',
        'totalMora',
        'totalCapital'
    ))->setPaper('a4', 'portrait');

    return $pdf->stream("loan-{$loan->id}.pdf");
}
}