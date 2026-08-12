<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Support\DefaultCompanySettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyProvisioningService
{
    public function __construct(
        private DefaultCompanySettings $settings,
        private SaasAuditService $audit
    ) {}

    public function provision(array $data): Company
    {
        return DB::transaction(function () use ($data): Company {
            $startedAt = now();
            $subscriptionYears = (int) $data['subscription_years'];
            $periodEnd = $startedAt->copy()->addYearsNoOverflow($subscriptionYears);
            $company = Company::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'status' => 'active',
                'email' => null,
                'phone' => null,
                'address' => null,
                'timezone' => config('app.timezone'),
                'currency_code' => 'MXN',
                'currency_symbol' => '$',
            ]);

            CompanySubscription::create([
                'company_id' => $company->id,
                'status' => SubscriptionStatus::ACTIVE,
                'started_at' => $startedAt,
                'current_period_start' => $startedAt,
                'current_period_end' => $periodEnd,
                'grace_until' => null,
                'suspended_at' => null,
                'cancelled_at' => null,
            ]);

            User::create([
                'company_id' => $company->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'phone' => $data['admin_phone'] ?? null,
                'password' => $data['password'],
                'role' => 'admin',
                'customer_id' => null,
            ]);

            $this->settings->initialize($company->id, [
                'company_name' => $company->name,
                'company_phone' => $company->phone,
                'company_email' => $company->email,
                'company_address' => $company->address,
                'advanced_timezone' => $company->timezone,
                'advanced_currency_code' => $company->currency_code,
                'advanced_currency_symbol' => $company->currency_symbol,
            ]);

            $admin = $company->primaryAdmin()->firstOrFail();

            $this->audit->record(
                'company_created',
                $company,
                null,
                [
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'status' => $company->status,
                    ],
                    'subscription' => [
                        'status' => SubscriptionStatus::ACTIVE->value,
                        'contracted_years' => $subscriptionYears,
                        'started_at' => $startedAt->toISOString(),
                        'current_period_start' => $startedAt->toISOString(),
                        'current_period_end' => $periodEnd->toISOString(),
                    ],
                    'primary_admin' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                    ],
                ],
                "Empresa {$company->name} creada y aprovisionada."
            );

            return $company->load(['subscription', 'primaryAdmin']);
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'empresa';
        $slug = $base;
        $suffix = 2;

        while (Company::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
