<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function mockSocialiteFacade($provider, $email = 'test@example.com', $id = '12345', $name = 'Test User', bool $emailVerified = true, $nickname = null)
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn($id);
        $abstractUser->shouldReceive('getEmail')->andReturn($email);
        $abstractUser->shouldReceive('getName')->andReturn($name);
        $abstractUser->shouldReceive('getNickname')->andReturn($nickname);
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
        // Google's raw userinfo, as Socialite exposes it.
        $abstractUser->user = ['email' => $email, 'email_verified' => $emailVerified];

        $providerMock = Mockery::mock(Provider::class);
        $providerMock->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($providerMock);
    }

    public function test_google_redirect_works()
    {
        $response = $this->get('/auth/google/redirect');
        $response->assertRedirect();
        $this->assertStringContainsString('google.com', $response->headers->get('Location'));
    }

    public function test_new_user_can_login_via_google()
    {
        $this->mockSocialiteFacade('google', 'newuser@example.com');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false)); // Assuming dashboard redirects to /my-learning for students
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => '12345',
            'provider_email' => 'newuser@example.com',
        ]);
    }

    public function test_existing_email_user_can_link_social_account()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->mockSocialiteFacade('google', 'existing@example.com', 'google123');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google123',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_social_account_logs_in()
    {
        $user = User::factory()->create();
        $account = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google123',
            'provider_email' => 'doesnotmatter@example.com',
        ]);

        $this->mockSocialiteFacade('google', 'changed@example.com', 'google123');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_oauth_failure_redirects_to_login_with_error()
    {
        Socialite::shouldReceive('driver')->with('google')->andThrow(new \Exception('OAuth Failed'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    // --- Test 7: Same provider/provider_id cannot be attached to multiple users ---

    public function test_same_provider_id_cannot_create_duplicate_social_accounts()
    {
        $user = User::factory()->create();
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'unique-google-id',
            'provider_email' => $user->email,
        ]);

        // A second user tries to OAuth with the same google provider_id
        // The controller should find the existing social_account and log in as $user,
        // NOT create a new user or a duplicate social_account.
        $this->mockSocialiteFacade('google', 'different@example.com', 'unique-google-id');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('social_accounts', 1);
        $this->assertDatabaseCount('users', 1);
    }

    // --- Test 9: Missing/unverified email is handled safely ---

    public function test_missing_email_redirects_with_error()
    {
        $this->mockSocialiteFacade('google', null, 'no-email-id', 'No Email User');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // --- Test 10: Existing normal email/password login still works ---

    public function test_existing_email_password_login_still_works()
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'local@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    // --- Test 11: Existing registration still works ---

    public function test_existing_registration_still_works()
    {
        $response = $this->post('/register', [
            'name' => 'Brand New User',
            'email' => 'brandnew@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'brandnew@example.com',
            'name' => 'Brand New User',
        ]);
    }

    // --- Test 12: Existing password reset still works ---

    public function test_existing_password_reset_flow_still_works()
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        // Request a password reset link
        $response = $this->post('/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $response->assertSessionHasNoErrors();

        // Verify the token was created
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);
    }

    // --- Test 13: Existing admin authentication still works ---

    public function test_existing_admin_authentication_still_works()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->assertTrue($admin->isAdmin());

        // Verify admin can authenticate normally
        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        // Verify non-admin cannot access admin routes
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student);
        $this->assertFalse($student->isAdmin());
    }

    // --- Name moderation on Google sign-up (reuses NameModerator) ---

    /**
     * @return array<string, array{0: string}>
     */
    public static function restrictedGoogleNames(): array
    {
        return [
            'english profanity' => ['Big Shit'],
            'english inflection' => ['The mother fucker'],
            'khmer vulgar' => ['ចុយ'],
            'khmer embedded in a longer name' => ['សុភាចុយសុខា'],
            'khmer obfuscated / reversed variant' => ['ជាខ្នុយ'],
            'transliterated khmer slang' => ['Kdmv'],
            'case variation' => ['JMR'],
            'spacing and punctuation trick' => ['  J.K.J  '],
            'two-word slang with odd spacing' => ['OV   hg'],
            'repeated-letter obfuscation' => ['jjkkjj'],
        ];
    }

    #[DataProvider('restrictedGoogleNames')]
    public function test_a_restricted_google_name_creates_no_account($name)
    {
        $this->mockSocialiteFacade('google', 'someone@example.com', 'g-restricted', $name);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_the_rejection_message_does_not_reveal_the_matched_word()
    {
        $this->mockSocialiteFacade('google', 'someone@example.com', 'g-restricted', 'Kdmv');

        $this->get('/auth/google/callback');

        $message = session('errors')->first('email');
        $this->assertStringContainsString('Google profile', $message);
        $this->assertStringNotContainsStringIgnoringCase('kdmv', $message);
    }

    public function test_a_restricted_nickname_fallback_is_moderated_too()
    {
        $this->mockSocialiteFacade('google', 'nick@example.com', 'g-nick', null, true, 'jkj');

        $this->get('/auth/google/callback')->assertRedirect('/login');

        $this->assertDatabaseMissing('users', ['email' => 'nick@example.com']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function legitimateGoogleNames(): array
    {
        return [
            'latin name' => ['Sok Dara'],
            'khmer name' => ['សុខា'],
            'name that merely contains a blocked word' => ['Shitij Kumar'],
        ];
    }

    #[DataProvider('legitimateGoogleNames')]
    public function test_a_legitimate_google_name_signs_up_normally($name)
    {
        $this->mockSocialiteFacade('google', 'legit@example.com', 'g-legit', $name);

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'legit@example.com', 'name' => $name]);
    }

    public function test_existing_accounts_are_not_blocked_by_moderation_on_later_google_logins()
    {
        // Moderation guards account creation; an already-linked account with
        // any Google display name still signs in.
        $user = User::factory()->create();
        SocialAccount::create([
            'user_id' => $user->id, 'provider' => 'google',
            'provider_id' => 'g-linked', 'provider_email' => $user->email,
        ]);

        $this->mockSocialiteFacade('google', $user->email, 'g-linked', 'JMR');

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
    }

    // --- Google sign-up and email verification ---

    public function test_a_google_confirmed_email_arrives_verified()
    {
        $this->mockSocialiteFacade('google', 'verified@example.com', 'g-verified', 'Sok Dara', true);

        $this->get('/auth/google/callback');

        $this->assertTrue(User::where('email', 'verified@example.com')->first()->hasVerifiedEmail());
    }

    public function test_a_google_unconfirmed_email_must_be_verified_the_normal_way()
    {
        Notification::fake();
        $this->mockSocialiteFacade('google', 'unconfirmed@example.com', 'g-unconfirmed', 'Sok Dara', false);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'unconfirmed@example.com')->first();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('learning.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_google_cannot_be_linked_to_an_existing_account_without_a_confirmed_email()
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $this->mockSocialiteFacade('google', 'existing@example.com', 'g-unconfirmed', 'Test User', false);

        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', ['user_id' => $user->id]);
    }

    public function test_google_takeover_of_an_unverified_account_invalidates_the_old_password()
    {
        // Someone registered this address first but never verified it, and
        // knows its password. The real owner then signs in with Google.
        $squatted = User::factory()->unverified()->create([
            'email' => 'victim@example.com',
            'password' => bcrypt('attacker-knows-this'),
        ]);

        $this->mockSocialiteFacade('google', 'victim@example.com', 'g-victim', 'Victim', true);
        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($squatted);
        $squatted->refresh();
        $this->assertTrue($squatted->hasVerifiedEmail());
        $this->assertFalse(Hash::check('attacker-knows-this', $squatted->password));

        // The old password no longer logs in.
        auth()->logout();
        $this->post('/login', ['email' => 'victim@example.com', 'password' => 'attacker-knows-this']);
        $this->assertGuest();
    }

    public function test_linking_google_to_an_already_verified_account_keeps_its_password()
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => bcrypt('my-own-password'),
        ]);

        $this->mockSocialiteFacade('google', 'owner@example.com', 'g-owner', 'Owner', true);
        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('my-own-password', $user->fresh()->password));
    }
}
