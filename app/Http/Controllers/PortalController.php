<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Services\CompanyContext;

class PortalController extends Controller
{
    public function index(CompanyContext $companyContext)
    {
        $user = auth()->user();
        $company = $companyContext->getCompany();

        if (! $user || $user->role !== 'customer' || ! $company || $company->status !== 'active'
            || ! $user->company_id || (int) $user->company_id !== (int) $company->id) {
            abort(403, 'No tienes acceso al portal de esta empresa.');
        }

        $companyId = $company->id;

        $customer = Customer::where('customers.id', $user->customer_id)
            ->where('customers.company_id', $companyId)
            ->first();

        if (! $customer) {
            abort(403, 'No tienes un perfil de cliente asociado.');
        }

        $activeLoans = Loan::where('loans.customer_id', $customer->id)
                           ->where('loans.company_id', $companyId)
                           ->whereIn('loans.status', ['active', 'overdue'])
                           ->with([
                               'payments' => fn ($query) => $query
                                   ->where('payments.company_id', $companyId),
                           ])
                           ->latest('loans.created_at')
                           ->get();

        $paidLoans = Loan::where('loans.customer_id', $customer->id)
                         ->where('loans.company_id', $companyId)
                         ->where('loans.status', 'paid')
                         ->with([
                             'payments' => fn ($query) => $query
                                 ->where('payments.company_id', $companyId),
                         ])
                         ->latest('loans.created_at')
                         ->get();

        return view('portal.index', compact('customer', 'activeLoans', 'paidLoans'));
    }

    public function show(Loan $loan, CompanyContext $companyContext)
    {
        $user = auth()->user();
        $company = $companyContext->getCompany();

        if (! $user || $user->role !== 'customer' || ! $company || $company->status !== 'active'
            || ! $user->company_id || (int) $user->company_id !== (int) $company->id) {
            abort(403, 'No tienes acceso al portal de esta empresa.');
        }

        $companyId = $company->id;

        $customer = Customer::where('customers.id', $user->customer_id)
            ->where('customers.company_id', $companyId)
            ->first();

        if (! $customer) {
            abort(403, 'No tienes un perfil de cliente asociado.');
        }

        $loan = Loan::where('loans.id', $loan->getKey())
            ->where('loans.customer_id', $customer->id)
            ->where('loans.company_id', $companyId)
            ->with([
                'payments' => fn ($query) => $query
                    ->where('payments.company_id', $companyId),
            ])
            ->firstOrFail();

        return view('portal.show', compact('customer', 'loan'));
    }
}
