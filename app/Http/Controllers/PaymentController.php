<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\PaymentService;
use App\Http\Requests\StorePaymentRequest;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function store(StorePaymentRequest $request, Loan $loan)
    {
        if ($loan->status === 'paid') {
            return back()->with('error', 'Este préstamo ya está pagado.');
        }

        $paymentData = $request->validated();
        // This route is the administrative payment workflow. Its implicit
        // authorization may cover consecutive contractual periods.
        $paymentData['authorize_future_periods'] = true;

        $this->paymentService->applyPayment($loan, $paymentData);

        return redirect()->route('loans.show', $loan)
                         ->with('success', 'Pago registrado correctamente.');
    }
}
