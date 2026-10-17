<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayWayVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The payment book as a CSV, for the accountant and for tax time.
 *
 * The rules that matter: only an admin may download it, it holds exactly the
 * rows the tab and search box were showing, and it cannot change anything.
 */
class PaymentExportTest extends TestCase
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

    /** The streamed body, as the browser would receive it. */
    private function download(array $query = []): string
    {
        return $this->actingAs($this->admin())
            ->get(route('admin.payments.export', $query))
            ->assertOk()
            ->streamedContent();
    }

    public function test_an_admin_can_download_the_book_as_a_csv(): void
    {
        $paid = $this->payment(['status' => 'paid', 'paid_at' => now(), 'amount' => 35.00]);

        $this->actingAs($this->admin())
            ->get(route('admin.payments.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $body = $this->download();

        // fputcsv quotes any heading with a space in it.
        $this->assertStringContainsString('Invoice,Created,"Paid at",Status', $body);
        $this->assertStringContainsString($paid->invoice_no, $body);
        $this->assertStringContainsString('35.00', $body);
        $this->assertStringContainsString('USD', $body);
    }

    public function test_the_file_opens_in_excel_with_khmer_intact(): void
    {
        $title = 'ការរចនាក្រាហ្វិក';
        $course = Course::factory()->create(['title_en' => $title, 'price' => 20, 'access_type' => 'paid']);
        $this->payment(['course' => $course, 'course_title' => $title, 'status' => 'paid']);

        $body = $this->download();

        // The three-byte BOM, or Excel reads the file as the local code page and
        // every Khmer title arrives as mojibake.
        $this->assertSame(chr(0xEF).chr(0xBB).chr(0xBF), substr($body, 0, 3));
        $this->assertStringContainsString($title, $body);
    }

    public function test_the_export_holds_only_the_rows_the_tab_is_showing(): void
    {
        $paid = $this->payment(['status' => 'paid', 'paid_at' => now()]);
        $pending = $this->payment(['status' => 'pending']);

        $body = $this->download(['status' => 'paid']);

        $this->assertStringContainsString($paid->invoice_no, $body);
        $this->assertStringNotContainsString($pending->invoice_no, $body);
    }

    public function test_the_export_respects_the_search_box(): void
    {
        $wanted = $this->payment(['payer_email' => 'keeper@example.com']);
        $other = $this->payment(['payer_email' => 'someone-else@example.com']);

        $body = $this->download(['q' => 'keeper@example.com']);

        $this->assertStringContainsString($wanted->invoice_no, $body);
        $this->assertStringNotContainsString($other->invoice_no, $body);
    }

    public function test_archived_rows_stay_out_unless_their_own_tab_is_asked_for(): void
    {
        $archived = $this->payment(['status' => 'paid', 'paid_at' => now(), 'archived_at' => now()]);
        $live = $this->payment(['status' => 'paid', 'paid_at' => now()]);

        $default = $this->download();
        $this->assertStringContainsString($live->invoice_no, $default);
        $this->assertStringNotContainsString($archived->invoice_no, $default);

        $tab = $this->download(['status' => 'archived']);
        $this->assertStringContainsString($archived->invoice_no, $tab);
        $this->assertStringNotContainsString($live->invoice_no, $tab);
    }

    public function test_a_payer_name_cannot_smuggle_a_formula_into_the_spreadsheet(): void
    {
        // Excel and Sheets execute a cell that opens with = + - @, so a customer
        // typed name must not arrive as live content in the accountant's file.
        $this->payment(['payer_name' => '=HYPERLINK("http://evil.example","click")']);

        $body = $this->download();

        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
    }

    public function test_an_orphaned_payment_still_exports_under_the_name_it_was_made_with(): void
    {
        $payment = $this->payment(['status' => 'paid', 'paid_at' => now(), 'payer_name' => 'Sok Dara']);
        $payment->user->delete();
        $payment->course->delete();

        $body = $this->download();

        $this->assertStringContainsString($payment->invoice_no, $body);
        $this->assertStringContainsString('Sok Dara', $body);
    }

    public function test_the_file_reads_newest_first_like_the_page_it_came_from(): void
    {
        $older = $this->payment(['status' => 'paid']);
        $newer = $this->payment(['status' => 'paid']);

        // created_at is not fillable, so it has to be set past the model.
        Payment::query()->whereKey($older->id)->update(['created_at' => now()->subDays(3)]);

        $body = $this->download();

        $this->assertLessThan(
            strpos($body, $older->invoice_no),
            strpos($body, $newer->invoice_no),
            'The newest payment should appear before the oldest.'
        );
    }

    public function test_a_student_cannot_download_the_payment_book(): void
    {
        $this->payment(['status' => 'paid']);

        $this->actingAs($this->student())
            ->get(route('admin.payments.export'))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_download_the_payment_book(): void
    {
        $this->get(route('admin.payments.export'))->assertRedirect();
    }

    public function test_the_export_changes_nothing(): void
    {
        $payment = $this->payment(['status' => 'pending']);
        // fresh() on both sides: the just-created model in memory only holds the
        // columns that were written, while a re-read row holds every column.
        $before = $payment->fresh()->toArray();

        $this->download();

        $this->assertEquals($before, $payment->fresh()->toArray());
        $this->assertDatabaseCount('enrollments', 0);
    }
}
