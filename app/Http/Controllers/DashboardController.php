<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Services\CompanyContext;
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
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $totalCapital = Loan::whereIn('status', ['active', 'overdue'])
                            ->where('company_id', $companyId)
                            ->sum('remaining_balance');

        $totalcustomers = Customer::where('status', 'active')
                            ->where('company_id', $companyId)
                            ->count();

        $activeLoansCount = Loan::where('status', 'active')
                            ->where('company_id', $companyId)
                            ->count();

        $loansoverdues = Loan::where('status', 'overdue')
                            ->where('company_id', $companyId)
                            ->count();

        $paymentsHoy = Payment::whereDate('payment_date', $hoy)
                        ->where('company_id', $companyId)
                        ->whereHas('loan', fn ($query) => $query
                            ->where('loans.company_id', $companyId)
                            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId)))
                        ->with([
                            'loan' => fn ($query) => $query->where('loans.company_id', $companyId),
                            'loan.customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                            'recordedBy' => fn ($query) => $query->where('users.company_id', $companyId),
                        ])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $paymentsHoy->sum('amount_paid');

        $interestDelMes = Payment::whereMonth('payment_date', $hoy->month)
                             ->where('company_id', $companyId)
                             ->whereYear('payment_date', $hoy->year)
                             ->sum('interest_payment');

        $moraDelMes = Payment::whereMonth('payment_date', $hoy->month)
                           ->where('company_id', $companyId)
                           ->whereYear('payment_date', $hoy->year)
                          ->sum('penalty_payment');

        $overdues = Loan::where('status', 'overdue')
                        ->where('company_id', $companyId)
                        ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
                        ->with([
                            'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                        ])
                        ->latest()
                        ->take(10)
                        ->get();

        $proximosVencimientos = Loan::where('status', 'active')
                                    ->where('company_id', $companyId)
                                    ->whereBetween('next_payment_date', [
                                        $hoy->copy()->toDateString(),
                                        $hoy->copy()->addDays(7)->toDateString(),
                                    ])
                                    ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
                                    ->with([
                                        'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                                    ])
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
                                    ->where('company_id', $companyId)
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
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $paymentsHoy = Payment::whereDate('payment_date', $hoy)
                        ->where('company_id', $companyId)
                        ->where('recorded_by', auth()->id())
                        ->whereHas('loan', fn ($query) => $query
                            ->where('loans.company_id', $companyId)
                            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId)))
                        ->with([
                            'loan' => fn ($query) => $query->where('loans.company_id', $companyId),
                            'loan.customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                        ])
                        ->latest()
                        ->get();

        $totalCobradoHoy = $paymentsHoy->sum('amount_paid');

        $vencenHoy = Loan::where('status', 'active')
                         ->where('company_id', $companyId)
                         ->whereDate('next_payment_date', $hoy->toDateString())
                         ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
                         ->with([
                             'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                         ])
                         ->get();

        $overdues = Loan::where('status', 'overdue')
                        ->where('company_id', $companyId)
                        ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
                        ->with([
                            'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                        ])
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
