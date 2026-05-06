<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CollectorController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $fifteenDaysAgo = $today->copy()->subDays(15);

        // Cobros de hoy (que NO tienen pago registrado hoy)
        $todayLoans = Loan::with('customer')
            ->whereIn('status', ['active', 'overdue'])
            ->whereDate('next_payment_date', $today)
            ->whereDoesntHave('payments', function ($q) use ($today) {
                $q->whereDate('payment_date', $today);
            })
            ->get();

        // Atrasados (menos de 15 días, sin pago hoy)
        $overdueLoans = Loan::with('customer')
            ->whereIn('status', ['active', 'overdue'])
            ->whereDate('next_payment_date', '<', $today)
            ->whereDate('next_payment_date', '>=', $fifteenDaysAgo)
            ->whereDoesntHave('payments', function ($q) use ($today) {
                $q->whereDate('payment_date', $today);
            })
            ->get();

        // Cobrados hoy por este cobrador
        $collectedToday = Payment::where('recorded_by', auth()->id())
            ->whereDate('payment_date', $today)
            ->with('loan.customer')
            ->latest()
            ->get();

        // Todos para el mapa
        $allLoans = $todayLoans->merge($overdueLoans)->unique('id');

        $mapLoans = $allLoans->filter(function ($loan) {
            return $loan->customer && $loan->customer->latitude && $loan->customer->longitude;
        });

        // Estadísticas
        $totalToday = $todayLoans->count();
        $totalOverdue = $overdueLoans->count();
        $totalPending = $allLoans->sum('suggested_payment');
        $totalCollected = $collectedToday->sum('amount_paid');
        $collectCount = $collectedToday->count();

        return view('collector.index', compact(
            'todayLoans', 'overdueLoans', 'allLoans', 'mapLoans',
            'collectedToday', 'totalToday', 'totalOverdue',
            'totalPending', 'totalCollected', 'collectCount'
        ));
    }

    public function collect(Request $request, Loan $loan)
    {
        $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'notes'       => ['nullable', 'string'],
        ]);

        $paymentService = app(PaymentService::class);

        $paymentService->applyPayment($loan, [
            'amount_paid'  => $request->amount_paid,
            'payment_date' => Carbon::today()->toDateString(),
            'notes'        => $request->notes ?? 'Cobro en campo',
        ]);

        \App\Models\ActivityLog::log('payment', 'payments', 'Cobró $' . number_format($request->amount_paid, 2) . ' del préstamo #' . $loan->id . ' en campo', $loan);

        return back()->with('success', 'Cobro de $' . number_format($request->amount_paid, 2) . ' a ' . $loan->customer->full_name . ' registrado.');
    }
}