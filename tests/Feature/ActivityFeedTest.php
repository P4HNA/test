<?php

namespace Tests\Feature;

use App\Models\ActivityLike;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Support\ActivityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    private function lessonIn(Course $course, string $title): Lesson
    {
        $module = Module::query()->create(['course_id' => $course->id, 'title_en' => 'Module 1', 'title_km' => 'ជំពូក ១', 'sort_order' => 1]);

        return Lesson::query()->create(['module_id' => $module->id, 'title_en' => $title, 'title_km' => $title, 'sort_order' => 1, 'duration_minutes' => 12]);
    }

    public function test_new_lessons_of_published_courses_are_posted(): void
    {
        $course = Course::factory()->create(['title_en' => 'Motion Principles']);
        $this->lessonIn($course, 'Easing Curves');
        $draft = Course::factory()->create(['title_en' => 'Draft Course', 'is_published' => false]);
        $this->lessonIn($draft, 'Hidden Lesson');

        $this->get(route('activity.index'))
            ->assertOk()
            ->assertSee('Community Learning Activity')
            ->assertSee('Motion Principles — Easing Curves')
            ->assertDontSee('Hidden Lesson')
            ->assertDontSee('Draft Course');
    }

    public function test_student_milestones_are_for_signed_in_visitors_only_and_use_short_names(): void
    {
        $course = Course::factory()->create(['title_en' => 'Graphic Design Fundamentals']);
        $student = User::factory()->create(['name' => 'Sothea Chan']);
        Enrollment::query()->create(['user_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        Certificate::query()->create(['user_id' => $student->id, 'course_id' => $course->id, 'code' => 'PD-TEST-1', 'holder_name' => 'Sothea Chan', 'issued_at' => now()]);

        $this->get(route('activity.index'))
            ->assertOk()
            ->assertDontSee('Sothea')
            ->assertSee('Sign in to see your classmates');

        $this->actingAs(User::factory()->create())
            ->get(route('activity.index'))
            ->assertOk()
            ->assertSee('Sothea C.')
            ->assertDontSee('Sothea Chan')
            ->assertSee('earned the certificate for')
            ->assertSee('joined the course');
    }

    public function test_assignments_and_quiz_wins_are_posted(): void
    {
        $assignment = \App\Models\Assignment::factory()->create(['title_en' => 'Typography Poster']);
        $quiz = \App\Models\Quiz::factory()->create(['course_id' => $assignment->course_id, 'title_en' => 'Colour Quiz']);
        $student = User::factory()->create(['name' => 'Bopha Vann']);
        \App\Models\QuizAttempt::query()->create(['quiz_id' => $quiz->id, 'user_id' => $student->id, 'answers' => [], 'correct' => 5, 'total' => 5, 'score' => 100, 'passed' => true]);

        $this->get(route('activity.index'))->assertOk()
            ->assertSee('posted a new assignment')
            ->assertSee('Assignment #03 — Typography Poster')
            ->assertDontSee('Bopha');

        $this->actingAs(User::factory()->create())->get(route('activity.index'))->assertOk()
            ->assertSee('Bopha V.')
            ->assertSee('passed a quiz')
            ->assertSee('Scored 100% (5 of 5)');

        $this->assertTrue(ActivityFeed::isValidKey('quiz:12'));
        $this->assertTrue(ActivityFeed::isValidKey('task:3'));
    }

    public function test_a_like_toggles_and_is_counted(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user)->postJson(route('activity.like'), ['key' => 'course:'.$course->id])
            ->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->actingAs($user)->postJson(route('activity.like'), ['key' => 'course:'.$course->id])
            ->assertOk()->assertJson(['liked' => false, 'count' => 0]);

        $this->assertSame(0, ActivityLike::query()->count());
    }

    public function test_a_made_up_key_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('activity.like'), ['key' => '<script>'])
            ->assertStatus(422);
    }

    public function test_guests_cannot_like(): void
    {
        $this->post(route('activity.like'), ['key' => 'course:1'])->assertRedirect(route('login'));
    }

    public function test_short_names(): void
    {
        $this->assertSame('Sothea C.', ActivityFeed::shortName('Sothea Chan'));
        $this->assertSame('Dara S.', ActivityFeed::shortName('  Dara  Mao  Somnang '));
        $this->assertSame('សុធា', ActivityFeed::shortName('សុធា'));
    }

    public function test_log_in_another_account_signs_out_to_the_sign_in_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'), ['switch_account' => '1'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
