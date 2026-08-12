<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\CompanyIndexRequest;
use App\Http\Requests\SuperAdmin\RenewCompanyRequest;
use App\Http\Requests\SuperAdmin\StoreCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyGraceRequest;
use App\Models\Company;
use App\Services\CompanyProvisioningService;
use App\Services\CompanySubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(CompanyIndexRequest $request): View
    {
        $filters = $request->validated();
        $companies = Company::query()
            ->with(['subscription', 'primaryAdmin'])
            ->withCount(['users', 'customers', 'loans'])
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                $filters['subscription_status'] ?? null,
                function (Builder $query, string $status): void {
                    $query->whereHas('subscription', function (Builder $query) use ($status): void {
                        match ($status) {
                            'active' => $query->effectivelyActive(),
                            'past_due' => $query->effectivelyPastDue(),
                            default => $query->where('status', $status),
                        };
                    });
                }
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => Company::query()->count(),
            'active' => Company::query()
                ->whereHas('subscription', fn (Builder $query) => $query->effectivelyActive())
                ->count(),
            'past_due' => Company::query()
                ->whereHas('subscription', fn (Builder $query) => $query->effectivelyPastDue())
                ->count(),
            'suspended' => Company::query()
                ->whereHas('subscription', fn (Builder $query) => $query->where('status', 'suspended'))
                ->count(),
        ];

        return view('superadmin.companies.index', compact('companies', 'summary', 'filters'));
    }

    public function create(): View
    {
        return view('superadmin.companies.create');
    }

    public function store(
        StoreCompanyRequest $request,
        CompanyProvisioningService $provisioning
    ): RedirectResponse {
        $company = $provisioning->provision($request->validated());

        return redirect()
            ->route('superadmin.companies.show', $company)
            ->with('success', 'Empresa creada y aprovisionada correctamente.');
    }

    public function show(Company $company): View
    {
        $company->load(['subscription', 'primaryAdmin'])
            ->loadCount(['users', 'customers', 'loans']);

        return view('superadmin.companies.show', compact('company'));
    }

    public function suspend(
        Company $company,
        CompanySubscriptionService $subscriptions
    ): RedirectResponse {
        $subscriptions->suspend($company);

        return $this->subscriptionRedirect($company, 'Suscripción suspendida.');
    }

    public function reactivate(
        Company $company,
        CompanySubscriptionService $subscriptions
    ): RedirectResponse {
        $subscriptions->reactivate($company);

        return $this->subscriptionRedirect($company, 'Suscripción reactivada.');
    }

    public function cancel(
        Company $company,
        CompanySubscriptionService $subscriptions
    ): RedirectResponse {
        $subscriptions->cancel($company);

        return $this->subscriptionRedirect($company, 'Suscripción cancelada.');
    }

    public function renew(
        RenewCompanyRequest $request,
        Company $company,
        CompanySubscriptionService $subscriptions
    ): RedirectResponse {
        $subscriptions->renew($company, (int) $request->validated('subscription_years'));

        return $this->subscriptionRedirect($company, 'Suscripción renovada.');
    }

    public function updateGrace(
        UpdateCompanyGraceRequest $request,
        Company $company,
        CompanySubscriptionService $subscriptions
    ): RedirectResponse {
        $subscriptions->updateGrace($company, $request->validated('grace_until'));

        return $this->subscriptionRedirect($company, 'Periodo de gracia actualizado.');
    }

    public function removeGrace(
        Company $company,
        CompanySubscriptionService $subscriptions
    ): RedirectResponse {
        $subscriptions->removeGrace($company);

        return $this->subscriptionRedirect($company, 'Periodo de gracia eliminado.');
    }

    private function subscriptionRedirect(Company $company, string $message): RedirectResponse
    {
        return redirect()
            ->route('superadmin.companies.show', $company)
            ->with('success', $message);
    }
}
