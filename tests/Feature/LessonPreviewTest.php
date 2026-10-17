<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonPreviewTest extends TestCase
{
    use RefreshDatabase;

    /** A paid course with a free sample lesson and a locked one after it. */
    private function course(array $overrides = []): array
    {
        // $overrides first: with "+" the left-hand side wins on a repeated key.
        $course = Course::factory()->create($overrides + [
            'slug' => 'preview-course',
            'access_type' => 'paid',
            'price' => 35,
            'is_published' => true,
        ]);

        $module = Module::query()->create([
            'course_id' => $course->id,
            'title_en' => 'Module 01',
            'title_km' => 'ម៉ូឌុល ០១',
            'sort_order' => 0,
        ]);

        $free = Lesson::query()->create([
            'module_id' => $module->id,
            'title_en' => 'Free sample lesson',
            'title_km' => 'មេរៀនគំរូ',
            'video_host' => 'youtube',
            'video_url' => 'https://youtu.be/abcdefghijk',
            'duration_minutes' => 9,
            'is_preview' => true,
            'sort_order' => 0,
        ]);

        $paid = Lesson::query()->create([
            'module_id' => $module->id,
            'title_en' => 'Paid lesson',
            'title_km' => 'មេរៀនបង់ប្រាក់',
            'video_host' => 'youtube',
            'video_url' => 'https://youtu.be/zyxwvutsrqp',
            'duration_minutes' => 14,
            'is_preview' => false,
            'sort_order' => 1,
        ]);

        return [$course, $free, $paid];
    }

    public function test_a_preview_lesson_opens_without_paying_and_its_siblings_stay_locked(): void
    {
        [$course, $free, $paid] = $this->course();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $free]))
            ->assertOk()
            ->assertSee('Free sample lesson')
            ->assertSee('Get the full course');

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $paid]))
            ->assertRedirect(route('courses.show', $course));

        // Watching the sample must not quietly enrol them in the whole course.
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_a_preview_watcher_cannot_record_progress(): void
    {
        [$course, $free] = $this->course();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('courses.lessons.complete', [$course, $free]))
            ->assertForbidden();

        $this->assertDatabaseCount('lesson_completions', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_the_preview_flag_does_not_leak_an_unpublished_course(): void
    {
        [$course, $free] = $this->course(['is_published' => false]);
        $user = User::factory()->create();

        // A draft is only for admins and for students who already have it. A
        // preview flag left switched on inside one must not open that door.
        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $free]))
            ->assertRedirect(route('courses.show', $course));
    }

    public function test_an_uploaded_preview_video_streams_but_a_paid_one_does_not(): void
    {
        Storage::fake('local');
        [$course, $free, $paid] = $this->course();

        foreach ([$free, $paid] as $lesson) {
            $path = 'lesson-videos/'.$lesson->id.'.mp4';
            Storage::disk('local')->put($path, 'not really a video');
            $lesson->forceFill(['video_host' => 'upload', 'video_url' => null, 'video_path' => $path])->save();
        }

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('courses.lessons.video', [$course, $free]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('courses.lessons.video', [$course, $paid]))
            ->assertForbidden();
    }

    public function test_the_course_page_advertises_the_preview_and_links_to_it(): void
    {
        [$course, $free] = $this->course();

        $this->actingAs(User::factory()->create())
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Free preview')
            ->assertSee(route('courses.lessons.show', [$course, $free]), escape: false);
    }

    public function test_an_enrolled_student_is_unaffected_by_the_preview_flag(): void
    {
        [$course, $free, $paid] = $this->course();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        // Both lessons open, and the normal progress button is back.
        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $paid]))
            ->assertOk()
            ->assertSee('Mark as complete')
            ->assertDontSee('Get the full course');

        $this->actingAs($user)
            ->post(route('courses.lessons.complete', [$course, $free]))
            ->assertRedirect();
    }

    public function test_an_admin_can_turn_a_preview_on_and_off(): void
    {
        [$course, , $paid] = $this->course();
        $admin = User::factory()->admin()->create();

        $payload = [
            'module_id' => $paid->module_id,
            'title_en' => $paid->title_en,
            'title_km' => $paid->title_km,
            'video_host' => 'keep',
            'duration_minutes' => $paid->duration_minutes,
            'is_preview' => '1',
        ];

        // Models, not ids: an admin route binds a course on whichever key the
        // model declares, so letting route() resolve it keeps this honest
        // whether that key is the id or the uuid.
        $this->actingAs($admin)
            ->put(route('admin.lessons.update', [$course, $paid]), $payload)
            ->assertRedirect();
        $this->assertTrue($paid->fresh()->is_preview);

        // Unticking the box submits no field at all, which must turn it back off.
        unset($payload['is_preview']);

        $this->actingAs($admin)
            ->put(route('admin.lessons.update', [$course, $paid]), $payload)
            ->assertRedirect();
        $this->assertFalse($paid->fresh()->is_preview);
    }
}
