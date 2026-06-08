<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CollectorController extends Controller
{
    public function index()
    {
        $today     = Carbon::today();
        $collector = auth()->user();

        $frequencies = $collector->collector_frequencies
            ?? ['daily', 'weekly', 'biweekly', 'monthly'];

        $overdueDays = $collector->collector_overdue_days ?? 15;

        $todayLoans = Loan::with('customer')
            ->whereIn('status', ['active', 'overdue'])
            ->whereIn('payment_frequency', $frequencies)
            ->whereDate('next_payment_date', $today)
            ->whereDoesntHave('payments', function ($q) use ($today) {
                $q->whereDate('payment_date', $today);
            })
            ->get();

        $overdueLoans = collect();
        if ($overdueDays > 0) {
            $limitDate = $today->copy()->subDays($overdueDays);
            $overdueLoans = Loan::with('customer')
                ->whereIn('status', ['active', 'overdue'])
                ->whereIn('payment_frequency', $frequencies)
                ->whereDate('next_payment_date', '<', $today)
                ->whereDate('next_payment_date', '>=', $limitDate)
                ->whereDoesntHave('payments', function ($q) use ($today) {
                    $q->whereDate('payment_date', $today);
                })
                ->get();
        }

        $collectedToday = Payment::where('recorded_by', auth()->id())
            ->whereDate('payment_date', $today)
            ->with('loan.customer')
            ->latest()
            ->get();

        $allLoans = $todayLoans->merge($overdueLoans)->unique('id');

        $mapLoans = $allLoans->filter(function ($loan) {
            return $loan->customer && $loan->customer->latitude && $loan->customer->longitude;
        });

        $totalToday     = $todayLoans->count();
        $totalOverdue   = $overdueLoans->count();
        $totalPending   = $allLoans->sum('suggested_payment');
        $totalCollected = $collectedToday->sum('amount_paid');
        $collectCount   = $collectedToday->count();

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

    public function adminUpdateConfig(Request $request, User $user)
    {
        abort_unless($user->isCollector(), 403);

        $request->validate([
            'collector_frequencies'   => ['nullable', 'array'],
            'collector_frequencies.*' => ['in:daily,weekly,biweekly,monthly'],
            'collector_overdue_days'  => ['required', 'integer', 'min:0', 'max:90'],
        ]);

        $frequencies = $request->collector_frequencies ?? [];

        if (empty($frequencies)) {
            return redirect()->route('settings.index')
                             ->with('error', 'El cobrador debe tener al menos una frecuencia asignada.');
        }

        $user->update([
            'collector_frequencies'  => $frequencies,
            'collector_overdue_days' => $request->collector_overdue_days,
        ]);

        \App\Models\ActivityLog::log('update', 'users', 'Actualizó configuración del cobrador ' . $user->name, $user);

        return redirect()->route('settings.index')
                         ->with('success', 'Configuración de ' . $user->name . ' guardada.');
    }
}