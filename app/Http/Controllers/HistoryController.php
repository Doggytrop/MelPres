<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\CompanyContext;
use Barryvdh\DomPDF\Facade\Pdf;

class HistoryController extends Controller
{
    public function index()
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $loans = Loan::with([
                         'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                         'payments' => fn ($query) => $query->where('payments.company_id', $companyId),
                         'payments.recordedBy' => fn ($query) => $query->where('users.company_id', $companyId),
                     ])
                     ->where('loans.company_id', $companyId)
                     ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
                     ->where('loans.status', 'paid')
                     ->latest('loans.updated_at')
                     ->paginate(15);

        return view('history.index', compact('loans'));
    }

    public function show(Loan $loan)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $loan = Loan::where('loans.id', $loan->getKey())
            ->where('loans.company_id', $companyId)
            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
            ->with([
                'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                'payments' => fn ($query) => $query->where('payments.company_id', $companyId),
                'payments.recordedBy' => fn ($query) => $query->where('users.company_id', $companyId),
            ])
            ->firstOrFail();

        if ($loan->status !== 'paid') {
            return redirect()->route('loans.show', $loan);
        }

        return view('history.show', compact('loan'));
    }

    public function pdf(Loan $loan, CompanyContext $companyContext)
    {
        $company = $companyContext->getCompany();

        if (! $company || $company->status !== 'active') {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $companyId = $company->id;

        $loan = Loan::where('loans.id', $loan->getKey())
            ->where('loans.company_id', $companyId)
            ->whereHas('customer', fn ($query) => $query->where('customers.company_id', $companyId))
            ->with([
                'customer' => fn ($query) => $query->where('customers.company_id', $companyId),
                'payments' => fn ($query) => $query->where('payments.company_id', $companyId),
            ])
            ->firstOrFail();

        if ($loan->status !== 'paid') {
            return redirect()->route('history.index');
        }

        $totalpaid     = $loan->payments->sum('amount_paid');
        $totalinterest = $loan->payments->sum('interest_payment');
        $totalMora     = $loan->payments->sum('penalty_payment');
        $totalCapital  = $loan->payments->sum('capital_payment');

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
