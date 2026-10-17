<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

/**
 * A payment gateway's error text is written for whoever runs the server: it
 * names .env keys, the API host, hash failures, merchant ids and whitelisting.
 * A paying customer must never see any of it.
 *
 * These tests run with APP_DEBUG=true on purpose -- that is the setting the
 * project ships with, and the old code leaked precisely when it was on.
 *
 * The Bakong strings stay in the list although that integration has been
 * removed: a string that must never be shown does not stop mattering when the
 * code that produced it is retired, and a stale value can still be sitting in
 * an .env somewhere.
 */
class PaymentErrorPrivacyTest extends TestCase
{
    use RefreshDatabase;

    /** Fragments that must never reach a customer's screen. */
    private const SECRETS = [
        // ABA PayWay
        'PAYWAY_MERCHANT_ID',
        'PAYWAY_API_KEY',
        'PAYWAY_BASE_URL',
        'PAYWAY_CALLBACK_URL',
        'payway.com.kh',
        'generate-qr',
        'check-transaction',
        'Invalid hash',
        'whitelist',
        'hmac',
        // Bakong (the retired gateway -- see the note above)
        'BAKONG_API_TOKEN',
        'BAKONG_ACCOUNT_ID',
        'BAKONG_API_URL',
        'api-bakong.nbc.gov.kh',
        // Generic operator-facing detail
        'artisan',
        'config:clear',
        '.env',
        'Unauthorized',
        'HTTP 403',
        'HTTP 500',
        'vendor/',
        'SQLSTATE',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // The leak used to appear exactly here, so pin the risky setting on.
        config(['app.debug' => true]);

        config()->set('services.payway', [
            'base_url' => 'https://checkout-sandbox.payway.com.kh',
            'merchant_id' => 'test_merchant',
            'api_key' => 'test_api_key',
            'callback_url' => 'https://tunnel.example.com/payway/callback',
            'currency' => 'USD',
            'qr_lifetime' => 10,
            'timeout' => 15,
        ]);

        Log::spy();
    }

    private function paidCourse(): Course
    {
        return Course::factory()->create([
            'title_en' => 'Adobe Illustrator Beginner',
            'price' => 35,
            'access_type' => 'paid',
        ]);
    }

