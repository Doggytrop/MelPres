<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return $this->dashboardAdmin();
        }

        return $this->dashboardAdvisor();
    }

    private function dashboardAdmin()
    {
        $hoy = Carbon::today();

        $totalCapital = Loan::whereIn('status', ['active', 'overdue'])
                            ->sum('remaining_balance');

        $totalcustomers = Customer::where('status', 'active')->count();

        $activeLoansCount = Loan::where('status', 'active')->count();

        $loansoverdues = Loan::where('status', 'overdue')->count();

        $paymentsHoy = Payment::whereDate('payment_date', $hoy)
                        ->with(['loan.customer', 'recordedBy'])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $paymentsHoy->sum('amount_paid');

        $interestDelMes = Payment::whereMonth('payment_date', $hoy->month)
                             ->whereYear('payment_date', $hoy->year)
                             ->sum('interest_payment');

        $moraDelMes = Payment::whereMonth('payment_date', $hoy->month)
                          ->whereYear('payment_date', $hoy->year)
                          ->sum('penalty_payment');

        $overdues = Loan::where('status', 'overdue')
                        ->with('customer')
                        ->latest()
                        ->take(10)
                        ->get();

        $proximosVencimientos = Loan::where('status', 'active')
                                    ->whereBetween('next_payment_date', [
                                        $hoy->copy()->toDateString(),
                                        $hoy->copy()->addDays(7)->toDateString(),
                                    ])
                                    ->with('customer')
                                    ->orderBy('next_payment_date')
                                    ->take(10)
                                    ->get();

        // — Datos para la gráfica de pagos por mes (últimos 6 meses) —
        $chartLabels = [];
        $chartData   = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $hoy->copy()->subMonths($i);
            $chartLabels[] = $month->translatedFormat('M Y');
            $chartData[]   = Payment::whereMonth('payment_date', $month->month)
                                    ->whereYear('payment_date', $month->year)
                                    ->sum('amount_paid');
        }

        return view('dashboard.admin', compact(
            'totalCapital',
            'totalcustomers',
            'activeLoansCount',
            'loansoverdues',
            'paymentsHoy',
            'totalCobradoHoy',
            'interestDelMes',
            'moraDelMes',
            'overdues',
            'proximosVencimientos',
            'chartLabels',
            'chartData',
        ));
    }

    private function dashboardAdvisor()
    {
        $hoy = Carbon::today();

        $paymentsHoy = Payment::whereDate('payment_date', $hoy)
                        ->where('recorded_by', auth()->id())
                        ->with(['loan.customer'])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $paymentsHoy->sum('amount_paid');

        $vencenHoy = Loan::where('status', 'active')
                         ->whereDate('next_payment_date', $hoy->toDateString())
                         ->with('customer')
                         ->get();

        $overdues = Loan::where('status', 'overdue')
                        ->with('customer')
                        ->latest()
                        ->take(5)
                        ->get();

        return view('dashboard.advisor', compact(
            'paymentsHoy',
            'totalCobradoHoy',
            'vencenHoy',
            'overdues',
        ));
    }
}