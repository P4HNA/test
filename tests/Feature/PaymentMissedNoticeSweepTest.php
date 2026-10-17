<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentNeedsReview;
use App\Notifications\PaymentReceipt;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * "payway:recheck" also picks up the emails that were owed and never sent.
 *
 * Both sendReceipt() and alertReview() claim their one-and-only send on a
 * timestamp column, and both hand that claim back when the mail server refuses.
 * Nothing used to come back for them afterwards: the sweeper only ever looked at
 * pending rows, so a receipt lost to an SMTP outage was lost for good and a
 * payment held for review could sit unannounced indefinitely.
 */
class PaymentMissedNoticeSweepTest extends TestCase
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

    private function payment(array $attributes = []): Payment
    {
        $course = Course::factory()->create([
            'title_en' => 'Photoshop Masterclass', 'price' => 35, 'access_type' => 'paid',
        ]);
        $user = User::factory()->create([
            'name' => 'Sokha Chan', 'email_verified_at' => now(),
        ]);

        // created_at is not fillable, so Eloquent would stamp "now" over it and
        // every age-based case here would silently test a fresh row instead.
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $payment = Payment::create(array_merge([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => 'PD'.fake()->unique()->numerify('############'),
            'amount' => 35.00,
            'currency' => 'USD',
            'qr' => '00020101-test',
            'status' => 'paid',
            'paid_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ], $attributes));

        if ($createdAt) {
            Payment::query()->whereKey($payment->getKey())->update(['created_at' => $createdAt]);
            $payment->refresh();
        }

        return $payment;
    }

    public function test_a_receipt_that_never_went_out_is_sent_on_the_next_sweep(): void
    {
        Notification::fake();
        $payment = $this->payment(['receipt_sent_at' => null]);

        $this->artisan('payway:recheck')->assertSuccessful();

        Notification::assertSentOnDemand(PaymentReceipt::class);
        $this->assertNotNull($payment->fresh()->receipt_sent_at);
    }

    public function test_a_receipt_already_sent_is_not_sent_twice(): void
    {
        Notification::fake();
        $this->payment(['receipt_sent_at' => now()->subHour()]);

        $this->artisan('payway:recheck')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_an_unannounced_held_payment_warns_the_admins_on_the_next_sweep(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $payment = $this->payment(['status' => 'review', 'paid_at' => null, 'review_notified_at' => null]);

        $this->artisan('payway:recheck')->assertSuccessful();

        Notification::assertSentTo($admin, PaymentNeedsReview::class);
        $this->assertNotNull($payment->fresh()->review_notified_at);
    }

    /**
     * A held payment is money that probably moved, sitting behind a locked
     * course. Age makes that more urgent, not less, so the alert sweep is
     * deliberately not bounded by --hours the way the receipt sweep is.
     */
    public function test_an_old_held_payment_is_still_announced(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->payment([
            'status' => 'review',
            'paid_at' => null,
            'review_notified_at' => null,
            'created_at' => now()->subMonth(),
        ]);

        $this->artisan('payway:recheck')->assertSuccessful();

        Notification::assertSentTo($admin, PaymentNeedsReview::class);
    }

    public function test_a_receipt_older_than_the_window_is_left_alone(): void
    {
        Notification::fake();
        $this->payment(['receipt_sent_at' => null, 'created_at' => now()->subMonth()]);

        $this->artisan('payway:recheck --hours=24')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_pretend_sends_nothing(): void
    {
        Notification::fake();
        $payment = $this->payment(['receipt_sent_at' => null]);

        $this->artisan('payway:recheck --pretend')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($payment->fresh()->receipt_sent_at);
    }

    public function test_an_unpaid_payment_gets_no_receipt(): void
    {
        Notification::fake();
        $this->payment([
            'status' => 'expired',
            'paid_at' => null,
            'receipt_sent_at' => null,
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('payway:recheck')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
