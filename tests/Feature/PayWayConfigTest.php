<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\PayWayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PAYWAY_CALLBACK_URL has to be OUR address, not PayWay's.
 *
 * This was a real outage and a quiet one. The callback URL had one of PayWay's
 * own API endpoints pasted into it, so PayWay posted every confirmation back to
 * itself and this app was never told a payment had happened. Nothing looked
 * wrong: QR generation still worked, check-transaction still answered, and the
 * self-test printed ".env settings look complete". Courses only unlocked while
 * the customer sat on the payment page waiting for the 20-second polling
 * fallback, and a customer who closed the page stayed locked out after paying.
 *
 * So the setting is validated rather than trusted, and that validation is
 * pinned here.
 */
class PayWayConfigTest extends TestCase
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

    private function problems(string $callbackUrl): array
    {
        config()->set('services.payway.callback_url', $callbackUrl);

        return (new PayWayService)->configProblems();
    }

    private function warnings(string $callbackUrl): array
    {
        config()->set('services.payway.callback_url', $callbackUrl);

        return (new PayWayService)->configWarnings();
    }

    public function test_the_exact_endpoint_that_caused_the_outage_is_refused(): void
    {
        $problems = $this->problems('https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase');

        $this->assertNotEmpty($problems, 'A callback URL pointing at PayWay must be reported as a problem.');
        $this->assertStringContainsString('points at PayWay', implode(' ', $problems));
    }

    /**
     * Whatever base_url is set to, and whichever PayWay host is pasted in, the
     * answer has to be the same: that is not our address.
     */
    public function test_any_payway_host_is_refused_as_a_callback(): void
    {
        $urls = [
            'sandbox gateway root' => 'https://checkout-sandbox.payway.com.kh',
            'live gateway' => 'https://checkout.payway.com.kh/api/payment-gateway/v1/payments/purchase',
            'live gateway root' => 'https://checkout.payway.com.kh/',
            'some other payway subdomain' => 'https://api.payway.com.kh/payway/callback',
            'uppercased host' => 'https://CHECKOUT.PAYWAY.COM.KH/whatever',
        ];

        foreach ($urls as $label => $url) {
            $this->assertNotEmpty(
                $this->problems($url),
                "A callback URL on PayWay's own domain must be refused ($label: $url)."
            );
        }
    }

    public function test_a_real_public_callback_url_is_accepted_without_complaint(): void
    {
        $this->assertSame([], $this->problems('https://tunnel.example.com/payway/callback'));
        $this->assertSame([], $this->warnings('https://tunnel.example.com/payway/callback'));
    }

    /** The app answers callbacks on exactly one path, so any other path is flagged. */
    public function test_the_right_host_on_the_wrong_path_is_warned_about(): void
    {
        $warnings = $this->warnings('https://tunnel.example.com/webhook');

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('/payway/callback', implode(' ', $warnings));

        // A trailing slash is the same path, so it must not be nagged about.
        $this->assertSame([], $this->warnings('https://tunnel.example.com/payway/callback/'));
    }

    /** Still true, and still only a warning: local dev has the polling fallback. */
    public function test_a_local_callback_is_a_warning_not_a_hard_error(): void
    {
        $url = 'http://127.0.0.1:8000/payway/callback';

        $this->assertSame([], $this->problems($url));
        $this->assertStringContainsString('cannot reach it', implode(' ', $this->warnings($url)));
    }

    /**
     * The payment page must refuse to open rather than hand a customer a QR
     * whose confirmation would go to PayWay instead of us.
     */
    public function test_checkout_refuses_to_sell_when_the_callback_points_at_payway(): void
    {
        Http::fake();

        config()->set(
            'services.payway.callback_url',
            'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'
        );

        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('payments.checkout', $course));

        $response->assertRedirect(route('courses.show', $course));
        // No QR was requested, so nothing was charged and no payment row exists.
        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
        $this->assertFalse($user->fresh()->isEnrolledIn($course));
    }

    /**
     * The problem list names .env keys, so it belongs in the log. The customer
     * gets an apology and nothing else.
     */
    public function test_the_customer_is_never_shown_the_configuration_detail(): void
    {
        Http::fake();

        config()->set(
            'services.payway.callback_url',
            'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'
        );

        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->get(route('payments.checkout', $course));

        $response->assertOk();
        $response->assertDontSee('PAYWAY_CALLBACK_URL');
        $response->assertDontSee('payment-gateway');
        $response->assertDontSee('points at PayWay');
    }
}
