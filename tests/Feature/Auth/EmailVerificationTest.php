<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('learning.index', absolute: false));
        $response->assertSessionHas('status');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_expired_verification_link_is_rejected_with_a_way_forward(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->travel(61)->minutes();

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl.'x')
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_one_users_link_cannot_verify_another_account(): void
    {
        $owner = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $ownersLink = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $owner->id, 'hash' => sha1($owner->email)]
        );

        $this->actingAs($other)->get($ownersLink)
            ->assertRedirect(route('verification.notice'));

        $this->assertFalse($owner->fresh()->hasVerifiedEmail());
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
    }

    public function test_registration_sends_the_verification_email_and_leaves_the_account_unverified(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'new.student@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_still_succeeds_when_the_mail_server_fails(): void
    {
        Mail::shouldReceive('mailer')->andThrow(new RuntimeException('Connection could not be established'));

        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(route('verification.notice', absolute: false))
            ->assertSessionHasErrors('verification');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new.student@example.com']);
    }

    public function test_the_verify_page_matches_the_light_auth_design_and_shows_the_address(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'shown@example.com']);

        $this->actingAs($user)->get('/verify-email')
            ->assertOk()
            ->assertSee('shown@example.com')
            ->assertSee('background:#FFFFFF', false)
            ->assertSee(route('verification.send'), false)
            ->assertSee(route('profile.edit'), false);
    }

    public function test_verified_users_are_sent_on_from_the_verify_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/verify-email')->assertRedirect();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function verifiedOnlyRoutes(): array
    {
        return [
            'my learning' => ['get', 'learning.index'],
            'learning profile' => ['get', 'learning.profile'],
            'dashboard' => ['get', 'dashboard'],
        ];
    }

    #[DataProvider('verifiedOnlyRoutes')]
    public function test_unverified_users_are_sent_to_verify_before_learning(string $method, string $route): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->{$method}(route($route))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_users_cannot_enrol_pay_or_open_lessons(): void
    {
        $user = User::factory()->unverified()->create();
        $course = Course::factory()->create(['access_type' => 'free', 'price' => 0]);
        $module = Module::query()->create(['course_id' => $course->id, 'title_en' => 'M', 'title_km' => 'M', 'sort_order' => 0]);
        $lesson = Lesson::query()->create([
            'module_id' => $module->id, 'title_en' => 'L', 'title_km' => 'L',
            'video_host' => 'youtube', 'duration_minutes' => 1, 'sort_order' => 0,
        ]);

        $this->actingAs($user)->post(route('courses.enroll', $course))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('payments.checkout', $course))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('courses.lessons.show', [$course, $lesson]))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post(route('courses.lessons.complete', [$course, $lesson]))->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('enrollments', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('lesson_completions', ['user_id' => $user->id]);
    }

    public function test_unverified_users_can_still_reach_their_profile_to_fix_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_verified_users_keep_working_normally(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('learning.index'))->assertOk();
    }

    public function test_resend_sends_a_new_link_then_enforces_a_cooldown(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'))
            ->assertRedirect('/verify-email')
            ->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);

        // Immediately again: refused, nothing sent, and told how long to wait.
        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'))
            ->assertSessionHasErrors('verification');
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);

        // The countdown survives a page refresh.
        $this->actingAs($user)->get('/verify-email')->assertSee('Resend link in');

        $this->travel(EmailVerificationNotificationController::COOLDOWN_SECONDS + 1)->seconds();

        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_registration_starts_the_resend_cooldown(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->post(route('verification.send'))->assertSessionHasErrors('verification');

        $user = User::where('email', 'new.student@example.com')->firstOrFail();
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    public function test_a_resend_mail_failure_is_reported_without_crashing(): void
    {
        $user = User::factory()->unverified()->create();
        Mail::shouldReceive('mailer')->andThrow(new RuntimeException('Connection could not be established'));

        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'))
            ->assertRedirect('/verify-email')
            ->assertSessionHasErrors('verification');

        // No cooldown for an email that never left.
        $this->assertSame(0, EmailVerificationNotificationController::secondsUntilResendAllowed($user));
    }
}
