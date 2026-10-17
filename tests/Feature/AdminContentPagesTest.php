<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Videos and Course pictures admin pages (Payments is covered by
 * PaymentHistoryTest and PaymentSweeperAndRefundTest).
 */
class AdminContentPagesTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(Course $course, array $attributes = []): Lesson
    {
        $module = Module::query()->firstOrCreate(
            ['course_id' => $course->id, 'title_en' => 'Module 01'],
            ['title_km' => 'ម៉ូឌុល ០១', 'sort_order' => 1],
        );

        return Lesson::query()->create($attributes + [
            'module_id' => $module->id,
            'title_en' => 'Lesson '.fake()->unique()->numberBetween(1, 999),
            'title_km' => 'មេរៀន',
            'video_host' => 'youtube',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 10,
            'sort_order' => 1,
        ]);
    }

    public function test_admins_can_open_every_content_page(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['title_en' => 'Figma Basics']);
        $this->lesson($course);

        $this->actingAs($admin);
        $this->get(route('admin.payments'))->assertOk()->assertSee('Payments');
        $this->get(route('admin.videos'))->assertOk()->assertSee('Figma Basics');
        $this->get(route('admin.videos.show', $course))->assertOk()->assertSee('Edit course');
        $this->get(route('admin.thumbnails'))->assertOk()->assertSee('Course pictures');
    }

    public function test_students_cannot_open_the_content_pages(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($student);
        $this->get(route('admin.payments'))->assertForbidden();
        $this->get(route('admin.videos'))->assertForbidden();
        $this->get(route('admin.videos.show', $course))->assertForbidden();
        $this->get(route('admin.thumbnails'))->assertForbidden();
    }

    public function test_the_videos_overview_counts_lessons_that_still_need_a_video(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $this->lesson($course);
        $this->lesson($course, ['video_url' => null]);

        $this->actingAs($admin)->get(route('admin.videos'))
            ->assertOk()
            ->assertSee('1 missing')
            ->assertSee('1/2');
    }

    public function test_removing_a_video_keeps_the_lesson(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $lesson = $this->lesson($course);

        $this->actingAs($admin)
            ->delete(route('admin.videos.destroy', $lesson))
            ->assertRedirect(route('admin.videos.show', $course));

        $lesson->refresh();
        $this->assertNull($lesson->video_url);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }

    public function test_a_picture_link_can_be_saved_from_the_gallery(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.thumbnails'))
            ->post(route('admin.thumbnails.update', $course), [
                'course_id' => $course->id,
                'thumbnail_url' => 'https://example.com/cover.jpg',
            ])
            ->assertRedirect(route('admin.thumbnails'));

        $this->assertSame('https://example.com/cover.jpg', $course->refresh()->thumbnail_url);
    }

    public function test_a_bad_picture_link_is_reported_on_that_course_only(): void
    {
        $admin = User::factory()->admin()->create();
        $first = Course::factory()->create();
        Course::factory()->create();

        $html = $this->actingAs($admin)
            ->from(route('admin.thumbnails'))
            ->followingRedirects()
            ->post(route('admin.thumbnails.update', $first), [
                'course_id' => $first->id,
                'thumbnail_url' => 'not a link',
            ])
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'The picture link must start with http:// or https://'));
    }
}
