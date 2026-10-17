<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentNeedsReview;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The alert for a payment held for review.
 *
 * "review" is the one state nothing else resolves: PayWay reported an amount
 * that does not match the order, so the course stays locked and the customer is
 * told to make contact. Somebody therefore has to be told to expect them.
 *
 * Same two rules as the receipt. It goes out exactly once however many times we
 * are told about the payment, and a mail failure must not change what the
 * payment is -- the course stays correctly locked either way.
 */
class PaymentReviewAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.payway', [
            'base_url' => 'https://checkout-sandbox.payway.com.kh',
            'merchant_id' => 'test_merchant',
            'api_key' => 'test_api_key',
            'callback_url' => 'https://tunnel.example.com/payway/callback',
            'currency' => 'USD',
            'qr_lifetime' => 10,
            'timeout' => 15,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    private function pendingPayment(): Payment
    {
        $course = Course::factory()->create([
            'title_en' => 'Photoshop Masterclass', 'price' => 35, 'access_type' => 'paid',
        ]);
        $user = User::factory()->create([
            'name' => 'Sokha Chan', 'email' => 'sokha@example.com', 'email_verified_at' => now(),
        ]);

        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => 'PD'.fake()->unique()->numerify('############'),
            'amount' => 35,
            'currency' => 'USD',
            'qr' => '00020101-test',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ]);
    }

    /** PayWay says APPROVED, but for the wrong money. */
    private function approvedFor(float $amount, string $currency = 'USD'): void
    {
        Http::fake([
            '*check-transaction*' => Http::response([
                'status' => ['code' => '0'],
                'data' => [
                    'payment_status' => 'APPROVED',
                    'payment_amount' => $amount,
                    'payment_currency' => $currency,
                    'apv' => '123456',
                ],
            ]),
        ]);
    }

    public function test_an_amount_mismatch_warns_the_admins(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $payment = $this->pendingPayment();
        $this->approvedFor(5.00);

        $state = app(PayWayVerifier::class)->settle($payment);

        $this->assertSame('review', $state);
        Notification::assertSentTo($admin, PaymentNeedsReview::class);
    }

    public function test_the_course_stays_locked_when_the_amount_does_not_match(): void
    {
        Notification::fake();
        $this->admin();
        $payment = $this->pendingPayment();
        $this->approvedFor(5.00);

        app(PayWayVerifier::class)->settle($payment);

        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $payment->user_id,
            'course_id' => $payment->course_id,
        ]);
    }

    public function test_the_admins_are_warned_only_once_however_often_we_are_told(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $payment = $this->pendingPayment();
        $this->approvedFor(5.00);

        $verifier = app(PayWayVerifier::class);
        $verifier->settle($payment);
        $verifier->alertReview($payment->fresh());
        $verifier->alertReview($payment->fresh());

        Notification::assertSentToTimes($admin, PaymentNeedsReview::class, 1);
    }

    public function test_every_admin_is_warned_and_no_student_is(): void
    {
        Notification::fake();
        $first = $this->admin();
        $second = $this->admin();
        $payment = $this->pendingPayment();
        $this->approvedFor(5.00);

        app(PayWayVerifier::class)->settle($payment);

        Notification::assertSentTo($first, PaymentNeedsReview::class);
        Notification::assertSentTo($second, PaymentNeedsReview::class);
        Notification::assertNotSentTo($payment->user, PaymentNeedsReview::class);
    }

    public function test_a_matching_payment_warns_nobody(): void
    {
        Notification::fake();
        $this->admin();
        $payment = $this->pendingPayment();
        $this->approvedFor(35.00);

        $state = app(PayWayVerifier::class)->settle($payment);

        $this->assertSame('paid', $state);
        Notification::assertNothingSentTo([$payment->user]);
        Notification::assertNotSentTo(User::where('role', 'admin')->get(), PaymentNeedsReview::class);
    }

    public function test_a_failed_alert_is_left_unclaimed_so_it_can_be_retried(): void
    {
        $this->admin();
        $payment = $this->pendingPayment();
        $this->approvedFor(5.00);

        // The mail server refusing must not swallow the warning for good.
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('smtp down'));

        $state = app(PayWayVerifier::class)->settle($payment);

        $this->assertSame('review', $state);
        $this->assertNull($payment->fresh()->review_notified_at);
    }

    public function test_a_held_payment_with_no_admin_to_tell_is_still_held(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();
        $this->approvedFor(5.00);

        $state = app(PayWayVerifier::class)->settle($payment);

        $this->assertSame('review', $state);
        $this->assertNull($payment->fresh()->review_notified_at);
    }
}