    private function student(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function assertNoSecretsIn(string $text, string $where): void
    {
        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsStringIgnoringCase(
                $secret,
                $text,
                "Technical gateway detail \"{$secret}\" leaked into {$where}"
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Checkout */
    /* ------------------------------------------------------------------ */

    public function test_a_misconfigured_gateway_shows_the_customer_nothing_technical(): void
    {
        Http::fake();

        // The kind of thing configProblems() says, and must never say out loud.
        config()->set('services.payway.api_key', '');
        config()->set('services.payway.merchant_id', '');

        $response = $this->actingAs($this->student())
            ->get(route('payments.checkout', $course = $this->paidCourse()));

        $response->assertRedirect(route('courses.show', $course));

        $status = (string) session('status');

        $this->assertSame('Online payment is temporarily unavailable. Please try again in a few minutes.', $status);
        $this->assertNoSecretsIn($status, 'the checkout status message');
    }

    public function test_a_rejected_api_key_shows_the_customer_nothing_technical(): void
    {
        // PayWay's own wording for a bad hash names the API key.
        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => [
                    'code' => '1',
                    'message' => 'Invalid hash — check PAYWAY_API_KEY, computed with hmac over the request.',
                ],
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $this->actingAs($this->student())
            ->get(route('payments.checkout', $course = $this->paidCourse()))
            ->assertRedirect(route('courses.show', $course));

        $this->assertNoSecretsIn((string) session('status'), 'the checkout status message');
    }

    public function test_a_non_whitelisted_server_shows_the_customer_nothing_technical(): void
    {
        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => [
                    'code' => '6',
                    'message' => 'Your server IP is not whitelisted at checkout-sandbox.payway.com.kh',
                ],
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $this->actingAs($this->student())
            ->get(route('payments.checkout', $course = $this->paidCourse()))
            ->assertRedirect(route('courses.show', $course));

        $this->assertNoSecretsIn((string) session('status'), 'the checkout status message');
    }

    /** An HTML error page instead of JSON: the message carries the URL and body. */
    public function test_a_gateway_serving_html_shows_the_customer_nothing_technical(): void
    {
        Http::fake([
            '*/generate-qr' => Http::response(
                '<html><body><h1>403 Forbidden</h1><p>vendor/nginx</p></body></html>',
                403
            ),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $this->actingAs($this->student())
            ->get(route('payments.checkout', $course = $this->paidCourse()))
            ->assertRedirect(route('courses.show', $course));

        $this->assertNoSecretsIn((string) session('status'), 'the checkout status message');
    }

    /* ------------------------------------------------------------------ */
    /* The status endpoint the page polls */
    /* ------------------------------------------------------------------ */

    public function test_the_status_endpoint_hides_gateway_api_errors(): void
    {
        Http::fake([
            '*/check-transaction-2' => Http::response(
                '<html>403 Forbidden — check-transaction blocked at payway.com.kh</html>',
                403
            ),
        ]);

        $user = $this->student();
        $payment = $this->pendingPayment($user, $this->paidCourse());

        $response = $this->actingAs($user)->postJson(route('payments.check', $payment));

        $response->assertOk()->assertJson(['state' => 'error', 'paid' => false]);

        $body = $response->getContent();

        $this->assertNoSecretsIn($body, 'the payment status response');
        $this->assertStringContainsString('Still checking your payment', $body);
    }

    public function test_the_status_endpoint_hides_unexpected_errors(): void
    {
        // A connection failure: Laravel's exception text names the host.
        Http::fake([
            '*/check-transaction-2' => fn () => throw new \RuntimeException(
                'SQLSTATE[HY000]: General error in /var/www/vendor/laravel/framework/src/Database.php'
            ),
        ]);

        $user = $this->student();
        $payment = $this->pendingPayment($user, $this->paidCourse());

        $response = $this->actingAs($user)->postJson(route('payments.check', $payment));

        $response->assertOk()->assertJson(['state' => 'error', 'paid' => false]);

        $body = $response->getContent();

        $this->assertNoSecretsIn($body, 'the payment status response');
        $this->assertStringNotContainsString('/var/www', $body);
    }

    /* ------------------------------------------------------------------ */
    /* ...but the operator must still be able to find out */
    /* ------------------------------------------------------------------ */

    public function test_the_technical_reason_is_still_written_to_the_log(): void
    {
        Http::fake();
        config()->set('services.payway.api_key', '');

        $this->actingAs($this->student())->get(route('payments.checkout', $this->paidCourse()));

        // Hidden from the customer, but the operator must still be able to fix it.
        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $context = []) {
                return $message === 'ABA PayWay is not configured'
                    && collect($context['problems'] ?? [])
                        ->contains(fn ($problem) => str_contains((string) $problem, 'PAYWAY_API_KEY'));
            })
            ->once();
    }

    public function test_the_callback_payload_is_logged_in_full(): void
    {
        Http::fake([
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'APPROVED', 'payment_amount' => '35.00', 'payment_currency' => 'USD'],
            ]),
        ]);

        $payment = $this->pendingPayment($this->student(), $this->paidCourse());

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        // Whatever PayWay sent has to be recoverable afterwards: when a payment
        // goes wrong this is often the only record of it.
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'PayWay callback received'
                && ($context['payload']['tran_id'] ?? null) === $payment->invoice_no)
            ->once();
    }

    /**
     * Negative control: proves the guard above is not vacuous. If this ever
     * fails, assertNoSecretsIn() has stopped detecting anything and the other
     * tests in this file are worthless.
     */
    public function test_the_leak_detector_actually_catches_a_leak(): void
    {
        $realGatewayError = 'PayWay QR error [1]: Invalid hash — check PAYWAY_API_KEY '
            .'(the "Public Key" from ABA) at checkout-sandbox.payway.com.kh, then run: php artisan config:clear';

        $caught = false;

        try {
            $this->assertNoSecretsIn($realGatewayError, 'a deliberately planted leak');
        } catch (AssertionFailedError) {
            $caught = true;
        }

        $this->assertTrue($caught, 'assertNoSecretsIn() failed to detect real gateway error text');
    }

    public function test_the_controller_has_no_debug_gated_message_path_left(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PaymentController.php'));

        // The old leak was a showDetails() gate that turned on with APP_DEBUG.
        $this->assertStringNotContainsString('showDetails', $source);
        $this->assertStringNotContainsString("config('app.debug')", $source);
        $this->assertStringNotContainsString("environment('local')", $source);
    }

    /**
     * The customer-facing message must not be assembled from the exception, in
     * any gateway path. One argument-free helper is the whole point.
     */
    public function test_the_unavailable_message_takes_no_detail_argument(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PaymentController.php'));

        $this->assertMatchesRegularExpression(
            '/private function paymentUnavailable\(Course \$course\)/',
            $source,
            'paymentUnavailable() grew a parameter — gateway text could now be passed through to the customer.'
        );
    }

    private function pendingPayment(User $user, Course $course): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => 'PD'.fake()->unique()->numerify('##########'),
            'amount' => 35.00,
            'currency' => 'USD',
            'qr' => 'qr-payload',
            'qr_image' => base64_encode('fake-png'),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ]);
    }
}
