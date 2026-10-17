<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The payment history: the admin's money book, and the student's own receipts.
 *
 * Two rules hold this together. A student sees their OWN payments and nothing
 * else, and "delete" never destroys a payment -- it archives it, because the row
 * is both an accounting record and the explanation for why a student has access
 * to a course.
 */
class PaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);
    }

    private function payment(array $attributes = []): Payment
    {
        $course = $attributes['course'] ?? Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $user = $attributes['user'] ?? $this->student();
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
            'qr' => '00020101-test-qr',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'gateway_response' => ['gateway' => PayWayVerifier::GATEWAY],
        ], $attributes));
    }

    /* ---------------------------------------------------------------- admin */

    public function test_an_admin_sees_every_payment_with_its_status(): void
    {
        $paid = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        $waiting = $this->payment();
        $refused = $this->payment(['status' => 'declined']);

        $response = $this->actingAs($this->admin())->get(route('admin.payments'));

        $response->assertOk();
        $response->assertSee($paid->invoice_no);
        $response->assertSee($waiting->invoice_no);
        $response->assertSee($refused->invoice_no);
    }

    public function test_the_status_tabs_filter_the_list(): void
    {
        $paid = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        $waiting = $this->payment();

        $response = $this->actingAs($this->admin())->get(route('admin.payments', ['status' => 'paid']));

        $response->assertOk();
        $response->assertSee($paid->invoice_no);
        $response->assertDontSee($waiting->invoice_no);
    }

    public function test_an_unknown_status_falls_back_to_all_rather_than_erroring(): void
    {
        $payment = $this->payment(['status' => 'paid', 'paid_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.payments', ['status' => 'nonsense']));

        $response->assertOk();
        $response->assertSee($payment->invoice_no);
    }

    public function test_search_finds_a_payment_by_invoice_email_and_course(): void
    {
        $course = Course::factory()->create(['title_en' => 'Advanced Retouching', 'price' => 40, 'access_type' => 'paid']);
        $buyer = User::factory()->create(['email' => 'findme@example.com', 'email_verified_at' => now()]);
        $wanted = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        $other = $this->payment();

        $admin = $this->admin();

        foreach ([$wanted->invoice_no, 'findme@example.com', 'Advanced Retouching'] as $term) {
            $response = $this->actingAs($admin)->get(route('admin.payments', ['q' => $term]));

            $response->assertOk();
            $response->assertSee($wanted->invoice_no);
            $response->assertDontSee($other->invoice_no);
        }
    }

    /**
     * A payment whose student and course have been deleted must still be
     * findable by the names it was made under -- that is what the snapshot
     * columns on the row are for.
     */
    public function test_an_orphaned_payment_is_still_listed_and_searchable(): void
    {
        $payment = $this->payment([
            'status' => 'paid',
            'paid_at' => now(),
            'user_id' => null,
            'course_id' => null,
            'payer_name' => 'Gone Student',
            'payer_email' => 'gone@example.com',
            'course_title' => 'Deleted Masterclass',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.payments', ['q' => 'Deleted Masterclass']));

        $response->assertOk();
        $response->assertSee($payment->invoice_no);
        $response->assertSee('Gone Student');
    }

    public function test_the_totals_add_up_per_currency_and_never_across_them(): void
    {
        $this->payment(['status' => 'paid', 'paid_at' => now(), 'amount' => 35.00, 'currency' => 'USD']);
        $this->payment(['status' => 'paid', 'paid_at' => now(), 'amount' => 15.50, 'currency' => 'USD']);
        $this->payment(['status' => 'paid', 'paid_at' => now(), 'amount' => 80000, 'currency' => 'KHR']);
        // Not money in the bank, so it must not be counted.
        $this->payment(['status' => 'pending', 'amount' => 999.00, 'currency' => 'USD']);

        $response = $this->actingAs($this->admin())->get(route('admin.payments'));

        $response->assertOk();
        $response->assertSee('$50.50');       // 35.00 + 15.50, and not 1049.50
        $response->assertSee('80,000 ៛', false);
    }

    /* ------------------------------------------------------------- archiving */

    public function test_archiving_hides_a_payment_without_deleting_it(): void
    {
        $payment = $this->payment(['status' => 'declined']);

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.archive', $payment))
            ->assertRedirect();

        // Still there, just marked.
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
        $this->assertNotNull($payment->fresh()->archived_at);
    }

    public function test_an_archived_payment_leaves_the_normal_list_and_appears_under_archived(): void
    {
        $payment = $this->payment(['status' => 'declined', 'archived_at' => now()]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.payments'))
            ->assertOk()->assertDontSee($payment->invoice_no);

        $this->actingAs($admin)->get(route('admin.payments', ['status' => 'archived']))
            ->assertOk()->assertSee($payment->invoice_no);
    }

    /** Archiving tidies the list; it must not rewrite the books. */
    public function test_an_archived_paid_payment_still_counts_in_the_totals(): void
    {
        $this->payment(['status' => 'paid', 'paid_at' => now(), 'amount' => 35.00, 'archived_at' => now()]);

        $this->actingAs($this->admin())->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('$35.00');
    }

    public function test_archiving_never_touches_the_enrollment_so_the_student_keeps_access(): void
    {
        $course = Course::factory()->create(['price' => 35, 'access_type' => 'paid']);
        $buyer = $this->student();
        $payment = $this->payment(['course' => $course, 'user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);
        Enrollment::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $this->actingAs($this->admin())->patch(route('admin.payments.archive', $payment));

        $this->assertTrue($buyer->fresh()->isEnrolledIn($course));
    }

    public function test_a_payment_can_be_restored(): void
    {
        $payment = $this->payment(['status' => 'declined', 'archived_at' => now()]);

        $this->actingAs($this->admin())
            ->patch(route('admin.payments.restore', $payment))
            ->assertRedirect();

        $this->assertNull($payment->fresh()->archived_at);
    }

    public function test_archiving_twice_is_harmless(): void
    {
        $payment = $this->payment(['status' => 'declined']);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.payments.archive', $payment));
        $first = $payment->fresh()->archived_at;

        $this->actingAs($admin)->patch(route('admin.payments.archive', $payment))->assertRedirect();

        $this->assertEquals($first, $payment->fresh()->archived_at);
    }

    /* ---------------------------------------------------------- who may look */

    public function test_a_student_cannot_reach_the_admin_payment_book(): void
    {
        $this->actingAs($this->student())
            ->get(route('admin.payments'))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_reach_the_admin_payment_book(): void
    {
        $this->get(route('admin.payments'))->assertRedirect(route('login'));
    }

    public function test_a_student_cannot_archive_a_payment(): void
    {
        $payment = $this->payment(['status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($this->student())
            ->patch(route('admin.payments.archive', $payment))
            ->assertForbidden();

        $this->assertNull($payment->fresh()->archived_at);
    }

    /* -------------------------------------------------------- student's view */

    public function test_a_student_sees_their_own_payment_history(): void
    {
        $buyer = $this->student();
        $mine = $this->payment(['user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);

        $response = $this->actingAs($buyer)->get(route('learning.profile'));

        $response->assertOk();
        // The history lives under the "Payments" tab on the profile; the
        // receipt itself is what the student actually needs to see.
        $response->assertSee('Payments');
        $response->assertSee($mine->invoice_no);
        $response->assertSee('$35.00');
    }

    /** The rule that matters most on this page. */
    public function test_a_student_never_sees_another_students_payment(): void
    {
        $buyer = $this->student();
        $someoneElse = $this->payment(['status' => 'paid', 'paid_at' => now()]);

        $response = $this->actingAs($buyer)->get(route('learning.profile'));

        $response->assertOk();
        $response->assertDontSee($someoneElse->invoice_no);
    }

    public function test_a_payment_an_admin_archived_is_hidden_from_the_student_too(): void
    {
        $buyer = $this->student();
        $archived = $this->payment(['user' => $buyer, 'status' => 'declined', 'archived_at' => now()]);

        $this->actingAs($buyer)->get(route('learning.profile'))
            ->assertOk()
            ->assertDontSee($archived->invoice_no);
    }

    /**
     * The customer-facing wording is deliberately gentler than the gateway's:
     * "declined" reads like an accusation for what is usually an abandoned QR.
     */
    public function test_the_student_is_shown_plain_wording_not_gateway_jargon(): void
    {
        $buyer = $this->student();
        $this->payment(['user' => $buyer, 'status' => 'declined']);

        $response = $this->actingAs($buyer)->get(route('learning.profile'));

        $response->assertOk();
        $response->assertSee('Not paid');
        $response->assertDontSee('declined');
    }

    public function test_a_student_with_no_payments_is_told_so_kindly(): void
    {
        $response = $this->actingAs($this->student())->get(route('learning.profile'));

        $response->assertOk();
        $response->assertSee('No payments yet');
    }

    public function test_a_guest_cannot_see_any_payment_history(): void
    {
        $this->get(route('learning.profile'))->assertRedirect(route('login'));
    }

    /**
     * There is no route a student could use to remove a payment, whatever they
     * send. Only the admin-guarded archive and restore exist.
     */
    public function test_there_is_no_customer_facing_way_to_delete_a_payment(): void
    {
        $buyer = $this->student();
        $payment = $this->payment(['user' => $buyer, 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($buyer)
            ->delete(route('admin.payments.archive', $payment))
            ->assertStatus(405);

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }
}
