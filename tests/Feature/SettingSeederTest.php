<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_active_companies_receive_the_same_key_in_distinct_records(): void
    {
        $company = $this->company('Empresa Uno', 'empresa-uno');
        $otherCompany = $this->company('Empresa Dos', 'empresa-dos');

        $this->seed(SettingSeeder::class);

        $settings = Setting::where('key', 'company_name')
            ->whereIn('company_id', [$company->id, $otherCompany->id])
            ->get();

        $this->assertCount(2, $settings);
        $this->assertCount(2, $settings->pluck('company_id')->unique());
    }

    public function test_running_the_seeder_twice_does_not_duplicate_settings(): void
    {
        $company = $this->company('Empresa Uno', 'empresa-uno');

        $this->seed(SettingSeeder::class);
        $firstCount = Setting::where('company_id', $company->id)->count();
        $firstIds = Setting::where('company_id', $company->id)
            ->orderBy('key')
            ->pluck('id')
            ->all();

        $this->seed(SettingSeeder::class);

        $this->assertSame($firstCount, Setting::where('company_id', $company->id)->count());
        $this->assertSame(
            $firstIds,
            Setting::where('company_id', $company->id)->orderBy('key')->pluck('id')->all()
        );
    }

    public function test_seeding_an_active_company_does_not_update_an_inactive_company(): void
    {
        $company = $this->company('Empresa Uno', 'empresa-uno');
        $otherCompany = $this->company('Empresa Dos', 'empresa-dos');

        $this->seed(SettingSeeder::class);

        $otherCompany->update(['status' => 'inactive']);
        Setting::where('company_id', $otherCompany->id)
            ->where('key', 'company_name')
            ->update(['value' => 'Valor Empresa Dos']);
        Setting::where('company_id', $company->id)
            ->where('key', 'company_name')
            ->update(['value' => 'Valor anterior']);

        $this->seed(SettingSeeder::class);

        $this->assertSame(
            'MelPres',
            Setting::where('company_id', $company->id)->where('key', 'company_name')->value('value')
        );
        $this->assertSame(
            'Valor Empresa Dos',
            Setting::where('company_id', $otherCompany->id)->where('key', 'company_name')->value('value')
        );
    }

    public function test_seeder_never_creates_settings_without_a_company(): void
    {
        $company = $this->company('Empresa Uno', 'empresa-uno');

        $this->seed(SettingSeeder::class);

        $this->assertSame(0, Setting::whereNull('company_id')->count());
        $this->assertDatabaseHas('settings', [
            'company_id' => $company->id,
            'key' => 'company_name',
            'value' => 'MelPres',
        ]);
    }

    public function test_new_active_company_receives_its_settings_on_the_next_run(): void
    {
        $company = $this->company('Empresa Uno', 'empresa-uno');
        $this->seed(SettingSeeder::class);
        $expectedCount = Setting::where('company_id', $company->id)->count();

        $newCompany = $this->company('Empresa Nueva', 'empresa-nueva');
        $this->assertSame(0, Setting::where('company_id', $newCompany->id)->count());

        $this->seed(SettingSeeder::class);

        $this->assertSame(
            $expectedCount,
            Setting::where('company_id', $newCompany->id)->count()
        );
    }

    public function test_every_seeded_setting_keeps_the_correct_company_id(): void
    {
        $company = $this->company('Empresa Uno', 'empresa-uno');
        $otherCompany = $this->company('Empresa Dos', 'empresa-dos');

        $this->seed(SettingSeeder::class);

        $seeded = Setting::whereIn('company_id', [$company->id, $otherCompany->id])->get();

        $this->assertNotEmpty($seeded);
        $this->assertTrue(
            $seeded->every(
                fn (Setting $setting) => in_array(
                    (int) $setting->company_id,
                    [$company->id, $otherCompany->id],
                    true
                )
            )
        );
    }

    private function company(string $name, string $slug): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }
}
