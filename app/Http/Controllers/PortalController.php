<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function index()
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            abort(403, 'No tienes un perfil de cliente asociado.');
        }

        $activeLoans = Loan::where('customer_id', $customer->id)
                           ->whereIn('status', ['active', 'overdue'])
                           ->with('payments')
                           ->latest()
                           ->get();

        $paidLoans = Loan::where('customer_id', $customer->id)
                         ->where('status', 'paid')
                         ->with('payments')
                         ->latest()
                         ->get();

        return view('portal.index', compact('customer', 'activeLoans', 'paidLoans'));
    }

    public function show(Loan $loan)
    {
        $customer = auth()->user()->customer;

        if (!$customer || $loan->customer_id !== $customer->id) {
            abort(403, 'No tienes acceso a este préstamo.');
        }

        $loan->load('payments');

        return view('portal.show', compact('customer', 'loan'));
    }
}