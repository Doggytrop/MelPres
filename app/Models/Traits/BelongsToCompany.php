<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Services\CompanyContext;

trait BelongsToCompany
{
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $query = $query->where($field ?? $this->getRouteKeyName(), $value);

        if ($companyId = app(CompanyContext::class)->getCompanyId()) {
            $query = $query->where($this->qualifyColumn('company_id'), $companyId);
        }

        return $query;
    }

    public function withoutCompanyScope()
    {
        return $this->newQueryWithoutScopes();
    }
}
