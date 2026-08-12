<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Services\CompanyContext;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CashRegisterController extends Controller
{
    public function index()
    {
        $fecha = request('fecha', Carbon::today()->toDateString());
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $query = Payment::whereDate('payment_date', $fecha)
                     ->where('company_id', $companyId)
                     ->whereHas('loan', fn ($query) => $query
                         ->where('loans.company_id', $companyId)
                         ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId)))
                     ->with([
                         'loan' => fn ($query) => $query->where('loans.company_id', $companyId),
                         'loan.customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                         'recordedBy' => fn ($query) => $query->where('users.company_id', $companyId),
                     ]);

        if (auth()->user()->isadvisor()) {
            $query->where('recorded_by', auth()->id());
        }

        $payments = $query->latest()->get();

        $poradvisor    = $payments->groupBy('recorded_by');
        $totalCobrado = $payments->sum('amount_paid');
        $totalCapital = $payments->sum('capital_payment');
        $totalinterest = $payments->sum('interest_payment');
        $totalMora    = $payments->sum('penalty_payment');
        $advisores     = User::where('role', 'advisor')
                            ->where('company_id', $companyId)
                            ->get();

        return view('cash-register.index', compact(
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
        $company = app(CompanyContext::class)->getCompany();

        if (! $company || $company->status !== 'active') {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $companyId = $company->id;

        $query = Payment::whereDate('payments.payment_date', $fecha)
                     ->where('payments.company_id', $companyId)
                     ->with([
                         'loan' => fn ($query) => $query->where('loans.company_id', $companyId),
                         'loan.customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                         'recordedBy' => fn ($query) => $query->where('users.company_id', $companyId),
                     ]);

        if (auth()->user()->isadvisor()) {
            $query->where('payments.recorded_by', auth()->id());
        }

        $payments = $query->latest('payments.created_at')->get();

        $poradvisor    = $payments->groupBy('recorded_by');
        $totalCobrado = $payments->sum('amount_paid');
        $totalCapital = $payments->sum('capital_payment');
        $totalinterest = $payments->sum('interestt_payment');
        $totalMora    = $payments->sum('penalty_payment');

        $pdf = Pdf::loadView('cash-register.pdf', compact(
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
