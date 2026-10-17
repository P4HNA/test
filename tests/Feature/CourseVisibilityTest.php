<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Draft courses: built out of sight, published when ready.
 */
class CourseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function course(array $attributes = []): array
    {
        $course = Course::factory()->create($attributes + ['title_en' => 'Secret Draft Course']);
        $module = Module::query()->create(['course_id' => $course->id, 'title_en' => 'M1', 'title_km' => 'M1', 'sort_order' => 1]);
        $lesson = Lesson::query()->create([
            'module_id' => $module->id, 'title_en' => 'L1', 'title_km' => 'L1', 'video_host' => 'youtube',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duration_minutes' => 5, 'sort_order' => 1,
        ]);

        return [$course, $lesson];
    }

    public function test_existing_and_factory_courses_are_published(): void
    {
        [$course] = $this->course();

        $this->assertTrue($course->fresh()->isPublished());
        $this->get(route('courses'))->assertOk()->assertSee('Secret Draft Course');
    }

    public function test_a_draft_is_hidden_from_the_catalogue_and_the_home_page(): void
    {
        $this->course(['is_published' => false]);

        $this->get(route('home'))->assertOk()->assertDontSee('Secret Draft Course');
        $this->get(route('courses'))->assertOk()->assertDontSee('Secret Draft Course');
    }

    public function test_a_draft_page_does_not_exist_for_guests_and_students(): void
    {
        [$course] = $this->course(['is_published' => false]);

        $this->get(route('courses.show', $course))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('courses.show', $course))->assertNotFound();
    }

    public function test_an_admin_can_preview_a_draft(): void
    {
        [$course] = $this->course(['is_published' => false]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Secret Draft Course');
    }

    public function test_a_draft_cannot_be_joined_or_bought(): void
    {
        [$free] = $this->course(['is_published' => false, 'price' => 0, 'access_type' => 'free']);
        [$paid] = $this->course(['is_published' => false, 'price' => 20, 'access_type' => 'paid', 'title_en' => 'Paid Draft']);
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('courses.enroll', $free))->assertNotFound();
        $this->actingAs($student)->get(route('payments.checkout', $paid))->assertNotFound();
        $this->assertDatabaseMissing('enrollments', ['user_id' => $student->id]);
    }

    public function test_students_who_already_have_a_course_keep_it_when_it_becomes_a_draft(): void
    {
        [$course, $lesson] = $this->course();
        $student = User::factory()->create();
        Enrollment::query()->create(['user_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);

        $course->update(['is_published' => false]);

        $this->actingAs($student)->get(route('courses.show', $course))->assertOk();
        $this->actingAs($student)->get(route('courses.lessons.show', [$course, $lesson]))->assertOk();
    }

    public function test_a_checkout_started_before_hiding_can_still_be_reached(): void
    {
        [$course] = $this->course(['price' => 20, 'access_type' => 'paid']);
        $student = User::factory()->create();
        Payment::create([
            'user_id' => $student->id, 'course_id' => $course->id, 'payer_name' => $student->name,
            'payer_email' => $student->email, 'course_title' => $course->title_en, 'invoice_no' => 'PD2609280009',
            'amount' => 20, 'currency' => 'USD', 'qr' => 'qr', 'md5' => md5('y'), 'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);

        $course->update(['is_published' => false]);

        $status = $this->actingAs($student)->get(route('payments.checkout', $course))->status();
        $this->assertNotSame(404, $status);
    }

    public function test_a_new_course_starts_as_a_draft_unless_published(): void
    {
        $admin = User::factory()->admin()->create();
        $base = [
            'title_en' => 'Brand New', 'mark' => 'Ps', 'category' => 'Photoshop', 'level' => 'Beginner',
            'price' => 0, 'description_en' => 'About it.',
        ];

        $this->actingAs($admin)->post(route('admin.courses.store'), $base);
        $this->assertFalse(Course::where('title_en', 'Brand New')->first()->isPublished());

        $this->actingAs($admin)->post(route('admin.courses.store'), ['title_en' => 'Straight Live', 'is_published' => '1'] + $base);
        $this->assertTrue(Course::where('title_en', 'Straight Live')->first()->isPublished());
    }

    public function test_the_course_list_can_publish_and_hide_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        [$course] = $this->course(['is_published' => false]);

        $this->actingAs($admin)->from(route('admin.courses'))
            ->patch(route('admin.courses.visibility', $course), ['published' => 1])
            ->assertRedirect(route('admin.courses'));
        $this->assertTrue($course->fresh()->isPublished());

        $this->actingAs($admin)->patch(route('admin.courses.visibility', $course), ['published' => 0]);
        $this->assertFalse($course->fresh()->isPublished());
    }

    public function test_students_cannot_publish_a_course(): void
    {
        [$course] = $this->course(['is_published' => false]);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.courses.visibility', $course), ['published' => 1])
            ->assertForbidden();
        $this->assertFalse($course->fresh()->isPublished());
    }

    public function test_the_admin_course_list_filters_drafts_and_flags_missing_videos(): void
    {
        $admin = User::factory()->admin()->create();
        [$draft] = $this->course(['is_published' => false]);
        Course::factory()->create(['title_en' => 'Live Course']);
        Lesson::query()->create([
            'module_id' => $draft->modules()->first()->id, 'title_en' => 'L2', 'title_km' => 'L2',
            'video_host' => 'youtube', 'video_url' => null, 'duration_minutes' => 5, 'sort_order' => 2,
        ]);

        $this->actingAs($admin)->get(route('admin.courses', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('Secret Draft Course')
            ->assertDontSee('Live Course')
            ->assertSee('1 lesson without a video');
    }

    public function test_the_dashboard_lists_what_needs_attention(): void
    {
        $admin = User::factory()->admin()->create();
        [$course] = $this->course(['is_published' => false]);
        Lesson::query()->create([
            'module_id' => $course->modules()->first()->id, 'title_en' => 'L2', 'title_km' => 'L2',
            'video_host' => 'youtube', 'video_url' => null, 'duration_minutes' => 5, 'sort_order' => 2,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Needs attention')
            ->assertSee('lesson has no video', false)
            ->assertSee('course is a draft', false)
            ->assertSee('See drafts');
    }
}
