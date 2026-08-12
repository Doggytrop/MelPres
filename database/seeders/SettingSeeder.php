<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Support\DefaultCompanySettings;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(DefaultCompanySettings $settings): void
    {
        Company::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->each(fn (Company $company) => $settings->initialize($company->id));
    }
}
