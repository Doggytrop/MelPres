<?php

namespace App\Http\Middleware;

use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;

class RequireCompanyContext
{
    public function __construct(private CompanyContext $companyContext) {}

    public function handle(Request $request, Closure $next)
    {
        $company = $this->companyContext->getCompany();

        if (! $company || $company->status !== 'active') {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        return $next($request);
    }
}
