<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Promotions: discounts that really change what checkout charges, the bar at
 * the top of the homepage, and the one-at-a-time featured banner on the Course
 * Library.
 */
class PromotionTest extends TestCase
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
        Cache::flush();
    }

    private function course(array $attributes = []): Course
    {
        return Course::factory()->create($attributes + [
            'title_en' => 'Adobe Illustrator Beginner',
            'category' => 'Illustrator',
            'price' => 35.00,
            'access_type' => 'paid',
        ]);
    }

    private function promotion(array $attributes = []): Promotion
    {
        return Promotion::query()->create($attributes + [
            'kind' => 'discount',
            'title_en' => 'Illustrator week',
            'discount_percent' => 20,
            'applies_to' => 'all',
            'is_active' => true,
            'show_on_home' => true,
            'priority' => 0,
        ]);
    }

    private function fakePayWay(string $checkStatus = 'PENDING', string $amount = '28.00'): void
    {
        Http::fake([
            self::QR_ENDPOINT => Http::response([
                'status' => ['code' => '0', 'message' => 'success'],
                'qrString' => '00020101021230560016abaakhppxxx@abaa',
                'qrImage' => base64_encode('fake-png-bytes'),
                'abapay_deeplink' => 'https://link.payway.com.kh/aba?id=TEST123',
            ]),
            self::CHECK_ENDPOINT => Http::response([
                'status' => ['code' => '0', 'message' => 'success'],
                'data' => ['payment_status' => $checkStatus, 'payment_amount' => $amount, 'payment_currency' => 'USD', 'apv' => 'APV1'],
            ]),
        ]);
    }

    /* ---------------------------------------------------------------- pricing */

    public function test_a_live_discount_lowers_the_price(): void
    {
        $course = $this->course();
        $this->promotion(['applies_to' => 'course', 'course_id' => $course->id]);

        $this->assertSame(28.0, $course->fresh()->salePrice());
        $this->assertTrue($course->fresh()->isOnSale());
    }

    public function test_discounts_never_stack_the_best_single_one_wins(): void
    {
        $course = $this->course();
        $this->promotion(['discount_percent' => 10]);
        $this->promotion(['discount_percent' => 30, 'applies_to' => 'category', 'category' => 'Illustrator']);
        $this->promotion(['discount_percent' => 20, 'applies_to' => 'course', 'course_id' => $course->id]);

        $this->assertSame(24.5, $course->fresh()->salePrice()); // 30% off, not 10+20+30
    }

    public function test_a_discount_never_takes_a_course_below_one_cent(): void
    {
        $cheap = $this->course(['price' => 0.01]);
        $this->promotion(['discount_percent' => 90]);

        $this->assertSame(0.01, $cheap->fresh()->salePrice());
        $this->assertFalse($cheap->fresh()->isOnSale());
    }

    public function test_only_live_promotions_change_prices(): void
    {
        $course = $this->course();
        $this->promotion(['starts_at' => now()->addDay()]);             // scheduled
        $this->promotion(['ends_at' => now()->subMinute()]);            // ended
        $this->promotion(['is_active' => false]);                       // turned off
        $this->promotion(['kind' => 'announcement', 'discount_percent' => null]);

        $this->assertSame(35.0, $course->fresh()->salePrice());
    }

    public function test_a_category_discount_skips_other_categories_and_free_courses(): void
    {
        $illustrator = $this->course();
        $photoshop = $this->course(['title_en' => 'Photoshop', 'category' => 'Photoshop']);
        $free = $this->course(['title_en' => 'Free one', 'price' => 0, 'access_type' => 'free']);
        $this->promotion(['applies_to' => 'category', 'category' => 'Illustrator']);

        $this->assertTrue($illustrator->fresh()->isOnSale());
        $this->assertFalse($photoshop->fresh()->isOnSale());
        $this->assertFalse($free->fresh()->isOnSale());
    }

    /* ---------------------------------------------------------------- checkout */

    public function test_checkout_charges_the_discounted_price_and_keeps_the_regular_one(): void
    {
        $this->fakePayWay();
        $course = $this->course();
        $promotion = $this->promotion(['applies_to' => 'course', 'course_id' => $course->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk()->assertSee('28.00');

        $payment = Payment::query()->where('user_id', $user->id)->sole();
        $this->assertSame('28.00', (string) $payment->amount);
        $this->assertSame('35.00', (string) $payment->list_price);
        $this->assertSame($promotion->id, $payment->promotion_id);
        $this->assertTrue($payment->wasDiscounted());

        // PayWay was asked for the discounted amount.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generate-qr') && $request['amount'] === '28.00');
    }

    public function test_a_full_price_checkout_records_no_promotion(): void
    {
        $this->fakePayWay();
        $course = $this->course();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();

        $payment = Payment::query()->where('user_id', $user->id)->sole();
        $this->assertSame('35.00', (string) $payment->amount);
        $this->assertNull($payment->list_price);
        $this->assertNull($payment->promotion_id);
    }

    public function test_paying_the_discounted_amount_unlocks_the_course(): void
    {
        $this->fakePayWay('APPROVED', '28.00');
        $course = $this->course();
        $this->promotion(['applies_to' => 'course', 'course_id' => $course->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();
        $payment = Payment::query()->where('user_id', $user->id)->sole();

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($user->fresh()->isEnrolledIn($course));
    }

    public function test_a_sale_ending_after_the_qr_was_shown_still_honours_its_price(): void
    {
        $this->fakePayWay('APPROVED', '28.00');
        $course = $this->course();
        $promotion = $this->promotion(['applies_to' => 'course', 'course_id' => $course->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('payments.checkout', $course))->assertOk();
        $payment = Payment::query()->where('user_id', $user->id)->sole();

        $promotion->update(['is_active' => false]);   // sale over while they scan

        $this->postJson(route('payway.callback'), ['tran_id' => $payment->invoice_no])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_the_course_page_shows_the_sale_and_the_real_amount(): void
    {
        $course = $this->course();
        $this->promotion(['applies_to' => 'course', 'course_id' => $course->id, 'ends_at' => now()->addDays(3)]);

        $this->actingAs(User::factory()->create())->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Pay $28.00 with KHQR')
            ->assertSee('<s', false)
            ->assertSee('20% off');
    }

    /* ---------------------------------------------------------------- homepage */

    public function test_the_homepage_shows_the_live_promotion_with_the_highest_priority(): void
    {
        $this->course();
        $this->promotion(['title_en' => 'Small news', 'kind' => 'announcement', 'discount_percent' => null, 'priority' => 1]);
        $this->promotion(['title_en' => 'Big sale', 'priority' => 5]);
        $this->promotion(['title_en' => 'Hidden sale', 'priority' => 9, 'show_on_home' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Big sale')
            ->assertDontSee('Small news')
            ->assertDontSee('Hidden sale');
    }

    public function test_ended_or_turned_off_promotions_leave_the_homepage(): void
    {
        $this->course();
        $this->promotion(['title_en' => 'Old sale', 'ends_at' => now()->subHour()]);
        $this->promotion(['title_en' => 'Paused sale', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Old sale')
            ->assertDontSee('Paused sale')
            ->assertDontSee('class="promo"', false);
    }

    public function test_the_homepage_keeps_the_top_bar_but_no_card_in_the_course_section(): void
    {
        $course = $this->course();
        $this->promotion(['title_en' => 'Pinned sale', 'applies_to' => 'course', 'course_id' => $course->id]);

        $html = $this->get(route('home'))->assertOk()->assertSee('Pinned sale')->getContent();

        $bar = strpos($html, 'class="promo"');
        $this->assertNotFalse($bar, 'The promotion bar stays at the very top.');
        $this->assertLessThan(strpos($html, 'id="site-nav"'), $bar, 'The bar sits above the menu.');
        $this->assertStringNotContainsString('class="cpromo"', $html);   // the old Courses-section card is gone
        $this->assertStringNotContainsString('pd-ribbon"', $html);       // ribbons belong to the Course Library
    }

    public function test_the_course_library_shows_the_banner_and_the_ribbon_only_on_the_targeted_course(): void
    {
        $target = $this->course(['title_en' => 'Targeted Course', 'price' => 50.00]);
        $other = $this->course(['title_en' => 'Other Course', 'price' => 50.00]);
        $this->promotion(['title_en' => 'Library sale', 'discount_percent' => 20, 'applies_to' => 'course', 'course_id' => $target->id]);

        $html = $this->get(route('courses'))->assertOk()
            ->assertSee('class="pbn"', false)
            ->assertSee('Library sale')
            ->assertSee('$40')          // the real sale price
            ->assertSee('Save $10')     // and the real saving
            ->getContent();

        $this->assertSame(1, substr_count($html, 'class="pd-ribbon"'), 'Only the targeted course carries the ribbon.');
        $this->assertSame(1, substr_count($html, ' is-promo'), 'Only the targeted course gets the blue ring.');
        $this->assertStringContainsString('20% off', $html);
    }

    public function test_a_category_promotion_ribbons_every_course_in_that_category_only(): void
    {
        $this->course(['title_en' => 'Ai One', 'category' => 'Illustrator']);
        $this->course(['title_en' => 'Ai Two', 'category' => 'Illustrator']);
        $this->course(['title_en' => 'Ps One', 'category' => 'Photoshop']);
        $this->promotion(['applies_to' => 'category', 'category' => 'Illustrator']);

        $html = $this->get(route('courses'))->assertOk()->assertSee('class="pbn"', false)->getContent();

        $this->assertSame(2, substr_count($html, 'class="pd-ribbon"'));
    }

    /**
     * The banner prints the promoted course's lesson and student counts.
     *
     * The promotion's course is loaded by its own query, not by the course list
     * beside it, so the banner counts them itself. If that count ever goes
     * missing the page still renders -- it just quietly reads " lessons" and
     * drops the students line -- which is exactly the kind of break no other
     * assertion here would catch. Asserted inside the banner only, because
     * every course tile prints a lesson count of its own.
     */
    public function test_the_banner_prints_the_promoted_courses_lesson_and_student_counts(): void
    {
        $course = $this->course(['title_en' => 'Counted Course']);
        $module = Module::query()->create([
            'course_id' => $course->id,
            'title_en' => 'Module 01',
            'title_km' => 'ម៉ូឌុល ០១',
            'sort_order' => 0,
        ]);

        foreach (range(1, 3) as $i) {
            Lesson::query()->create([
                'module_id' => $module->id,
                'title_en' => 'Lesson 0'.$i,
                'title_km' => 'មេរៀន 0'.$i,
                'video_host' => 'youtube',
                'duration_minutes' => 10,
                'sort_order' => $i,
            ]);
        }

        Enrollment::query()->create([
            'user_id' => User::factory()->create()->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $this->promotion(['applies_to' => 'course', 'course_id' => $course->id]);

        $html = $this->get(route('courses'))->assertOk()->getContent();

        $start = strpos($html, '<aside class="pbn"');
        $this->assertNotFalse($start, 'The featured banner should be on the Course Library.');
        $banner = substr($html, $start, strpos($html, '</aside>', $start) - $start);

        $this->assertStringContainsString('3 lessons', $banner);
        $this->assertStringContainsString('1 student enrolled', $banner);
    }

    public function test_no_banner_or_ribbon_for_inactive_expired_or_announcement_promotions(): void
    {
        $this->course();
        $this->promotion(['title_en' => 'Old', 'ends_at' => now()->subHour()]);
        $this->promotion(['title_en' => 'Paused', 'is_active' => false]);

        $this->get(route('courses'))->assertOk()->assertDontSee('class="pbn"', false)->assertDontSee('pd-ribbon"', false);

        $this->promotion(['kind' => 'announcement', 'discount_percent' => null, 'title_en' => 'Just news']);
        $html = $this->get(route('courses'))->assertOk()->assertSee('Just news')->getContent();
        $this->assertStringNotContainsString('pd-ribbon"', $html);   // news changes no price
    }

    public function test_a_promotion_for_a_draft_course_is_not_advertised(): void
    {
        $draft = $this->course(['is_published' => false]);
        $this->promotion(['title_en' => 'Draft sale', 'applies_to' => 'course', 'course_id' => $draft->id]);

        $this->get(route('home'))->assertOk()->assertDontSee('Draft sale');
    }

    public function test_a_category_promotion_links_to_that_category(): void
    {
        $this->course();
        $this->promotion(['applies_to' => 'category', 'category' => 'Illustrator']);

        $this->get(route('home'))->assertOk()->assertSee(route('courses', ['category' => 'Illustrator']), false);
        $this->get(route('courses', ['category' => 'illustrator']))->assertOk()->assertViewHas('activeCategory', 'Illustrator');
    }

    /* ---------------------------------------------------------------- admin */

    public function test_an_admin_can_create_a_discount(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->course();

        $this->actingAs($admin)->post(route('admin.promotions.store'), [
            'kind' => 'discount',
            'title_en' => 'Illustrator week',
            'discount_percent' => 25,
            'applies_to' => 'course',
            'course_id' => $course->id,
            'ends_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'is_active' => '1',
            'show_on_home' => '1',
            'priority' => 2,
        ])->assertRedirect(route('admin.promotions'));

        $promotion = Promotion::query()->sole();
        $this->assertSame(25, $promotion->discount_percent);
        $this->assertSame($course->id, $promotion->course_id);
        $this->assertTrue($promotion->isLive());
        $this->assertSame(26.25, $course->fresh()->salePrice());
    }

    public function test_the_admin_form_rejects_unsafe_or_impossible_values(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.promotions.store'), [
            'kind' => 'discount',
            'title_en' => 'Too good',
            'discount_percent' => 95,
            'applies_to' => 'course',
            'cta_url' => 'javascript:alert(1)',
            'starts_at' => '2026-10-10T10:00',
            'ends_at' => '2026-10-01T10:00',
        ])->assertSessionHasErrors(['discount_percent', 'course_id', 'cta_url', 'ends_at']);

        $this->assertSame(0, Promotion::count());
    }

    public function test_an_announcement_needs_no_discount(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.promotions.store'), [
            'kind' => 'announcement',
            'title_en' => 'New Figma course in November',
            'applies_to' => 'all',
            'cta_url' => '/courses',
            'is_active' => '1',
            'show_on_home' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Promotion::query()->sole()->discount_percent);
    }

    public function test_an_admin_can_turn_a_promotion_off_and_delete_it(): void
    {
        $admin = User::factory()->admin()->create();
        $course = $this->course();
        $promotion = $this->promotion();

        $this->actingAs($admin)->patch(route('admin.promotions.toggle', $promotion))->assertRedirect();
        $this->assertFalse($promotion->fresh()->is_active);
        $this->assertSame(35.0, $course->fresh()->salePrice());

        $this->actingAs($admin)->delete(route('admin.promotions.destroy', $promotion))->assertRedirect(route('admin.promotions'));
        $this->assertSame(0, Promotion::count());
    }

    public function test_the_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $promotion = $this->promotion(['title_en' => 'Visible in the list']);

        $this->actingAs($admin)->get(route('admin.promotions'))->assertOk()->assertSee('Visible in the list')->assertSee('On the homepage now');
        $this->actingAs($admin)->get(route('admin.promotions.create'))->assertOk()->assertSee('Homepage preview');
        $this->actingAs($admin)->get(route('admin.promotions.edit', $promotion))->assertOk()->assertSee('Delete this promotion');
    }

    public function test_students_cannot_manage_promotions(): void
    {
        $student = User::factory()->create();
        $promotion = $this->promotion();

        $this->actingAs($student)->get(route('admin.promotions'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.promotions.store'), ['title_en' => 'x'])->assertForbidden();
        $this->actingAs($student)->patch(route('admin.promotions.toggle', $promotion))->assertForbidden();
        $this->assertTrue($promotion->fresh()->is_active);
    }
}
