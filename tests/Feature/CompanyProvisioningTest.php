<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Support\DefaultCompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CompanyProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_only_shows_the_minimum_fields(): void
    {
        $response = $this->actingAs($this->superadmin())
            ->get(route('superadmin.companies.create'));

        $response->assertOk();

        foreach (['name', 'admin_name', 'admin_email', 'admin_phone', 'password', 'password_confirmation', 'subscription_years'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }

        foreach (['slug', 'email', 'phone', 'address', 'timezone', 'currency_code', 'currency_symbol', 'started_at', 'current_period_start', 'current_period_end', 'grace_until'] as $field) {
            $response->assertDontSee('name="'.$field.'"', false);
        }
    }

    public function test_minimum_creation_provisions_secure_defaults_subscription_admin_and_all_settings(): void
    {
        $now = Carbon::parse('2026-07-31 10:00:00', config('app.timezone'));
        Carbon::setTestNow($now);

        try {
            $data = $this->companyData([
                'admin_phone' => null,
                'slug' => 'slug-forzado',
                'status' => 'inactive',
                'company_id' => 999,
                'role' => 'superadmin',
                'email' => 'forzado@empresa.test',
                'phone' => '0000000000',
                'address' => 'Direccion forzada',
                'timezone' => 'UTC',
                'currency_code' => 'USD',
                'currency_symbol' => 'US$',
                'started_at' => '2030-01-01 00:00:00',
                'current_period_start' => '2030-01-01 00:00:00',
                'current_period_end' => '2035-01-01 00:00:00',
                'grace_until' => '2030-02-05 00:00:00',
                'suspended_at' => '2030-02-06 00:00:00',
                'subscription' => ['status' => 'cancelled'],
            ]);

            $this->actingAs($this->superadmin())
                ->post(route('superadmin.companies.store'), $data)
                ->assertRedirect();

            $company = Company::where('slug', 'empresa-nueva')->firstOrFail();
            $subscription = $company->subscription()->firstOrFail();
            $admin = User::where('email', $data['admin_email'])->firstOrFail();

            $this->assertSame('active', $company->status);
            $this->assertNull($company->email);
            $this->assertNull($company->phone);
            $this->assertNull($company->address);
            $this->assertSame(config('app.timezone'), $company->timezone);
            $this->assertSame('MXN', $company->currency_code);
            $this->assertSame('$', $company->currency_symbol);
            $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
            $this->assertTrue($subscription->started_at->equalTo($now));
            $this->assertTrue($subscription->current_period_start->equalTo($now));
            $this->assertTrue($subscription->current_period_end->equalTo(
                $now->copy()->addYearNoOverflow()
            ));
            $this->assertNull($subscription->grace_until);
            $this->assertNull($subscription->suspended_at);
            $this->assertSame($company->id, $admin->company_id);
            $this->assertSame('admin', $admin->role);
            $this->assertNull($admin->phone);
            $this->assertSame(
                count(app(DefaultCompanySettings::class)->catalog()),
                Setting::where('company_id', $company->id)->count()
            );
            $this->assertDatabaseHas('settings', [
                'company_id' => $company->id,
                'key' => 'company_name',
                'value' => $company->name,
            ]);

            $renewalDisplay = $subscription->current_period_end->format('d/m/Y');
            $this->get(route('superadmin.companies.index'))
                ->assertOk()
                ->assertSee('Próxima renovación')
                ->assertSee($renewalDisplay);
            $this->get(route('superadmin.companies.show', $company))
                ->assertOk()
                ->assertSee('Próxima renovación')
                ->assertSee($renewalDisplay);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_creation_rejects_an_unsupported_service_term(): void
    {
        Carbon::setTestNow('2026-08-03 10:00:00');

        try {
            $this->actingAs($this->superadmin())
                ->from(route('superadmin.companies.create'))
                ->post(route('superadmin.companies.store'), $this->companyData([
                    'subscription_years' => 4,
                ]))
                ->assertRedirect(route('superadmin.companies.create'))
                ->assertSessionHasErrors('subscription_years');

            $this->assertDatabaseMissing('companies', ['slug' => 'empresa-nueva']);
        } finally {
            Carbon::setTestNow();
        }
    }

    #[DataProvider('contractedYearsProvider')]
    public function test_service_years_calculate_the_period_end(int $years): void
    {
        $now = Carbon::parse('2026-08-03 12:00:00', config('app.timezone'));
        Carbon::setTestNow($now);

        try {
            $this->actingAs($this->superadmin())
                ->post(route('superadmin.companies.store'), $this->companyData([
                    'subscription_years' => $years,
                ]))
                ->assertRedirect();

            $subscription = Company::where('slug', 'empresa-nueva')
                ->firstOrFail()
                ->subscription()
                ->firstOrFail();

            $this->assertTrue($subscription->current_period_end->equalTo(
                $now->copy()->addYearsNoOverflow($years)
            ));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_service_term_uses_no_overflow_for_leap_day(): void
    {
        $now = Carbon::parse('2024-02-29 10:00:00', config('app.timezone'));
        Carbon::setTestNow($now);

        try {
            $this->actingAs($this->superadmin())
                ->post(route('superadmin.companies.store'), $this->companyData())
                ->assertRedirect();

            $periodEnd = Company::where('slug', 'empresa-nueva')
                ->firstOrFail()
                ->subscription()
                ->value('current_period_end');

            $this->assertSame('2025-02-28 10:00:00', Carbon::parse($periodEnd)->toDateTimeString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public static function contractedYearsProvider(): array
    {
        return [
            'one year' => [1],
            'two years' => [2],
            'three years' => [3],
            'five years' => [5],
        ];
    }

    public function test_duplicate_names_receive_stable_slug_suffixes(): void
    {
        Company::factory()->create(['slug' => 'financiera-lopez']);
        Company::factory()->create(['slug' => 'financiera-lopez-2']);

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.store'), $this->companyData([
                'name' => 'Financiera Lopez',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'name' => 'Financiera Lopez',
            'slug' => 'financiera-lopez-3',
        ]);
    }

    public function test_slug_generation_normalizes_special_characters(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.store'), $this->companyData([
                'name' => 'Financiera López & Hijos!',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'name' => 'Financiera López & Hijos!',
            'slug' => 'financiera-lopez-hijos',
        ]);
    }

    public function test_settings_failure_rolls_back_the_entire_provisioning_transaction(): void
    {
        $data = $this->companyData();

        $this->mock(DefaultCompanySettings::class, function (MockInterface $mock): void {
            $mock->shouldReceive('initialize')
                ->once()
                ->andThrow(new RuntimeException('Forced settings failure'));
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->superadmin())
                ->post(route('superadmin.companies.store'), $data);
            $this->fail('Provisioning should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced settings failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('companies', ['slug' => 'empresa-nueva']);
        $this->assertDatabaseMissing('users', ['email' => $data['admin_email']]);
        $this->assertDatabaseMissing('settings', ['value' => $data['name']]);
    }

    public function test_duplicate_admin_email_is_rejected_before_provisioning(): void
    {
        User::factory()->create(['email' => 'admin@empresa.test']);

        $this->actingAs($this->superadmin())
            ->from(route('superadmin.companies.create'))
            ->post(route('superadmin.companies.store'), $this->companyData())
            ->assertRedirect(route('superadmin.companies.create'))
            ->assertSessionHasErrors('admin_email');

        $this->assertDatabaseMissing('companies', ['slug' => 'empresa-nueva']);
    }

    public function test_provisioned_admin_can_login_and_reach_tenant_dashboard(): void
    {
        $data = $this->companyData();
        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.store'), $data);
        $this->post(route('logout'));

        $login = $this->post('/login', [
            'login' => $data['admin_email'],
            'password' => $data['password'],
        ]);

        $admin = User::where('email', $data['admin_email'])->firstOrFail();
        $this->assertAuthenticatedAs($admin);
        $login->assertRedirect(route('dashboard', absolute: false));
        $this->get(route('dashboard'))->assertOk();
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'role' => 'superadmin',
        ]);
    }

    private function companyData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Empresa Nueva',
            'admin_name' => 'Admin Empresa',
            'admin_email' => 'admin@empresa.test',
            'admin_phone' => '6621000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subscription_years' => 1,
        ], $overrides);
    }
}
