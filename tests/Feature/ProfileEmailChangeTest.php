<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileEmailChangeController;
use App\Models\EmailChangeRequest;
use App\Models\User;
use App\Notifications\EmailChangeVerificationCode;
use App\Services\EmailChangeVerifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ProfileEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Triggers the "change email" step via the profile form and returns the
     * plaintext code that was sent to the new address.
     */
    private function requestEmailChange(User $user, string $newEmail): string
    {
        Notification::fake();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $newEmail,
        ])->assertSessionHasNoErrors();

        $code = null;

        Notification::assertSentOnDemand(
            EmailChangeVerificationCode::class,
            function ($notification) use (&$code, $newEmail) {
                if ($notification->newEmail !== $newEmail) {
                    return false;
                }

                $code = $notification->code;

                return true;
            }
        );

        $this->assertNotNull($code, 'No verification code notification was sent.');

        return $code;
    }

    public function test_changing_the_email_field_sends_a_code_and_does_not_change_it_yet(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $code = $this->requestEmailChange($user, 'new@example.com');

        $this->assertNotNull($code);
        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertDatabaseHas('email_change_requests', [
            'user_id' => $user->id,
            'new_email' => 'new@example.com',
        ]);
    }

    public function test_the_code_is_sent_to_the_new_address_not_the_current_one(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        Notification::fake();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => 'new@example.com',
        ]);

        Notification::assertNotSentTo($user, EmailChangeVerificationCode::class);
        Notification::assertSentOnDemand(EmailChangeVerificationCode::class);
    }

    public function test_confirming_the_correct_code_changes_the_email_and_marks_it_verified(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $code = $this->requestEmailChange($user, 'new@example.com');

        $response = $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => $code]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('email_change_requests', ['user_id' => $user->id]);
    }

    public function test_confirming_an_incorrect_code_does_not_change_the_email(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        $response = $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => '000000']);

        $response->assertSessionHasErrorsIn('emailChange', 'code');
        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertDatabaseHas('email_change_requests', ['user_id' => $user->id, 'attempts' => 1]);
    }

    public function test_a_non_digit_or_wrong_length_code_is_rejected_by_validation(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => 'abcdef'])
            ->assertSessionHasErrorsIn('emailChange', 'code');

        $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => '123'])
            ->assertSessionHasErrorsIn('emailChange', 'code');

        $this->assertSame('old@example.com', $user->fresh()->email);
    }

    public function test_too_many_incorrect_attempts_invalidates_the_code(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $code = $this->requestEmailChange($user, 'new@example.com');

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => '000000']);
        }

        // Even the real code no longer works once the attempt limit is hit.
        $response = $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => $code]);

        $response->assertSessionHasErrorsIn('emailChange', 'code');
        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertDatabaseMissing('email_change_requests', ['user_id' => $user->id]);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $code = $this->requestEmailChange($user, 'new@example.com');

        EmailChangeRequest::where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);

        $response = $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => $code]);

        $response->assertSessionHasErrorsIn('emailChange', 'code');
        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertDatabaseMissing('email_change_requests', ['user_id' => $user->id]);
    }

    public function test_resending_generates_a_new_code_that_invalidates_the_old_one(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $firstCode = $this->requestEmailChange($user, 'new@example.com');

        $this->travel(EmailChangeVerifier::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        Notification::fake();
        $this->actingAs($user)->post(route('profile.email.resend'))
            ->assertSessionHas('status', 'email-code-resent');

        $secondCode = null;
        Notification::assertSentOnDemand(
            EmailChangeVerificationCode::class,
            function ($notification) use (&$secondCode) {
                $secondCode = $notification->code;

                return true;
            }
        );

        $this->assertNotNull($secondCode);

        if ($firstCode === $secondCode) {
            $this->markTestSkipped('Randomly generated the same 6-digit code twice.');
        }

        // The old code must no longer work after a resend.
        $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => $firstCode])
            ->assertSessionHasErrorsIn('emailChange', 'code');
        $this->assertSame('old@example.com', $user->fresh()->email);

        // The new code does work.
        $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => $secondCode])
            ->assertSessionHasNoErrors();
        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_resending_with_nothing_pending_does_nothing(): void
    {
        $user = User::factory()->create();

        Notification::fake();

        $this->actingAs($user)->post(route('profile.email.resend'))
            ->assertSessionMissing('status');

        Notification::assertNothingSent();
    }

    public function test_cancelling_discards_the_pending_change(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        $this->actingAs($user)->delete(route('profile.email.cancel'));

        $this->assertDatabaseMissing('email_change_requests', ['user_id' => $user->id]);

        // Confirming afterwards has nothing to confirm.
        $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => '123456'])
            ->assertSessionHasErrorsIn('emailChange', 'code');
    }

    public function test_the_verification_code_never_appears_in_the_http_response(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $code = $this->requestEmailChange($user, 'new@example.com');

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertDontSee($code);
    }

    public function test_the_confirm_code_endpoint_is_rate_limited(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => '000000']);
        }

        $response = $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => '000000']);

        $response->assertStatus(429);
    }

    public function test_the_code_notification_is_sent_immediately_not_queued(): void
    {
        // A queued notification needs a running worker; without one the
        // code silently never leaves (the original bug on QUEUE_CONNECTION=database).
        $this->assertNotInstanceOf(ShouldQueue::class, new EmailChangeVerificationCode('123456', 'new@example.com'));

        config(['queue.default' => 'database']);
        Queue::fake();

        $user = User::factory()->create(['email' => 'old@example.com']);
        $code = $this->requestEmailChange($user, 'new@example.com');

        Queue::assertNothingPushed();
        $this->assertNotNull($code);
    }

    public function test_the_code_email_is_actually_rendered_and_handed_to_the_mailer_for_the_new_address(): void
    {
        // Real notification + mail pipeline (no Notification::fake), using
        // the in-memory "array" mailer from phpunit.xml.
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ])->assertSessionHas('status', 'email-verification-sent');

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);

        $email = $messages->first()->getOriginalMessage();
        $this->assertSame(['new@example.com'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));
        $this->assertSame('Confirm your new email address', $email->getSubject());
        $this->assertMatchesRegularExpression('/\b\d{6}\b/', $email->getTextBody() ?? $email->getHtmlBody());
    }

    public function test_resend_is_rejected_during_the_cooldown_and_sends_nothing(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $firstCode = $this->requestEmailChange($user, 'new@example.com');
        $sentAt = now();

        Notification::fake();

        foreach ([0, 1, 30, 58] as $secondsLater) {
            $this->travelTo($sentAt->copy()->addSeconds($secondsLater));
            $this->actingAs($user)->post(route('profile.email.resend'))
                ->assertRedirect(route('profile.edit'))
                ->assertSessionHasErrorsIn('emailChange', 'resend')
                ->assertSessionMissing('status');
        }

        Notification::assertNothingSent();

        // The original code is untouched by the refused resends.
        $this->actingAs($user)->post(route('profile.email.confirm'), ['code' => $firstCode])
            ->assertSessionHasNoErrors();
        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_the_cooldown_message_tells_the_user_when_they_can_try_again(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        $this->travel(20)->seconds();

        $this->actingAs($user)->post(route('profile.email.resend'));

        $this->assertMatchesRegularExpression(
            '/You can request another one in (39|40|41) seconds\./',
            session('errors')->getBag('emailChange')->first('resend')
        );
    }

    public function test_resend_is_allowed_again_once_the_cooldown_has_passed(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        $this->travel(EmailChangeVerifier::RESEND_COOLDOWN_SECONDS - 1)->seconds();
        Notification::fake();
        $this->actingAs($user)->post(route('profile.email.resend'))
            ->assertSessionHasErrorsIn('emailChange', 'resend');
        Notification::assertNothingSent();

        $this->travel(2)->seconds();
        $this->actingAs($user)->post(route('profile.email.resend'))
            ->assertSessionHas('status', 'email-code-resent');
        Notification::assertSentOnDemandTimes(EmailChangeVerificationCode::class, 1);

        // ...and a fresh cooldown starts from that resend.
        $this->actingAs($user)->post(route('profile.email.resend'))
            ->assertSessionHasErrorsIn('emailChange', 'resend');
        Notification::assertSentOnDemandTimes(EmailChangeVerificationCode::class, 1);
    }

    public function test_the_profile_form_cannot_be_used_to_bypass_the_cooldown(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'phone' => null]);
        $this->requestEmailChange($user, 'new@example.com');

        Notification::fake();

        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => 'Updated Name',
            'phone' => '012345678',
            'email' => 'other@example.com',
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSent();

        // Name/phone still saved; the pending change still targets the first address.
        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('012345678', $user->phone);
        $this->assertSame('old@example.com', $user->email);
        $this->assertDatabaseHas('email_change_requests', ['user_id' => $user->id, 'new_email' => 'new@example.com']);
    }

    public function test_the_profile_page_shows_the_resend_countdown_and_survives_a_refresh(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $this->requestEmailChange($user, 'new@example.com');

        $this->travel(15)->seconds();

        $html = $this->actingAs($user)->get('/profile')->getContent();

        $this->assertMatchesRegularExpression('/Resend code in (44|45|46)s/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*aria-describedby="resend-status"[^>]*disabled/s', $html);
        $this->assertMatchesRegularExpression('/You can request another code in (44|45|46) seconds\./', $html);

        $this->travel(EmailChangeVerifier::RESEND_COOLDOWN_SECONDS)->seconds();

        $html = $this->actingAs($user)->get('/profile')->getContent();
        $this->assertStringNotContainsString('Resend code in', strip_tags($html));
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*aria-describedby="resend-status"[^>]*\sdisabled[\s>]/s', $html);
    }

    public function test_a_mail_transport_failure_is_reported_without_leaking_the_code_and_allows_an_immediate_retry(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        Mail::shouldReceive('mailer')->andThrow(new RuntimeException('Connection could not be established with host smtp.test'));
        Log::spy();

        $response = $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ]);

        $response->assertSessionHasErrors(['email' => ProfileEmailChangeController::sendFailedMessage()]);
        $response->assertSessionMissing('status');
        $this->assertSame('old@example.com', $user->fresh()->email);

        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context) {
            return $message === 'Email-change verification code could not be sent.'
                && ! preg_match('/\b\d{6}\b/', json_encode($context));
        })->once();

        // No cooldown was started for an email that never left.
        $this->assertSame(0, app(EmailChangeVerifier::class)->secondsUntilResendAllowed($user));
    }
}
