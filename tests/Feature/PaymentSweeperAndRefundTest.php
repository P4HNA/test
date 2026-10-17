<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceipt;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The two things that catch what the callback misses.
 *
 * "payway:recheck" finds payments nobody was told about and unlocks them; a
 * refund closes the course again without destroying the record of the money.
 */
class PaymentSweeperAndRefundTest extends TestCase
{
    use RefreshDatabase;

    private const CHECK_ENDPOINT = '*/api/payment-gateway/v1/payments/check-transaction-2';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

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
        $course = $attributes['course'] ?? Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $user = $attributes['user'] ?? User::factory()->create(['email_verified_at' => now()]);
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

    private function fakeCheck(string $status, float $amount = 35.00): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response([
            'status' => ['code' => '0', 'message' => 'success'],
            'data' => [
                'payment_status' => $status,
                'payment_amount' => $amount,
                'payment_currency' => 'USD',
                'apv' => '123456',
            ],
        ])]);
    }

    /* --------------------------------------------------------- the sweeper */

    /** The whole reason it exists. */
    public function test_the_sweeper_unlocks_a_payment_whose_callback_never_arrived(): void
    {
        $payment = $this->payment();
        $this->fakeCheck('APPROVED');

        $this->artisan('payway:recheck')->assertSuccessful();

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertTrue($payment->user->isEnrolledIn($payment->course));
    }

    public function test_the_sweeper_leaves_an_unpaid_payment_alone(): void
    {
        $payment = $this->payment();
        $this->fakeCheck('PENDING');

        $this->artisan('payway:recheck')->assertSuccessful();

        $payment->refresh();
        $this->assertSame('pending', $payment->status);
        $this->assertFalse($payment->user->isEnrolledIn($payment->course));
    }

    public function test_the_sweeper_sends_the_receipt_for_what_it_rescues(): void
    {
        $this->payment();
        $this->fakeCheck('APPROVED');

        $this->artisan('payway:recheck')->assertSuccessful();

        Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
    }

    public function test_the_sweeper_ignores_payments_older_than_its_window(): void
    {
        // created_at is not fillable, so it has to be pushed back afterwards.
        $old = $this->payment();
        $old->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        Http::fake();

        $this->artisan('payway:recheck --hours=24')->assertSuccessful();

        // Nothing in range, so PayWay is never called at all.
        Http::assertNothingSent();
        $this->assertSame('pending', $old->fresh()->status);
    }

    /** ...but a wider window does pick it up, so nothing is stranded forever. */
    public function test_a_wider_window_still_reaches_an_older_payment(): void
    {
        $old = $this->payment();
        $old->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $this->fakeCheck('APPROVED');

        $this->artisan('payway:recheck --hours=240')->assertSuccessful();

        $this->assertTrue($old->fresh()->isPaid());
    }

    /**
     * Bakong rows have no PayWay transaction. Handing one to PayWay's
     * check-transaction endpoint is exactly the mix-up the gateway tag prevents.
     */
    public function test_the_sweeper_never_asks_payway_about_a_legacy_bakong_payment(): void
    {
        $this->payment(['gateway_response' => ['gateway' => 'bakong'], 'md5' => 'abc123']);
        Http::fake();

        $this->artisan('payway:recheck')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_the_sweeper_survives_payway_being_unreachable(): void
    {
        $payment = $this->payment();
        Http::fake([self::CHECK_ENDPOINT => Http::response('gateway down', 500)]);

        // A dead gateway must not make the scheduled command fail noisily.
        $this->artisan('payway:recheck')->assertSuccessful();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_the_sweeper_reports_a_broken_configuration_instead_of_running(): void
    {
        config()->set('services.payway.merchant_id', '');
        Http::fake();

        $this->artisan('payway:recheck')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_pretend_mode_changes_nothing(): void
    {
        $payment = $this->payment();
        $this->fakeCheck('APPROVED');

        $this->artisan('payway:recheck --pretend')->assertSuccessful();

        $this->assertSame('pending', $payment->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_the_sweeper_respects_its_limit(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->payment();
        }
        $this->fakeCheck('PENDING');

        $this->artisan('payway:recheck --limit=2')->assertSuccessful();

        Http::assertSentCount(2);
    }

    /* ---------------------------------------------------------- the refund */

    public function test_a_refund_closes_the_course_but_keeps_the_payment(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        app(PayWayVerifier::class)->markRefunded($payment);

        $payment->refresh();
        $this->assertSame('refunded', $payment->status);
        $this->assertNotNull($payment->refunded_at);
        // The money record survives; the access does not.
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount' => 35.00]);
        $this->assertFalse($buyer->fresh()->isEnrolledIn($course));
    }

    /**
     * Progress is keyed on the user and the lesson, not the enrollment, so a
     * refunded student who buys again picks up where they left off.
     */
    public function test_a_refund_keeps_their_lesson_progress(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $module = $course->modules()->create(['title_en' => 'M1', 'title_km' => 'M1', 'position' => 1]);
        $lesson = $module->lessons()->create(['title_en' => 'L1', 'title_km' => 'L1', 'position' => 1]);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        LessonCompletion::create(['user_id' => $buyer->id, 'lesson_id' => $lesson->id, 'completed_at' => now()]);

        app(PayWayVerifier::class)->markRefunded($payment);

        $this->assertDatabaseHas('lesson_completions', ['user_id' => $buyer->id, 'lesson_id' => $lesson->id]);
    }

    public function test_refunding_twice_is_harmless(): void
    {
        $payment = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        $verifier = app(PayWayVerifier::class);

        $this->assertTrue($verifier->markRefunded($payment));
        $first = $payment->fresh()->refunded_at;

        $this->assertFalse($verifier->markRefunded($payment->fresh()));
        $this->assertEquals($first, $payment->fresh()->refunded_at);
    }

    public function test_a_refunded_student_can_no_longer_open_the_lesson(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $module = $course->modules()->create(['title_en' => 'M1', 'title_km' => 'M1', 'position' => 1]);
        $lesson = $module->lessons()->create(['title_en' => 'L1', 'title_km' => 'L1', 'position' => 1]);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        // Before: they are in.
        $this->actingAs($buyer)->get(route('courses.lessons.show', [$course, $lesson]))->assertOk();

        app(PayWayVerifier::class)->markRefunded($payment);

        // After: the gate is closed again.
        $this->actingAs($buyer)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertRedirect(route('courses.show', $course));
    }

    /** PayWay is the authority, here as everywhere else. */
    public function test_a_callback_reporting_a_refund_closes_the_course(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->fakeCheck('REFUNDED');

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['state' => 'refunded']);

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertFalse($buyer->fresh()->isEnrolledIn($course));
    }

    /**
     * Checking for refunds must not make PayWay's retries expensive.
     *
     * A paid row is re-asked about so a refund can be noticed, but PayWay retries
     * this callback within moments of the payment, and asking every time would
     * turn one confirmation into a question per retry. The first one asks; the
     * storm behind it does not.
     */
    public function test_a_retry_storm_on_a_paid_payment_asks_payway_only_once(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer]);
        $this->fakeCheck('APPROVED');

        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
                ->assertOk()
                ->assertJson(['state' => 'paid']);
        }

        // One question, one enrollment, one receipt.
        Http::assertSentCount(1);
        $this->assertSame(1, Enrollment::query()
            ->where('user_id', $buyer->id)->where('course_id', $course->id)->count());
        Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
    }

    /**
     * ...and a retry that arrives while the payment is still pending IS asked
     * about every time, because the answer may have become APPROVED since.
     */
    public function test_retries_on_a_still_pending_payment_are_never_throttled(): void
    {
        $payment = $this->payment();
        $this->fakeCheck('PENDING');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();
        }

        Http::assertSentCount(3);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    /** A normal repeat callback on a paid payment must not disturb anything. */
    public function test_a_callback_on_a_still_paid_payment_leaves_it_alone(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->fakeCheck('APPROVED');

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['state' => 'paid']);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
    }

    public function test_the_refund_command_refuses_when_payway_still_says_paid(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->fakeCheck('APPROVED');

        $this->artisan('payments:refund '.$payment->invoice_no)->assertFailed();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
    }

    public function test_the_refund_command_records_it_when_payway_confirms(): void
    {
        $payment = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        $this->fakeCheck('REFUNDED');

        $this->artisan('payments:refund '.$payment->invoice_no)->assertSuccessful();

        $this->assertSame('refunded', $payment->fresh()->status);
    }

    public function test_the_refund_command_will_not_touch_an_unpaid_payment(): void
    {
        $payment = $this->payment();
        Http::fake();

        $this->artisan('payments:refund '.$payment->invoice_no)->assertFailed();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_the_refund_command_reports_an_unknown_invoice(): void
    {
        $this->artisan('payments:refund PDNOSUCHINVOICE')->assertFailed();
    }

    /* -------------------------------------------------- admin refund button */

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    /** @return array{0: Payment, 1: Course, 2: User} */
    private function paidWithAccess(): array
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        return [$payment, $course, $buyer];
    }

    public function test_the_admin_refund_button_closes_the_course_when_payway_confirms(): void
    {
        [$payment, $course, $buyer] = $this->paidWithAccess();
        $this->fakeCheck('REFUNDED');

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund', $payment))
            ->assertRedirect();

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertFalse($buyer->fresh()->isEnrolledIn($course));
    }

    /** The guard that stops a misclick cutting off a paying student. */
    public function test_the_admin_refund_button_changes_nothing_when_payway_still_says_paid(): void
    {
        [$payment, $course, $buyer] = $this->paidWithAccess();
        $this->fakeCheck('APPROVED');

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund', $payment))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
    }

    public function test_an_unreachable_payway_changes_nothing_and_says_so(): void
    {
        [$payment, $course, $buyer] = $this->paidWithAccess();
        Http::fake([self::CHECK_ENDPOINT => Http::response('gateway down', 500)]);

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund', $payment))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
    }

    public function test_the_admin_refund_button_refuses_an_unpaid_payment(): void
    {
        $payment = $this->payment();
        Http::fake();

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund', $payment))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_refunding_an_already_refunded_payment_is_refused(): void
    {
        $payment = $this->payment(['status' => 'refunded', 'paid_at' => now(), 'refunded_at' => now()->subDay()]);
        Http::fake();

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund', $payment))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    /* ------------------------------------------- the force override ("anyway") */

    public function test_the_override_needs_the_invoice_number_typed_correctly(): void
    {
        [$payment, $course, $buyer] = $this->paidWithAccess();

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund-anyway', $payment), ['confirm_invoice' => 'WRONG'])
            ->assertSessionHas('error');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
    }

    /** A confirmation that only exists in the browser is not a confirmation. */
    public function test_the_override_refuses_a_missing_confirmation_even_though_the_dialog_is_client_side(): void
    {
        [$payment, , $buyer] = $this->paidWithAccess();

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund-anyway', $payment))
            ->assertSessionHas('error');

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_the_override_works_with_the_right_invoice_and_never_asks_payway(): void
    {
        [$payment, $course, $buyer] = $this->paidWithAccess();
        Http::fake();

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund-anyway', $payment), ['confirm_invoice' => $payment->invoice_no])
            ->assertRedirect();

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertFalse($buyer->fresh()->isEnrolledIn($course));
        Http::assertNothingSent();
    }

    public function test_the_override_still_refuses_an_unpaid_payment(): void
    {
        $payment = $this->payment();

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.refund-anyway', $payment), ['confirm_invoice' => $payment->invoice_no])
            ->assertSessionHas('error');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /* ------------------------------------------------------- who may refund */

    public function test_a_student_cannot_refund_anything(): void
    {
        [$payment, $course, $buyer] = $this->paidWithAccess();
        Http::fake();

        $this->actingAs($buyer)->patch(route('admin.payments.refund', $payment))->assertForbidden();
        $this->actingAs($buyer)
            ->patch(route('admin.payments.refund-anyway', $payment), ['confirm_invoice' => $payment->invoice_no])
            ->assertForbidden();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
        Http::assertNothingSent();
    }

    public function test_a_guest_cannot_refund_anything(): void
    {
        [$payment] = $this->paidWithAccess();

        $this->patch(route('admin.payments.refund', $payment))->assertRedirect(route('login'));
        $this->assertSame('paid', $payment->fresh()->status);
    }

    /** The page must not offer a refund on a row that cannot have one. */
    public function test_the_refund_button_is_only_offered_on_paid_rows(): void
    {
        $paid = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        $pending = $this->payment();

        $html = $this->actingAs($this->admin())->get(route('admin.payments'))->getContent();

        $this->assertStringContainsString(route('admin.payments.refund', $paid), $html);
        $this->assertStringNotContainsString(route('admin.payments.refund', $pending), $html);
    }

    /* ------------------------------------------------------- admin surface */

    public function test_a_refunded_payment_shows_in_the_admin_book_with_its_own_tab(): void
    {
        $payment = $this->payment(['status' => 'refunded', 'paid_at' => now(), 'refunded_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.payments', ['status' => 'refunded']))
            ->assertOk()
            ->assertSee($payment->invoice_no)
            ->assertSee('Refunded');
    }

    /** Refunded money must not still be counted as revenue. */
    public function test_a_refunded_payment_is_not_counted_in_the_totals(): void
    {
        $this->payment(['status' => 'paid', 'paid_at' => now(), 'amount' => 20.00]);
        $this->payment(['status' => 'refunded', 'paid_at' => now(), 'refunded_at' => now(), 'amount' => 999.00]);
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('$20.00')
            ->assertDontSee('$1,019.00');
    }
}
