<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_screen_is_not_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_public_registration_cannot_create_or_authenticate_users(): void
    {
        $usersBeforeRequest = User::query()->count();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertSame($usersBeforeRequest, User::query()->count());
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_internal_user_management_route_remains_admin_protected(): void
    {
        $route = Route::getRoutes()->getByName('users.store');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('company.required', $route->gatherMiddleware());
        $this->assertContains('solo.admin', $route->gatherMiddleware());
    }
}
