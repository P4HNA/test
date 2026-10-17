<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceipt;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The receipt: sent once when a payment is confirmed, and never at the cost of
 * the unlock.
 *
 * Two rules. It goes out exactly once however many times we are told about the
 * payment (PayWay retries, and the page polls alongside the callback), and a mail
 * failure must leave the student enrolled anyway -- the course is what they paid
 * for, the email is a courtesy.
 */
class PaymentReceiptTest extends TestCase
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

    private function pendingPayment(array $attributes = []): Payment
    {
        $course = $attributes['course'] ?? Course::factory()->create([
            'title_en' => 'Photoshop Masterclass', 'price' => 35, 'access_type' => 'paid',
        ]);
        $user = $attributes['user'] ?? User::factory()->create([
            'name' => 'Sokha Chan', 'email' => 'sokha@example.com', 'email_verified_at' => now(),
        ]);
        unset($attributes['course'], $attributes['user']);

        return Payment::create(array_merge([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => 'PD'.fake()->unique()->numerify('############'),
            'amount' => 35.00,
            'currency' => 'USD',
            'qr' => '00020101-test',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ], $attributes));
    }

    /** @return array<string, mixed> An APPROVED check-transaction result. */
    private function approved(float $amount = 35.00): array
    {
        return [
            'approved' => true, 'pending' => false, 'final' => false,
            'status' => 'APPROVED', 'amount' => $amount, 'currency' => 'USD',
            'data' => ['payment_status' => 'APPROVED', 'apv' => '123456'],
            'raw' => ['status' => ['code' => '0']],
        ];
    }

    public function test_a_confirmed_payment_emails_the_receipt(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();

        app(PayWayVerifier::class)->markPaid($payment, $this->approved());

        Notification::assertSentOnDemand(
            PaymentReceipt::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'sokha@example.com'
        );
        $this->assertNotNull($payment->fresh()->receipt_sent_at);
    }

    public function test_the_receipt_carries_the_invoice_amount_and_course(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();

        app(PayWayVerifier::class)->markPaid($payment, $this->approved());
        $payment->refresh();

        $mail = (new PaymentReceipt($payment))->toMail($payment->user);
        $rendered = implode(' ', array_merge($mail->introLines, $mail->outroLines)).' '.$mail->subject;

        $this->assertStringContainsString($payment->invoice_no, $rendered);
        $this->assertStringContainsString('Photoshop Masterclass', $rendered);
        $this->assertStringContainsString('$35.00', $rendered);
    }

    /** PayWay retries its callback. The customer must not get three receipts. */
    public function test_a_repeated_confirmation_sends_only_one_receipt(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();
        $verifier = app(PayWayVerifier::class);

        $verifier->markPaid($payment, $this->approved());
        $verifier->markPaid($payment->fresh(), $this->approved());
        $verifier->markPaid($payment->fresh(), $this->approved());

        Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
    }

    public function test_calling_send_receipt_directly_again_sends_nothing(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();
        $verifier = app(PayWayVerifier::class);

        $verifier->markPaid($payment, $this->approved());
        $this->assertFalse($verifier->sendReceipt($payment->fresh()));

        Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
    }

    /** The whole point of sending it outside the transaction. */
    public function test_a_mail_failure_still_leaves_the_student_enrolled(): void
    {
        $payment = $this->pendingPayment();

        Notification::shouldReceive('route')->andThrow(new \RuntimeException('SMTP is down'));

        app(PayWayVerifier::class)->markPaid($payment, $this->approved());
        $payment->refresh();

        $this->assertTrue($payment->isPaid());
        $this->assertTrue($payment->user->isEnrolledIn($payment->course));
    }

    /** A failed send hands the claim back so it can be retried later. */
    public function test_a_failed_receipt_can_be_sent_again_later(): void
    {
        $payment = $this->pendingPayment();

        Notification::shouldReceive('route')->andThrow(new \RuntimeException('SMTP is down'));
        app(PayWayVerifier::class)->markPaid($payment, $this->approved());

        $this->assertNull($payment->fresh()->receipt_sent_at, 'A failed send must not leave the receipt marked as sent.');
    }

    public function test_an_unpaid_payment_is_never_receipted(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();

        $this->assertFalse(app(PayWayVerifier::class)->sendReceipt($payment));

        Notification::assertNothingSent();
    }

    /**
     * A payment whose account was deleted is receipted from its snapshot address
     * rather than being skipped -- but only if there is a usable one.
     */
    public function test_a_payment_with_no_usable_address_is_skipped_quietly(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();
        $payment->forceFill(['status' => 'paid', 'user_id' => null, 'payer_email' => null])->save();

        $this->assertFalse(app(PayWayVerifier::class)->sendReceipt($payment->fresh()));

        Notification::assertNothingSent();
        $this->assertNull($payment->fresh()->receipt_sent_at);
    }

    /* ------------------------------------------- payments:receipt (re-send) */

    public function test_the_command_sends_a_receipt_for_a_payment_that_never_got_one(): void
    {
        Notification::fake();
        // A payment from before receipts existed: paid, never receipted.
        $payment = $this->pendingPayment(['status' => 'paid', 'paid_at' => now()]);

        $this->artisan('payments:receipt '.$payment->invoice_no)
            ->expectsConfirmation('Email this receipt to sokha@example.com?', 'yes')
            ->assertSuccessful();

        Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
        $this->assertNotNull($payment->fresh()->receipt_sent_at);
    }

    /** Sending the same receipt twice by accident is the thing to protect against. */
    public function test_the_command_refuses_to_resend_without_force(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment(['status' => 'paid', 'paid_at' => now(), 'receipt_sent_at' => now()]);

        $this->artisan('payments:receipt '.$payment->invoice_no)->assertFailed();

        Notification::assertNothingSent();
    }

    public function test_force_resends_it(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment(['status' => 'paid', 'paid_at' => now(), 'receipt_sent_at' => now()->subDay()]);

        $this->artisan('payments:receipt '.$payment->invoice_no.' --force')
            ->expectsConfirmation('Email this receipt to sokha@example.com?', 'yes')
            ->assertSuccessful();

        Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
        // The claim is retaken, so the row still records that one went out.
        $this->assertTrue($payment->fresh()->receipt_sent_at->isToday());
    }

    public function test_saying_no_sends_nothing(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment(['status' => 'paid', 'paid_at' => now()]);

        $this->artisan('payments:receipt '.$payment->invoice_no)
            ->expectsConfirmation('Email this receipt to sokha@example.com?', 'no')
            ->assertFailed();

        Notification::assertNothingSent();
        $this->assertNull($payment->fresh()->receipt_sent_at);
    }

    public function test_the_command_will_not_receipt_an_unpaid_payment(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();

        $this->artisan('payments:receipt '.$payment->invoice_no)->assertFailed();

        Notification::assertNothingSent();
    }

    public function test_the_command_reports_an_unknown_invoice(): void
    {
        $this->artisan('payments:receipt PDNOSUCHTHING')->assertFailed();
    }

    public function test_the_command_reports_a_payment_with_no_address(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();
        $payment->forceFill(['status' => 'paid', 'user_id' => null, 'payer_email' => null])->save();

        $this->artisan('payments:receipt '.$payment->invoice_no)->assertFailed();

        Notification::assertNothingSent();
    }

    /** An amount mismatch is held for review and unlocks nothing, so no receipt. */
    public function test_a_payment_held_for_review_is_not_receipted(): void
    {
        Notification::fake();
        $payment = $this->pendingPayment();

        $outcome = app(PayWayVerifier::class)->markPaid($payment, $this->approved(5.00));

        $this->assertSame('review', $outcome);
        Notification::assertNothingSent();
    }
}
