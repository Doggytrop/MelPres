<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Collection;

class CompanyAutomationEligibility
{
    public function companies(): Collection
    {
        return Company::query()
            ->with('subscription')
            ->orderBy('id')
            ->get();
    }

    public function allows(Company $company): bool
    {
        return $company->status === 'active'
            && $company->subscription?->allowsAccess() === true;
    }
}
