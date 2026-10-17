<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Module;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A student's own admin page: their courses and progress, their payments,
 * and giving or taking away a course by hand.
 */
class AdminStudentRecordTest extends TestCase
{
    use RefreshDatabase;

    private function paidCourse(): array
    {
        $course = Course::factory()->create(['title_en' => 'Figma Basics', 'price' => 35, 'access_type' => 'paid']);
        $module = Module::query()->create(['course_id' => $course->id, 'title_en' => 'M1', 'title_km' => 'M1', 'sort_order' => 1]);
        $lessons = collect([1, 2])->map(fn ($i) => Lesson::query()->create([
            'module_id' => $module->id, 'title_en' => "Lesson {$i}", 'title_km' => "Lesson {$i}",
            'video_host' => 'youtube', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 5, 'sort_order' => $i,
        ]));

        return [$course, $lessons];
    }

    public function test_an_admin_sees_a_students_courses_progress_and_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['name' => 'Dara Meas']);
        [$course, $lessons] = $this->paidCourse();

        Enrollment::query()->create(['user_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        LessonCompletion::query()->create(['user_id' => $student->id, 'lesson_id' => $lessons[0]->id, 'completed_at' => now()]);
        Payment::create([
            'user_id' => $student->id, 'course_id' => $course->id, 'payer_name' => $student->name,
            'payer_email' => $student->email, 'course_title' => $course->title_en, 'invoice_no' => 'PD2609280001',
            'amount' => 35, 'currency' => 'USD', 'qr' => 'qr', 'md5' => md5('x'), 'status' => 'paid',
            'paid_at' => now(), 'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($admin)->get(route('admin.users.show', $student))
            ->assertOk()
            ->assertSee('Dara Meas')
            ->assertSee('Figma Basics')
            ->assertSee('1</strong> <span class="ad-muted">of 2 lessons · 50%', false)
            ->assertSee('PD2609280001')
            ->assertSee('Paid');
    }

    public function test_the_students_list_links_each_person_to_their_record(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.users'))
            ->assertOk()
            ->assertSee(route('admin.users.show', $student), false);
    }

    public function test_students_cannot_open_a_record_or_change_access(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        [$course] = $this->paidCourse();

        $this->actingAs($student)->get(route('admin.users.show', $other))->assertForbidden();
        $this->actingAs($student)->post(route('admin.users.enrollments.grant', $other), ['course_id' => $course->id])->assertForbidden();
        $this->assertDatabaseMissing('enrollments', ['user_id' => $other->id]);
    }

    public function test_giving_a_course_opens_it_without_inventing_a_payment(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        [$course, $lessons] = $this->paidCourse();

        $this->actingAs($admin)
            ->from(route('admin.users.show', $student))
            ->post(route('admin.users.enrollments.grant', $student), ['course_id' => $course->id])
            ->assertRedirect(route('admin.users.show', $student))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'No payment was recorded'));

        $this->assertTrue($student->fresh()->isEnrolledIn($course));
        $this->assertSame(0, Payment::count());

        // The student can now actually open the lesson.
        $this->actingAs($student)->get(route('courses.lessons.show', [$course, $lessons[0]]))->assertOk();
    }

    public function test_giving_a_course_twice_does_not_duplicate_it(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        [$course] = $this->paidCourse();

        $this->actingAs($admin)->post(route('admin.users.enrollments.grant', $student), ['course_id' => $course->id]);
        $this->actingAs($admin)->post(route('admin.users.enrollments.grant', $student), ['course_id' => $course->id])
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'already has'));

        $this->assertSame(1, Enrollment::where('user_id', $student->id)->count());
    }

    public function test_giving_a_course_needs_a_real_course(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.enrollments.grant', $student), ['course_id' => 999])
            ->assertSessionHasErrors('course_id');
    }

    public function test_taking_a_course_away_closes_it_but_keeps_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        [$course, $lessons] = $this->paidCourse();
        Enrollment::query()->create(['user_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        LessonCompletion::query()->create(['user_id' => $student->id, 'lesson_id' => $lessons[0]->id, 'completed_at' => now()]);

        $this->actingAs($admin)
            ->delete(route('admin.users.enrollments.revoke', [$student, $course]))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'progress is kept'));

        $this->assertFalse($student->fresh()->isEnrolledIn($course));
        $this->assertDatabaseHas('lesson_completions', ['user_id' => $student->id, 'lesson_id' => $lessons[0]->id]);
        $this->actingAs($student)->get(route('courses.lessons.show', [$course, $lessons[0]]))->assertRedirect();
    }

    public function test_removing_someone_from_their_record_page_returns_to_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.show', $student))
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_the_suspended_filter_lists_only_suspended_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Still Active']);
        User::factory()->create(['name' => 'Locked Out', 'banned_at' => now()]);

        $this->actingAs($admin)->get(route('admin.users', ['role' => 'suspended']))
            ->assertOk()
            ->assertSee('Locked Out')
            ->assertDontSee('Still Active');
    }
}
