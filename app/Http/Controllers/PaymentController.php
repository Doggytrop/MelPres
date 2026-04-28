<?php

namespace App\Http\Controllers;

use App\Models\loan;
use App\Services\paymentservice;
use App\Http\Requests\StorepaymentRequest;

class PaymentController extends Controller
{
    public function __construct(protected paymentservice $paymentservice) {}

    public function store(StorepaymentRequest $request, loan $loan)
    {
        if ($loan->status === 'paid') {
            return back()->with('error', 'Este préstamo ya está paid.');
        }

        $this->paymentservice->aplicarpayment($loan, $request->validated());

        return redirect()->route('loans.show', $loan)
                         ->with('success', 'payment registrado correctamente.');
    }
}