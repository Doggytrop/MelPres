<?php

namespace App\Http\Middleware;

use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAccess
{
    public function __construct(private CompanyContext $companyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->companyContext->getCompany();

        if (! $company) {
            return $next($request);
        }

        $subscription = $company->subscription()->first();

        if (! $subscription?->allowsAccess()) {
            return response()->view('subscription.suspended', status: 403);
        }

        return $next($request);
    }
}
