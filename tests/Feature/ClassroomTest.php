<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        Course::factory()->create(['title_en' => 'Adobe Photoshop Beginner']);

        $this->get('/')->assertOk();
        $this->get('/about')->assertOk()->assertSee('How it works');
        $this->get('/courses')->assertOk()->assertSee('Adobe Photoshop Beginner');
        $this->get('/courses?q=Photoshop')->assertOk()->assertSee('Adobe Photoshop Beginner');

        // The live-search index is intentionally embedded as JSON (for Alpine's
        // instant suggestions) and deliberately ignores "q", so the title can still
        // appear in the raw HTML even when no course card is rendered. Assert against
        // the actual rendered course results passed to the view, not the whole HTML
        // response, so this stays decoupled from that JSON data.
        $this->get('/courses?q=zzzz-missing')
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->isEmpty());
    }

    public function test_locale_can_be_switched(): void
    {
        $this->from('/courses')->post('/locale', ['locale' => 'km'])->assertRedirect('/courses');
        $this->assertSame('km', session('locale'));
    }

    public function test_student_can_enrol_complete_and_earn_certificate(): void
    {
        $user = User::factory()->create();
        // Pinned free: the factory randomly makes paid courses, which (correctly)
        // redirect to payment instead of the first lesson.
        $course = Course::factory()->create([
            'slug' => 'test-course',
            'access_type' => 'free',
            'price' => 0,
        ]);
        $module = Module::query()->create([
            'course_id' => $course->id,
            'title_en' => 'Module 01',
            'title_km' => 'ម៉ូឌុល ០១',
            'sort_order' => 0,
        ]);
        $lesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title_en' => 'Lesson 01',
            'title_km' => 'មេរៀន ០១',
            'video_host' => 'youtube',
            'duration_minutes' => 12,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('courses.enroll', $course))
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

        // Finishing the last lesson does not print a certificate. It lands on
        // the page where the student asks for one.
        $this->actingAs($user)
            ->post(route('courses.lessons.complete', [$course, $lesson]))
            ->assertRedirect(route('certificates.request', $course));

        $this->assertDatabaseMissing('certificates', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // The student sends the name to print on it and their Telegram.
        $this->actingAs($user)
            ->post(route('certificates.request.store', $course), [
                'real_name' => 'Sok Dara',
                'telegram_username' => 'sokdara',
            ])
            ->assertRedirect(route('certificates.request', $course));

        $certRequest = CertificateRequest::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->sole();

        $this->assertSame(CertificateRequest::PENDING, $certRequest->status);

        // An admin checking that request is what issues the certificate, and it
        // is printed in the name the admin checked, not the account name.
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.certificates.approve', $certRequest))
            ->assertRedirect();

        $this->assertSame(CertificateRequest::APPROVED, $certRequest->fresh()->status);

        $this->assertDatabaseHas('certificates', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'holder_name' => 'Sok Dara',
        ]);
    }

    public function test_a_lesson_cannot_be_completed_through_another_courses_url(): void
    {
        $user = User::factory()->create();
        $courseA = Course::factory()->create(['access_type' => 'free', 'price' => 0]);
        $courseB = Course::factory()->create(['access_type' => 'free', 'price' => 0]);
        $lessonA = $this->lessonIn($courseA);
        $lessonB = $this->lessonIn($courseB);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $courseB->id,
            'enrolled_at' => now(),
        ]);

        // Course A's lesson, smuggled in under Course B's URL.
        $this->actingAs($user)
            ->post(route('courses.lessons.complete', [$courseB, $lessonA]))
            ->assertNotFound();

        $this->assertDatabaseMissing('lesson_completions', ['user_id' => $user->id, 'lesson_id' => $lessonA->id]);
        $this->assertDatabaseMissing('certificates', ['user_id' => $user->id]);
        $this->assertNull(Enrollment::query()->where('user_id', $user->id)->where('course_id', $courseB->id)->value('last_lesson_id'));
        $this->assertSame(0, $courseA->progressPercent($user));

        // The real lesson of the enrolled course still completes normally, and
        // finishing it asks for a certificate rather than printing one.
        $this->actingAs($user)
            ->post(route('courses.lessons.complete', [$courseB, $lessonB]))
            ->assertRedirect(route('certificates.request', $courseB));

        $this->assertDatabaseHas('lesson_completions', ['user_id' => $user->id, 'lesson_id' => $lessonB->id]);
        $this->assertDatabaseMissing('certificates', ['user_id' => $user->id]);
    }

    public function test_an_admin_cannot_complete_a_lesson_through_another_courses_url_either(): void
    {
        $admin = User::factory()->admin()->create();
        $courseA = Course::factory()->create();
        $courseB = Course::factory()->create();
        $lessonA = $this->lessonIn($courseA);

        $this->actingAs($admin)
            ->post(route('courses.lessons.complete', [$courseB, $lessonA]))
            ->assertNotFound();

        $this->assertDatabaseMissing('lesson_completions', ['user_id' => $admin->id]);
        // No enrollment is auto-created for the mismatched course.
        $this->assertDatabaseMissing('enrollments', ['user_id' => $admin->id]);
    }

    public function test_an_admin_can_still_complete_a_lesson_of_the_right_course(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course);

        $this->actingAs($admin)
            ->post(route('courses.lessons.complete', [$course, $lesson]))
            ->assertRedirect(route('certificates.request', $course));

        $this->assertDatabaseHas('lesson_completions', ['user_id' => $admin->id, 'lesson_id' => $lesson->id]);
    }

    public function test_a_student_not_enrolled_in_the_course_cannot_complete_its_lesson(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = $this->lessonIn($course);

        $this->actingAs($user)
            ->post(route('courses.lessons.complete', [$course, $lesson]))
            ->assertForbidden();

        $this->assertDatabaseMissing('lesson_completions', ['user_id' => $user->id]);
    }

    public function test_lesson_page_and_video_reject_a_lesson_from_another_course(): void
    {
        $user = User::factory()->create();
        $courseA = Course::factory()->create();
        $courseB = Course::factory()->create();
        $lessonA = $this->lessonIn($courseA);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $courseB->id,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($user)->get(route('courses.lessons.show', [$courseB, $lessonA]))->assertNotFound();
        $this->actingAs($user)->get(route('courses.lessons.video', [$courseB, $lessonA]))->assertNotFound();
    }

    private function lessonIn(Course $course): Lesson
    {
        $module = Module::query()->create([
            'course_id' => $course->id,
            'title_en' => 'Module 01',
            'title_km' => 'ម៉ូឌុល ០១',
            'sort_order' => 0,
        ]);

        return Lesson::query()->create([
            'module_id' => $module->id,
            'title_en' => 'Lesson 01',
            'title_km' => 'មេរៀន ០១',
            'video_host' => 'youtube',
            'duration_minutes' => 12,
            'sort_order' => 0,
        ]);
    }

    public function test_guest_cannot_open_a_lesson(): void
    {
        $course = Course::factory()->create();
        $module = Module::query()->create([
            'course_id' => $course->id,
            'title_en' => 'Module 01',
            'title_km' => 'ម៉ូឌុល ០១',
            'sort_order' => 0,
        ]);
        $lesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title_en' => 'Lesson 01',
            'title_km' => 'មេរៀន ០១',
            'video_host' => 'youtube',
            'duration_minutes' => 12,
            'sort_order' => 0,
        ]);

        $this->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertRedirect(route('login'));
    }

    public function test_admin_portal_is_restricted(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.courses'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.courses'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users'))->assertOk();
    }
}
