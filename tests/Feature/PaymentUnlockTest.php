<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "I paid but the course never unlocked."
 *
 * The payment page polls only the QR it was handed, and a student can end up
 * paying a different one: an earlier QR they still had open, or a screenshot of
 * one from a previous visit. Under Bakong this was solved by sweeping their other
 * QRs on every poll, which cost an API call each time.
 *
 * With PayWay the payment settles itself -- whichever transaction was paid is the
 * one PayWay calls us about -- so the page only has to notice that the student is
 * now enrolled, whichever QR did it. These tests hold that end result in place.
 */
class PaymentUnlockTest extends TestCase
{
    use RefreshDatabase;

    private const CHECK_ENDPOINT = '*/api/payment-gateway/v1/payments/check-transaction-2';

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

        Cache::flush();
    }

    private function paidCourse(): Course
    {
        return Course::factory()->create([
            'title_en' => 'Adobe Illustrator Beginner',
            'price' => 35,
            'access_type' => 'paid',
        ]);
    }

    private function qr(User $user, Course $course, string $invoiceNo, string $status = 'pending'): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => $invoiceNo,
            'amount' => 35.00,
            'currency' => 'USD',
            'qr' => 'qr-payload-'.$invoiceNo,
            'qr_image' => base64_encode('fake-png'),
            'deeplink' => 'https://link.payway.com.kh/aba?id='.$invoiceNo,
            'status' => $status,
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ]);
    }

    /** PayWay knows about exactly one transaction — the QR the customer really paid. */
    private function fakePayWayPaidOnly(string $paidInvoiceNo): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => function ($request) use ($paidInvoiceNo) {
                $approved = ($request->data()['tran_id'] ?? null) === $paidInvoiceNo;

                return Http::response([
                    'status' => ['code' => '0'],
                    'data' => [
                        'payment_status' => $approved ? 'APPROVED' : 'PENDING',
                        'payment_amount' => $approved ? '35.00' : null,
                        'payment_currency' => 'USD',
                    ],
                ]);
            },
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Paying a QR other than the one on screen */
    /* ------------------------------------------------------------------ */

    public function test_paying_an_older_qr_still_unlocks_while_polling_a_newer_one(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();

        $old = $this->qr($user, $course, 'PDOLD0000000000AAAA');  // the one they paid
        $new = $this->qr($user, $course, 'PDNEW0000000000BBBB');  // the one the page polls

        $this->fakePayWayPaidOnly($old->invoice_no);

        // PayWay calls us about the transaction that was actually paid.
        $this->postJson(route('payway.callback'), ['tran_id' => $old->invoice_no])->assertOk();

        // The page, still polling the newer QR, has to notice.
        $this->actingAs($user)
            ->postJson(route('payments.check', $new))
            ->assertOk()
            ->assertJson([
                'state' => 'paid',
                'paid' => true,
                'redirect' => route('courses.show', $course),
            ]);

        // The money is recorded against the QR that was actually paid...
        $this->assertSame('paid', $old->fresh()->status);
        // ...and the course is unlocked.
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_the_polled_qr_is_not_falsely_marked_paid(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();

        $old = $this->qr($user, $course, 'PDOLD0000000000AAAA');
        $new = $this->qr($user, $course, 'PDNEW0000000000BBBB');

        $this->fakePayWayPaidOnly($old->invoice_no);

        $this->postJson(route('payway.callback'), ['tran_id' => $old->invoice_no])->assertOk();
        $this->actingAs($user)->postJson(route('payments.check', $new))->assertOk();

        // Only one transaction was paid; the other must not be forged into a
        // second one just because the student got their course.
        $this->assertSame('pending', $new->fresh()->status);
        $this->assertSame(1, Payment::where('status', 'paid')->count());
        $this->assertSame(1, Enrollment::count());
    }

    public function test_an_expired_older_qr_that_was_paid_still_unlocks(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();

        // Paid at the last second: our clock had already written it off.
        $old = $this->qr($user, $course, 'PDOLD0000000000AAAA', 'expired');
        $new = $this->qr($user, $course, 'PDNEW0000000000BBBB');

        $this->fakePayWayPaidOnly($old->invoice_no);

        $this->postJson(route('payway.callback'), ['tran_id' => $old->invoice_no])->assertOk();

        $this->assertSame('paid', $old->fresh()->status);

        $this->actingAs($user)
            ->postJson(route('payments.check', $new))
            ->assertOk()
            ->assertJson(['paid' => true]);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_returning_to_checkout_after_paying_an_older_qr_unlocks_instead_of_charging_again(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();

        $old = $this->qr($user, $course, 'PDOLD0000000000AAAA');

        $this->fakePayWayPaidOnly($old->invoice_no);

        // They paid, closed the page, and the callback never reached us (no
        // tunnel running, say). Coming back must not mint a second QR.
        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('courses.show', $course));

        $this->assertSame('paid', $old->fresh()->status);
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Enrollment::count());
    }

    /* ------------------------------------------------------------------ */
    /* The fallback check stays rate-limited */
    /* ------------------------------------------------------------------ */

    public function test_the_fallback_check_is_throttled_so_it_does_not_hammer_payway(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();
        $payment = $this->qr($user, $course, 'PDONLY000000000CCCC');

        $this->fakePayWayPaidOnly('PDSOMETHINGELSE');

        // Ten polls, three seconds apart, all inside one 20-second window.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();
        }

        Http::assertSentCount(1);
    }

    public function test_the_fallback_check_runs_again_once_the_throttle_window_passes(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();
        $payment = $this->qr($user, $course, 'PDONLY000000000CCCC');

        $this->fakePayWayPaidOnly('PDSOMETHINGELSE');

        $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();
        Http::assertSentCount(1);

        // 20 seconds later the gate reopens.
        $this->travel(21)->seconds();

        $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();
        Http::assertSentCount(2);
    }

    /* ------------------------------------------------------------------ */
    /* The ordinary case, and the boundaries */
    /* ------------------------------------------------------------------ */

    public function test_a_normal_single_qr_payment_still_works(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();
        $only = $this->qr($user, $course, 'PDONLY000000000CCCC');

        $this->fakePayWayPaidOnly($only->invoice_no);

        $this->actingAs($user)
            ->postJson(route('payments.check', $only))
            ->assertOk()
            ->assertJson(['paid' => true]);

        $this->assertSame('paid', $only->fresh()->status);
        $this->assertSame(1, Enrollment::count());
    }

    public function test_another_customers_paid_qr_does_not_unlock_your_course(): void
    {
        $course = $this->paidCourse();
        $stranger = User::factory()->create(['email_verified_at' => now()]);
        $me = User::factory()->create(['email_verified_at' => now()]);

        $theirs = $this->qr($stranger, $course, 'PDTHEIRS000000AAAA');  // paid, but not mine
        $mine = $this->qr($me, $course, 'PDMINE00000000BBBB');

        $this->fakePayWayPaidOnly($theirs->invoice_no);

        $this->postJson(route('payway.callback'), ['tran_id' => $theirs->invoice_no])->assertOk();

        $this->actingAs($me)
            ->postJson(route('payments.check', $mine))
            ->assertOk()
            ->assertJson(['paid' => false]);

        $this->assertDatabaseMissing('enrollments', ['user_id' => $me->id]);
        $this->assertDatabaseHas('enrollments', ['user_id' => $stranger->id]);
    }

    public function test_an_unverified_customer_can_still_have_their_payment_confirmed(): void
    {
        // Changing your email address resets email_verified_at. A customer who
        // does that mid-payment used to get 403 on every poll and was never
        // unlocked, while the page sat on "Waiting for payment...".
        $course = $this->paidCourse();
        $user = User::factory()->unverified()->create();
        $payment = $this->qr($user, $course, 'PDUNVERIFIED00DDDD');

        $this->fakePayWayPaidOnly($payment->invoice_no);

        $this->actingAs($user)
            ->postJson(route('payments.check', $payment))
            ->assertOk()
            ->assertJson(['paid' => true]);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_an_unverified_customer_still_cannot_start_a_new_payment(): void
    {
        // The product rule is unchanged: you need a verified address to pay.
        $course = $this->paidCourse();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_an_unverified_customer_still_cannot_open_a_lesson(): void
    {
        $course = $this->paidCourse();
        $user = User::factory()->unverified()->create();
        $payment = $this->qr($user, $course, 'PDUNVERIFIED00EEEE');

        $this->fakePayWayPaidOnly($payment->invoice_no);
        $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();

        // Enrolled by the payment, but lessons stay behind the verified gate.
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id]);

        $module = $course->modules()->create(['title_en' => 'M', 'title_km' => 'M', 'sort_order' => 1]);
        $lesson = $module->lessons()->create([
            'title_en' => 'L', 'title_km' => 'L', 'video_host' => 'youtube', 'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_guest_still_cannot_check_a_payment(): void
    {
        $course = $this->paidCourse();
        $payment = $this->qr(User::factory()->create(), $course, 'PDGUEST0000000FFFF');

        $this->post(route('payments.check', $payment))->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------ */
    /* The page's own handling of a refusal */
    /* ------------------------------------------------------------------ */

    public function test_the_page_stops_polling_on_a_refused_check(): void
    {
        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '0'],
                'qrString' => 'qr-payload',
                'qrImage' => base64_encode('fake-png'),
                'abapay_deeplink' => 'https://link.payway.com.kh/aba?id=TEST',
            ]),
            self::CHECK_ENDPOINT => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $html = $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get(route('payments.checkout', $this->paidCourse()))
            ->assertOk()
            ->getContent();

        // A 403 must end the poll loop, not fall through to "Waiting...".
        // fail() sets the message and calls stop(), which clears the intervals.
        $this->assertStringContainsString('response.status === 403', $html);
        $this->assertMatchesRegularExpression(
            "/response\.status === 403\)\s*\{\s*this\.fail\(/s",
            $html,
            'A 403 no longer ends the poll loop — the page would sit on "Waiting for payment" forever.'
        );
        $this->assertMatchesRegularExpression(
            "/fail\(state, title, detail\)\s*\{\s*this\.set\(state, title, detail\);\s*this\.stop\(\);/s",
            $html,
            'fail() no longer stops the timers.'
        );
    }
}
