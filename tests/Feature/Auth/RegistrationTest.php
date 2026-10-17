<?php

namespace Tests\Feature\Auth;

use App\Rules\NotRestrictedName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        // New accounts go to "check your inbox" first (email verification).
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_registration_rejects_a_restricted_profile_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'JMR',
            'email' => 'jmr@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['name' => NotRestrictedName::MESSAGE]);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jmr@example.com']);
    }

    public function test_registration_rejects_an_inappropriate_english_name_and_shows_the_warning(): void
    {
        $response = $this->from('/register')->followingRedirects()->post('/register', [
            'name' => 'Bitch',
            'email' => 'someone@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
        $response->assertSee(NotRestrictedName::MESSAGE);
        $this->assertMatchesRegularExpression('/<input\s[^>]*id="name"[^>]*aria-invalid="true"[^>]*aria-describedby="name-error"/', $response->getContent());
    }
}
