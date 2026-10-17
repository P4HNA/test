<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_the_password_free_demo_login_no_longer_exists(): void
    {
        // The seeded accounts it used to log into still exist.
        User::factory()->admin()->create(['email' => 'admin@piseth.design']);
        User::factory()->create(['email' => 'pisethyoeurn@gmail.com']);

        foreach (['admin', 'student'] as $role) {
            $this->post('/demo/'.$role)->assertNotFound();
            $this->assertGuest();
        }

        $this->assertFalse(Route::has('demo.login'));
    }

    public function test_the_admin_still_logs_in_normally_with_a_password(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@piseth.design',
            'password' => bcrypt('a-strong-admin-password'),
        ]);

        $this->post('/login', [
            'email' => 'admin@piseth.design',
            'password' => 'a-strong-admin-password',
        ]);

        $this->assertAuthenticatedAs($admin);
    }
}
