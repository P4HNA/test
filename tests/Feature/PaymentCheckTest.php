<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The payment check is a POST, not a GET.
 *
 * It is not a read: it can flip the payment to paid and create the enrollment,
 * so it must not be reachable by a browser prefetch, a crawler, a cached
 * response or a pasted link.
 */
class PaymentCheckTest extends TestCase
{
    use RefreshDatabase;

    private function paidCourse(): Course
    {
        return Course::factory()->create([
            'title_en' => 'Adobe Illustrator Beginner',
            'price' => 35,
            'access_type' => 'paid',
        ]);
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
            'deeplink' => 'https://link.payway.com.kh/aba?id=TEST',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ]);
    }

    /** PayWay says "no payment yet", so the endpoint answers "pending". */
    private function fakePayWayPending(): void
    {
        config()->set('services.payway', [
            'base_url' => 'https://checkout-sandbox.payway.com.kh',
            'merchant_id' => 'test_merchant',
            'api_key' => 'test_api_key',
            'callback_url' => 'https://tunnel.example.com/payway/callback',
            'currency' => 'USD',
            'qr_lifetime' => 10,
            'timeout' => 15,
        ]);

        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '0'],
                'qrString' => 'qr-payload',
                'qrImage' => base64_encode('fake-png'),
                'abapay_deeplink' => 'https://link.payway.com.kh/aba?id=TEST',
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);
    }

    public function test_the_check_endpoint_no_longer_answers_get(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user, $this->paidCourse());

        $this->actingAs($user)
            ->get(route('payments.check', $payment))
            ->assertStatus(405);
    }

    public function test_the_check_endpoint_answers_post(): void
    {
        $this->fakePayWayPending();

        $user = User::factory()->create();
        $payment = $this->pendingPayment($user, $this->paidCourse());

        $this->actingAs($user)
            ->postJson(route('payments.check', $payment))
            ->assertOk()
            ->assertJson(['state' => 'pending', 'paid' => false]);
    }

    public function test_a_guest_cannot_check_a_payment(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user, $this->paidCourse());

        $this->post(route('payments.check', $payment))
            ->assertRedirect(route('login'));
    }

    public function test_another_students_payment_cannot_be_checked(): void
    {
        $owner = User::factory()->create();
        $payment = $this->pendingPayment($owner, $this->paidCourse());
        $someoneElse = User::factory()->create();

        $this->actingAs($someoneElse)
            ->postJson(route('payments.check', $payment))
            ->assertForbidden();
    }

    public function test_the_route_is_registered_as_post_only(): void
    {
        $route = collect(app('router')->getRoutes())
            ->first(fn ($r) => $r->getName() === 'payments.check');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('GET', $route->methods());
    }

    public function test_the_payment_page_polls_with_post_and_sends_the_csrf_token(): void
    {
        $this->fakePayWayPending();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $course = $this->paidCourse();

        $html = $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertOk()
            ->getContent();

        // The page must carry a token and hand it to the poll as a POST.
        $this->assertStringContainsString('name="csrf-token"', $html);
        $this->assertMatchesRegularExpression("/method:\s*'POST'/", $html);
        $this->assertStringContainsString("'X-CSRF-TOKEN'", $html);
        $this->assertStringContainsString('csrf-token', $html);
    }
}
