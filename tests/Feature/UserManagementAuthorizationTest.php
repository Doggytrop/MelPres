<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_user_crud_pages(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');
        $advisor = $this->user($company, 'advisor');

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.create'))->assertOk();
        $this->actingAs($admin)->get(route('users.edit', $advisor))->assertOk();
    }

    public function test_advisor_receives_403(): void
    {
        [, $advisor] = $this->tenant('Empresa Uno', 'empresa-uno', 'advisor');

        $this->actingAs($advisor)->get(route('users.index'))->assertForbidden();
    }

    public function test_collector_receives_403(): void
    {
        [, $collector] = $this->tenant('Empresa Uno', 'empresa-uno', 'collector');

        $this->actingAs($collector)->get(route('users.index'))->assertForbidden();
    }

    public function test_customer_receives_403(): void
    {
        [, $customerUser] = $this->tenant('Empresa Uno', 'empresa-uno', 'customer');

        $this->actingAs($customerUser)->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_cannot_access_or_modify_user_from_another_company(): void
    {
        [, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos', 'admin');
        $otherUser = $this->user($otherCompany, 'advisor');

        $this->actingAs($admin)->get(route('users.edit', $otherUser))->assertNotFound();
        $this->actingAs($admin)->put(route('users.update', $otherUser), $this->userData())->assertNotFound();
        $this->actingAs($admin)->delete(route('users.destroy', $otherUser))->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'company_id' => $otherCompany->id,
            'role' => 'advisor',
        ]);
    }

    public function test_company_id_from_request_is_ignored(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos', 'admin');
        $data = $this->userData([
            'email' => 'new-user@example.com',
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($admin)->post(route('users.store'), $data)->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'company_id' => $company->id,
            'role' => 'advisor',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'new-user@example.com',
            'company_id' => $otherCompany->id,
        ]);
    }

    public function test_admin_cannot_delete_itself(): void
    {
        [, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'No puedes eliminarte a ti mismo.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');

        $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $this->assertSame(
            1,
            User::where('company_id', $company->id)->where('role', 'admin')->count()
        );
    }

    public function test_company_cannot_be_left_without_an_admin(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->put(route('users.update', $admin), $this->userData([
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'advisor',
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'La empresa debe conservar al menos un usuario administrador.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
    }

    public function test_activity_log_is_created_for_create_update_and_delete(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');

        $this->actingAs($admin)->post(route('users.store'), $this->userData([
            'email' => 'managed-user@example.com',
            'role' => 'collector',
        ]));

        $managedUser = User::where('email', 'managed-user@example.com')->firstOrFail();

        $this->actingAs($admin)->put(route('users.update', $managedUser), $this->userData([
            'name' => 'Usuario Actualizado',
            'email' => 'managed-user@example.com',
            'role' => 'advisor',
            'password' => '',
            'password_confirmation' => '',
        ]));

        $this->actingAs($admin)->delete(route('users.destroy', $managedUser));

        foreach (['create', 'update', 'delete'] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'company_id' => $company->id,
                'action' => $action,
                'module' => 'users',
                'model_type' => User::class,
                'model_id' => $managedUser->id,
            ]);
        }
    }

    public function test_invalid_or_global_roles_cannot_be_assigned(): void
    {
        [, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');

        $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), $this->userData(['role' => 'superadmin']))
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('role');
    }

    private function tenant(string $name, string $slug, string $role): array
    {
        $company = Company::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
        ]);

        return [$company, $this->user($company, $role)];
    }

    private function user(Company $company, string $role): User
    {
        return User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
        ]);
    }

    private function userData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuario Gestionado',
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'role' => 'advisor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }
}
