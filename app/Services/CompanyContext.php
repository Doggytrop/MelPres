<?php

namespace App\Services;

use App\Models\Company;

class CompanyContext
{
    private ?Company $company = null;

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getCompanyId(): ?int
    {
        return $this->company?->id;
    }

    public function clear(): void
    {
        $this->company = null;
    }
}
