<?php

namespace App\Http\Controllers;

use App\Models\customer;
use App\Models\loan;
use App\Models\payment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return $this->dashboardAdmin();
        }

        return $this->dashboardadvisor();
    }

    private function dashboardAdmin()
    {
        $hoy = Carbon::today();

        $totalCapital = loan::whereIn('status', ['active', 'overdue'])
                                ->sum('remaining_balance');

        $totalcustomers = customer::where('status', 'active')->count();

        $activeLoansCount = loan::where('status', 'active')->count();

        $loansoverdues = loan::where('status', 'overdue')->count();

        $paymentsHoy = payment::whereDate('payment_date', $hoy)
                        ->with(['loan.customer', 'registradoPor'])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $paymentsHoy->sum('amount_paid');

        $interestDelMes = payment::whereMonth('payment_date', $hoy->month)
                             ->whereYear('payment_date', $hoy->year)
                             ->sum('interest_payment');

        $moraDelMes = payment::whereMonth('payment_date', $hoy->month)
                          ->whereYear('payment_date', $hoy->year)
                          ->sum('penalty_payment');

        $overdues = loan::where('status', 'overdue')
                            ->with('customer')
                            ->latest()
                            ->take(10)
                            ->get();

        $proximosVencimientos = loan::where('status', 'active')
                                        ->whereBetween('next_payment_date', [
                                            $hoy->copy()->toDateString(),
                                            $hoy->copy()->addDays(7)->toDateString(),
                                        ])
                                        ->with('customer')
                                        ->orderBy('next_payment_date')
                                        ->take(10)
                                        ->get();

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
        ));
    }

    private function dashboardadvisor()
    {
        $hoy = Carbon::today();

        $paymentsHoy = payment::whereDate('payment_date', $hoy)
                        ->where('recorded_by', auth()->id())
                        ->with(['loan.customer'])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $paymentsHoy->sum('amount_paid');

        $vencenHoy = loan::where('status', 'active')
                             ->whereDate('next_payment_date', $hoy->toDateString())
                             ->with('customer')
                             ->get();

        $overdues = loan::where('status', 'overdue')
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