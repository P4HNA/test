<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Payments must outlive the student and the course they were made for.
 * Deleting either used to cascade the payment away, destroying the record of
 * money that really changed hands.
 */
class PaymentRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function course(array $attributes = []): Course
    {
        return Course::factory()->create($attributes + [
            'title_en' => 'Adobe Illustrator Beginner',
            'price' => 35,
            'access_type' => 'paid',
        ]);
    }

    private function payment(User $user, Course $course, array $attributes = []): Payment
    {
        return Payment::create($attributes + [
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
            'status' => 'paid',
            'paid_at' => now(),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ]);
    }

    /** PayWay configured and answering, so checkout can mint a QR. */
    private function fakePayWay(): void
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

    public function test_deleting_a_course_keeps_the_payment(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $payment = $this->payment($user, $course);

        $course->delete();

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        $payment->refresh();
        $this->assertNull($payment->course_id);
        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame('paid', $payment->status);
        $this->assertSame('35.00', (string) $payment->amount);
    }

    public function test_deleting_a_user_keeps_the_payment(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $payment = $this->payment($user, $course);

        $user->delete();

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        $payment->refresh();
        $this->assertNull($payment->user_id);
        $this->assertSame($course->id, $payment->course_id);
        $this->assertSame('paid', $payment->status);
    }

    public function test_deleting_both_keeps_the_payment_and_its_money(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $payment = $this->payment($user, $course);

        $course->delete();
        $user->delete();

        $payment->refresh();
        $this->assertNull($payment->user_id);
        $this->assertNull($payment->course_id);
        $this->assertSame('35.00', (string) $payment->amount);
        $this->assertSame('USD', $payment->currency);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_the_orphaned_payment_still_says_who_paid_for_what(): void
    {
        $user = User::factory()->create(['name' => 'Sokha Chan', 'email' => 'sokha@example.com']);
        $course = $this->course(['title_en' => 'UI/UX Design with Figma']);
        $payment = $this->payment($user, $course);

        $course->delete();
        $user->delete();
        $payment->refresh();

        $this->assertTrue($payment->isOrphaned());
        $this->assertSame('Sokha Chan', $payment->payerName());
        $this->assertSame('sokha@example.com', $payment->payerEmail());
        $this->assertSame('UI/UX Design with Figma', $payment->courseTitle());
    }

    public function test_a_live_payment_reads_through_to_the_real_records(): void
    {
        $user = User::factory()->create(['name' => 'Dara Meas', 'email' => 'dara@example.com']);
        $course = $this->course(['title_en' => 'Motion Graphics']);
        $payment = $this->payment($user, $course);

        // Renamed after paying: the live record wins over the snapshot.
        $user->update(['name' => 'Dara M.']);
        $course->update(['title_en' => 'Motion Graphics with After Effects']);
        $payment->refresh();

        $this->assertFalse($payment->isOrphaned());
        $this->assertSame('Dara M.', $payment->payerName());
        $this->assertSame('Motion Graphics with After Effects', $payment->courseTitle());
    }

    public function test_checkout_stores_the_snapshot_when_the_payment_is_created(): void
    {
        $user = User::factory()->create([
            'name' => 'Bopha Lim',
            'email' => 'bopha@example.com',
            'email_verified_at' => now(),
        ]);
        $course = $this->course(['title_en' => 'Adobe InDesign Masterclass']);

        $this->fakePayWay();

        $this->actingAs($user)
            ->get(route('payments.checkout', $course))
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'payer_name' => 'Bopha Lim',
            'payer_email' => 'bopha@example.com',
            'course_title' => 'Adobe InDesign Masterclass',
        ]);
    }

    public function test_confirming_an_orphaned_payment_does_not_try_to_enrol_anyone(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $payment = $this->payment($user, $course);

        $course->delete();
        $user->delete();
        $payment->refresh();

        $enrollmentsBefore = Enrollment::count();

        // Must not throw, and must not invent an enrollment with null ids.
        app(PayWayVerifier::class)->ensureEnrollment($payment);

        $this->assertSame($enrollmentsBefore, Enrollment::count());
        $this->assertDatabaseMissing('enrollments', ['user_id' => null]);
        $this->assertDatabaseMissing('enrollments', ['course_id' => null]);
    }

    public function test_a_normal_payment_still_enrols_the_student(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $payment = $this->payment($user, $course);

        app(PayWayVerifier::class)->ensureEnrollment($payment);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_deleting_a_course_still_removes_enrollments_and_progress(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $this->payment($user, $course);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $course->delete();

        // Only payments are spared; the rest of the cascade is unchanged.
        $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id]);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_the_account_deletion_notice_tells_students_payments_are_kept(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Records of payments you have made are kept for accounting', false);
    }
}
