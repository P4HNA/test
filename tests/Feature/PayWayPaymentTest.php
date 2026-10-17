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
 * ABA PayWay KHQR, end to end with PayWay itself faked.
 *
 * The thing being protected here is simple to state and easy to get wrong: a
 * course opens if and only if PayWay's own check-transaction endpoint says
 * APPROVED for the right amount, and it opens exactly once however many times
 * we are told about it.
 */
class PayWayPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const QR_ENDPOINT = '*/api/payment-gateway/v1/payments/generate-qr';

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

        // The 20-second fallback gate lives in the cache; a leftover key from a
        // previous test would silently skip the check under test.
        Cache::flush();
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    private function paidCourse(float $price = 35.00): Course
    {
        return Course::factory()->create([
            'title_en' => 'Adobe Illustrator Beginner',
            'price' => $price,
            'access_type' => 'paid',
        ]);
    }

    private function student(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    /** The shape PayWay's generate-qr returns on success. */
    private function fakeQrResponse(): array
    {
        return [
            'status' => ['code' => '0', 'message' => 'success'],
            'qrString' => '00020101021230560016abaakhppxxx@abaa',
            'qrImage' => base64_encode('fake-png-bytes'),
            'abapay_deeplink' => 'https://link.payway.com.kh/aba?id=TEST123',
        ];
    }

    /** The shape check-transaction-2 returns for a given status. */
    private function fakeCheckResponse(string $status, ?string $amount = '35.00', string $currency = 'USD'): array
    {
        return [
            'status' => ['code' => '0', 'message' => 'success'],
            'data' => array_filter([
                'payment_status' => $status,
                'payment_amount' => $amount,
                'payment_currency' => $currency,
                'apv' => 'APV'.$status,
            ], fn ($value) => $value !== null),
        ];
    }

    /** A pending PayWay QR, as checkout would have created it. */
    private function pendingQr(User $user, Course $course, string $invoiceNo = 'PD260926120000AAAA'): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => $invoiceNo,
            'amount' => round((float) $course->price, 2),
            'currency' => 'USD',
            'qr' => '00020101021230560016abaakhppxxx@abaa',
            'qr_image' => base64_encode('fake-png-bytes'),
            'deeplink' => 'https://link.payway.com.kh/aba?id=TEST123',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 1. QR creation */
    /* ------------------------------------------------------------------ */

    public function test_checkout_creates_a_payway_qr_for_the_course_price(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();

        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertOk()
            ->assertViewIs('payments.checkout');

        $payment = Payment::query()->where('user_id', $user->id)->sole();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('35.00', (string) $payment->amount);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame(PayWayVerifier::GATEWAY, $payment->gateway_response['gateway']);
        $this->assertNotEmpty($payment->qr);
        $this->assertNotEmpty($payment->qr_image);
        $this->assertSame('https://link.payway.com.kh/aba?id=TEST123', $payment->deeplink);
        // tran_id has to fit PayWay's column and its charset.
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{1,20}$/', $payment->invoice_no);
    }

    public function test_the_qr_request_is_signed_over_every_field_in_payways_order(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'generate-qr')) {
                return false;
            }

            $body = $request->data();

            // The callback URL travels base64-encoded, per PayWay's spec.
            $this->assertSame(
                base64_encode('https://tunnel.example.com/payway/callback'),
                $body['callback_url']
            );
            $this->assertSame('abapay_khqr', $body['payment_option']);
            $this->assertSame('purchase', $body['purchase_type']);
            $this->assertSame('template3_color', $body['qr_image_template']);
            $this->assertSame('35.00', $body['amount']);
            // PayWay's minimum lifetime is 3 minutes.
            $this->assertGreaterThanOrEqual(3, (int) $body['lifetime']);

            // Rebuild the hash the way PayWay does: the documented field order,
            // with anything we left out counting as the empty string.
            $order = [
                'req_time', 'merchant_id', 'tran_id', 'amount', 'items',
                'first_name', 'last_name', 'email', 'phone', 'purchase_type',
                'payment_option', 'callback_url', 'return_deeplink', 'currency',
                'custom_fields', 'return_params', 'payout', 'lifetime',
                'qr_image_template',
            ];

            $concatenated = '';

            foreach ($order as $field) {
                $concatenated .= (string) ($body[$field] ?? '');
            }

            $expected = base64_encode(hash_hmac('sha512', $concatenated, 'test_api_key', true));

            $this->assertSame($expected, $body['hash'], 'The QR request hash does not match PayWay\'s field order.');

            return true;
        });
    }

    public function test_an_unexpired_pending_qr_is_reused_instead_of_making_another(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();
        $first = Payment::query()->where('user_id', $user->id)->sole();

        // Second visit: the QR they are holding is still good.
        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();

        $this->assertSame(1, Payment::query()->where('user_id', $user->id)->count());
        $this->assertSame($first->invoice_no, Payment::query()->sole()->invoice_no);

        Http::assertSentCount(2); // one generate-qr + one fallback check. Not two QRs.
    }

    public function test_a_stale_price_is_not_reused(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $this->pendingQr($user, $course);

        // The course got more expensive after that QR was minted.
        $course->update(['price' => 45.00]);

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();

        $this->assertSame(2, Payment::query()->where('user_id', $user->id)->count());
        $this->assertSame('45.00', (string) Payment::query()->latest('id')->first()->amount);
    }

    public function test_the_price_comes_from_the_database_not_the_request(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();

        // Anything a customer could try to smuggle in has to be ignored.
        $this->actingAs($user)
            ->get(route('payments.checkout', $course).'?amount=0.01&price=0.01')
            ->assertOk();

        $this->assertSame('35.00', (string) Payment::query()->sole()->amount);

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'generate-qr')
            || $request->data()['amount'] === '35.00');
    }

    /* ------------------------------------------------------------------ */
    /* 2. An approved callback */
    /* ------------------------------------------------------------------ */

    public function test_an_approved_callback_marks_the_payment_paid_and_enrols_the_student(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        // No session, no CSRF token, no auth: exactly how PayWay calls us.
        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'state' => 'paid']);

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertTrue($user->fresh()->isEnrolledIn($course));

        // The callback is never believed on its own.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'check-transaction-2'));
    }

    public function test_the_callback_hash_is_signed_over_req_time_merchant_and_tran_id(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $payment = $this->pendingQr($this->student(), $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'check-transaction-2')) {
                return false;
            }

            $body = $request->data();
            $expected = base64_encode(hash_hmac(
                'sha512',
                $body['req_time'].$body['merchant_id'].$body['tran_id'],
                'test_api_key',
                true
            ));

            $this->assertSame($expected, $body['hash']);

            return true;
        });
    }

    public function test_the_transaction_id_is_read_from_any_of_the_three_field_names(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        foreach (['tran_id', 'merchant_ref', 'transaction_id'] as $index => $field) {
            $course = $this->paidCourse(35.00);
            $user = $this->student();
            $payment = $this->pendingQr($user, $course, 'PD26092612000'.$index.'AAAA');

            $this->postJson(route('payway.callback'), [$field => $payment->invoice_no])
                ->assertOk()
                ->assertJson(['state' => 'paid']);

            $this->assertSame('paid', $payment->fresh()->status, "Field {$field} was not read.");
        }
    }

    public function test_the_callback_needs_no_csrf_token(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        $payment = $this->pendingQr($this->student(), $this->paidCourse(35.00));

        // A form POST with no token at all. Any other POST route would 419 here.
        $this->post(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
    }

    /* ------------------------------------------------------------------ */
    /* 3. A callback that is not approved */
    /* ------------------------------------------------------------------ */

    public function test_a_declined_callback_does_not_unlock_the_course(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('DECLINED', '35.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['state' => 'declined']);

        $this->assertSame('declined', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
        $this->assertSame(0, Enrollment::query()->count());
    }

    public function test_a_pending_callback_leaves_the_payment_pending(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING', null)),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['state' => 'pending']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
    }

    /**
     * The one that matters most: a forged callback for a transaction PayWay has
     * never heard of must achieve nothing. This is why the callback is allowed
     * to be unauthenticated at all.
     */
    public function test_a_forged_callback_for_an_unpaid_transaction_unlocks_nothing(): void
    {
        Http::fake([
            // PayWay does not know this transaction.
            self::CHECK_ENDPOINT => Http::response(['status' => ['code' => '11', 'message' => 'Transaction not found']]),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), [
            'tran_id' => $payment->invoice_no,
            // A hopeful attacker adding the fields they wish were trusted.
            'payment_status' => 'APPROVED',
            'status' => 'APPROVED',
            'amount' => '35.00',
        ])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
    }

    public function test_a_callback_for_an_unknown_transaction_is_ignored(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED'))]);

        $this->postJson(route('payway.callback'), ['tran_id' => 'PDNOSUCHINVOICE'])
            ->assertOk()
            ->assertJson(['state' => 'unknown']);

        // No outgoing call: there was nothing to ask about.
        Http::assertNothingSent();
    }

    public function test_a_callback_with_no_transaction_id_is_ignored_without_erroring(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED'))]);

        // 200, not 4xx: PayWay retries on an error status, and a retry cannot
        // put an id into a body that never had one.
        $this->postJson(route('payway.callback'), ['something_else' => 'x'])
            ->assertOk()
            ->assertJson(['state' => 'ignored']);

        Http::assertNothingSent();
    }

    public function test_a_callback_naming_a_legacy_bakong_invoice_is_ignored(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED'))]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();

        $payment = $this->pendingQr($user, $course);
        // A row from the old integration: PayWay has no such transaction.
        $payment->forceFill([
            'md5' => str_repeat('a', 32),
            'gateway_response' => ['gateway' => 'bakong-individual'],
        ])->save();

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['state' => 'ignored']);

        $this->assertSame('pending', $payment->fresh()->status);
        Http::assertNothingSent();
    }

    /* ------------------------------------------------------------------ */
    /* 4. Amount mismatch */
    /* ------------------------------------------------------------------ */

    public function test_an_underpaid_transaction_is_held_for_review_and_unlocks_nothing(): void
    {
        Http::fake([
            // APPROVED, but for a dollar.
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '1.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
            ->assertOk()
            ->assertJson(['state' => 'review']);

        $payment->refresh();

        $this->assertSame('review', $payment->status);
        $this->assertNull($payment->paid_at);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
        $this->assertSame(0, Enrollment::query()->count());

        // What went wrong is recorded on the row, so it can be settled by hand.
        $this->assertSame('35.00', $payment->gateway_response['amount_mismatch']['expected']);
    }

    public function test_an_overpaid_transaction_is_also_held_for_review(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '350.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->assertSame('review', $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
    }

    public function test_a_different_currency_is_held_for_review(): void
    {
        Http::fake([
            // The right number, the wrong money.
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00', 'KHR')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->assertSame('review', $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
    }

    public function test_a_rounding_difference_of_a_fraction_of_a_cent_still_pays(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.000')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($user->fresh()->isEnrolledIn($course));
    }

    /* ------------------------------------------------------------------ */
    /* 5. A duplicate callback enrols only once */
    /* ------------------------------------------------------------------ */

    public function test_a_repeated_callback_enrols_the_student_only_once(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        // PayWay retries its callback. Five times, say.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])
                ->assertOk()
                ->assertJson(['state' => 'paid']);
        }

        $this->assertSame(1, Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->count());

        $this->assertSame(1, Payment::query()->where('status', 'paid')->count());

        // Only the first callback needed to ask PayWay anything; the rest saw
        // the payment already settled and stopped there.
        Http::assertSentCount(1);
    }

    public function test_a_callback_racing_the_pages_own_poll_still_enrols_once(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        // The page polls and the callback lands, in whichever order.
        $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();
        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();
        $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();

        $this->assertSame(1, Enrollment::query()->count());
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_the_paid_row_keeps_a_single_enrollment_even_if_one_already_existed(): void
    {
        Http::fake([
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00')),
        ]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        // Enrolled already — by an admin, say, or a free-course promotion.
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()->subDay(),
        ]);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->assertSame(1, Enrollment::query()->count());
        $this->assertSame('paid', $payment->fresh()->status);
    }

    /* ------------------------------------------------------------------ */
    /* The status endpoint the page polls */
    /* ------------------------------------------------------------------ */

    public function test_the_status_endpoint_reads_the_database_without_calling_payway(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING'))]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        // First poll is allowed one fallback check...
        $this->actingAs($user)->postJson(route('payments.check', $payment))->assertOk();
        Http::assertSentCount(1);

        // ...and the next few, inside the 20-second window, must not add more.
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)
                ->postJson(route('payments.check', $payment))
                ->assertOk()
                ->assertJson(['state' => 'pending', 'paid' => false]);
        }

        Http::assertSentCount(1);
    }

    public function test_the_status_endpoint_reports_a_payment_the_callback_already_settled(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00'))]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->actingAs($user)
            ->postJson(route('payments.check', $payment))
            ->assertOk()
            ->assertJson([
                'state' => 'paid',
                'paid' => true,
                'redirect' => route('courses.show', $course),
            ]);

        // The callback made the only outgoing call.
        Http::assertSentCount(1);
    }

    public function test_the_status_endpoint_expires_a_dead_qr(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING'))]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        $payment = $this->pendingQr($user, $course);
        $payment->forceFill([
            'expires_at' => now()->subMinutes(PayWayVerifier::EXPIRY_GRACE_MINUTES + 5),
        ])->save();

        $this->actingAs($user)
            ->postJson(route('payments.check', $payment))
            ->assertOk()
            ->assertJson([
                'state' => 'expired',
                'paid' => false,
                'retry_url' => route('payments.checkout', $course),
            ]);

        $this->assertSame('expired', $payment->fresh()->status);
    }

    public function test_another_students_payment_cannot_be_checked(): void
    {
        $course = $this->paidCourse(35.00);
        $payment = $this->pendingQr($this->student(), $course);

        $this->actingAs($this->student())
            ->postJson(route('payments.check', $payment))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_check_a_payment(): void
    {
        $payment = $this->pendingQr($this->student(), $this->paidCourse(35.00));

        $this->postJson(route('payments.check', $payment))->assertUnauthorized();
    }

    /* ------------------------------------------------------------------ */
    /* Checkout guards */
    /* ------------------------------------------------------------------ */

    public function test_an_already_enrolled_student_is_sent_to_the_course(): void
    {
        Http::fake();

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('courses.show', $course));

        Http::assertNothingSent();
    }

    public function test_a_free_course_never_reaches_payway(): void
    {
        Http::fake();

        $course = Course::factory()->create(['price' => 0, 'access_type' => 'free']);

        $this->actingAs($this->student())
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('courses.show', $course));

        Http::assertNothingSent();
    }

    public function test_a_student_who_already_paid_is_unlocked_instead_of_charged_again(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00'))]);

        $course = $this->paidCourse(35.00);
        $user = $this->student();
        // They paid this QR and then closed the page before the callback landed.
        $this->pendingQr($user, $course);

        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('courses.show', $course));

        $this->assertTrue($user->fresh()->isEnrolledIn($course));
        // No new QR was minted.
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_missing_settings_show_the_customer_nothing_technical(): void
    {
        Http::fake();
        config()->set('services.payway.api_key', '');

        $course = $this->paidCourse(35.00);

        $response = $this->actingAs($this->student())
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('courses.show', $course));

        $message = (string) session('status');

        $this->assertStringNotContainsStringIgnoringCase('PAYWAY_', $message);
        $this->assertStringNotContainsStringIgnoringCase('api_key', $message);
        $this->assertStringNotContainsStringIgnoringCase('payway.com.kh', $message);
        $this->assertNotEmpty($message);

        Http::assertNothingSent();
    }

    public function test_a_payway_refusal_shows_the_customer_nothing_technical(): void
    {
        Http::fake([
            // Wrong hash: PayWay's own wording names the API key.
            self::QR_ENDPOINT => Http::response([
                'status' => ['code' => '1', 'message' => 'Invalid hash. Check your PAYWAY_API_KEY.'],
            ]),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);

        $this->actingAs($this->student())
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('courses.show', $course));

        $message = (string) session('status');

        $this->assertStringNotContainsStringIgnoringCase('PAYWAY_API_KEY', $message);
        $this->assertStringNotContainsStringIgnoringCase('hash', $message);
        $this->assertNotEmpty($message);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_an_unverified_student_cannot_start_a_payment(): void
    {
        Http::fake();

        $course = $this->paidCourse(35.00);
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertRedirect(route('verification.notice'));

        Http::assertNothingSent();
    }

    /**
     * ...but once their money has moved it must still be possible to record it.
     * Changing an email address resets email_verified_at, and that must not be
     * able to happen mid-payment and lose the payment.
     */
    public function test_an_unverified_student_can_still_have_their_payment_confirmed(): void
    {
        Http::fake([self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('APPROVED', '35.00'))]);

        $course = $this->paidCourse(35.00);
        $user = User::factory()->unverified()->create();
        $payment = $this->pendingQr($user, $course);

        $this->actingAs($user)
            ->postJson(route('payments.check', $payment))
            ->assertOk()
            ->assertJson(['paid' => true]);

        $this->assertTrue($user->fresh()->isEnrolledIn($course));
    }

    /* ------------------------------------------------------------------ */
    /* The payment page itself */
    /* ------------------------------------------------------------------ */

    public function test_the_payment_page_shows_the_qr_amount_course_and_deeplink(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);

        $response = $this->actingAs($this->student())
            ->get(route('payments.checkout', $course))
            ->assertOk();

        $payment = Payment::query()->sole();

        $response->assertSee('Adobe Illustrator Beginner');
        $response->assertSee('USD 35.00');
        $response->assertSee($payment->invoice_no);
        $response->assertSee('https://link.payway.com.kh/aba?id=TEST123', false);
        $response->assertSee('Open ABA Mobile');

        $html = $response->getContent();

        // The code is drawn server-side from the payload as a square inline SVG.
        // PayWay's own qr_image is a tall pre-branded KHQR card that does not fit
        // this page, so it is kept on the row but never shown.
        $this->assertStringContainsString('id="qrcode"', $html);
        $this->assertMatchesRegularExpression(
            '/id="qrcode".*?<svg[^>]*viewBox="0 0 260 260"/s',
            $html,
            'The QR is no longer a square 260x260 inline SVG.'
        );
        $this->assertStringNotContainsString('data:image/png;base64,'.$payment->qr_image, $html);
    }

    /**
     * The QR has to be square. PayWay's qr_image is a tall card, and forcing that
     * into the page's square frame squashes it into something unscannable and
     * ugly -- which is exactly what happened once.
     */
    public function test_the_qr_is_rendered_square_and_not_stretched(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $html = $this->actingAs($this->student())
            ->get(route('payments.checkout', $this->paidCourse(35.00)))
            ->assertOk()
            ->getContent();

        preg_match('/id="qrcode".*?(<svg[^>]*>)/s', $html, $m);

        $this->assertNotEmpty($m, 'No QR svg found on the payment page.');

        // Square in its own right, so the CSS never has to distort it to fit.
        $this->assertStringContainsString('width="260px"', $m[1]);
        $this->assertStringContainsString('height="260px"', $m[1]);
    }

    /**
     * The old page pulled a QR-drawing library off a public CDN, onto the one
     * page where someone is about to pay. PayWay renders the image for us, so
     * there is no longer any reason for a third-party script to be here at all.
     */
    public function test_the_payment_page_loads_no_third_party_scripts(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $html = $this->actingAs($this->student())
            ->get(route('payments.checkout', $this->paidCourse(35.00)))
            ->assertOk()
            ->getContent();

        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);

        $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        $this->assertNotEmpty($matches[1], 'No script tags found — the assertion below would be vacuous.');

        foreach ($matches[1] as $src) {
            $host = parse_url($src, PHP_URL_HOST);

            // A relative src (no host) is ours by definition; an absolute one
            // has to point at our own host. Vite emits the latter.
            $this->assertTrue(
                $host === null || $host === $ownHost,
                "The payment page loads an off-site script: {$src}"
            );
        }
    }

    public function test_the_page_polls_our_own_status_endpoint_every_three_seconds(): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response($this->fakeQrResponse()),
            self::CHECK_ENDPOINT => Http::response($this->fakeCheckResponse('PENDING')),
        ]);

        $course = $this->paidCourse(35.00);

        $html = $this->actingAs($this->student())
            ->get(route('payments.checkout', $course))
            ->assertOk()
            ->getContent();

        $payment = Payment::query()->sole();

        // The URL reaches the page through @js, which escapes the slashes, so
        // compare against the same encoding rather than the bare route.
        $this->assertStringContainsString(
            trim((string) json_encode(route('payments.check', $payment)), '"'),
            $html
        );
        $this->assertStringContainsString('POLL_MS: 3000', $html);
        // A POST, so the poll carries the session's CSRF token.
        $this->assertStringContainsString("method: 'POST'", $html);
        $this->assertStringContainsString('X-CSRF-TOKEN', $html);
    }
}
