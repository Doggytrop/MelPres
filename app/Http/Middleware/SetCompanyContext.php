<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;

class SetCompanyContext
{
    public function __construct(private CompanyContext $companyContext) {}

    public function handle(Request $request, Closure $next)
    {
        $this->companyContext->clear();

        $user = $request->user();

        if ($user?->company_id) {
            $company = Company::find($user->company_id);

            if ($company && $company->status === 'active') {
                $this->companyContext->setCompany($company);
            }
        }

        try {
            return $next($request);
        } finally {
            $this->companyContext->clear();
        }
    }
}
