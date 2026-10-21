<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    private function enrolled(Quiz $quiz): User
    {
        $user = User::factory()->create();
        Enrollment::query()->create(['user_id' => $user->id, 'course_id' => $quiz->course_id, 'enrolled_at' => now()]);

        return $user;
    }

    public function test_the_quiz_page_shows_questions_but_not_the_answers(): void
    {
        $quiz = Quiz::factory()->withQuestions(3)->create();

        $html = $this->actingAs($this->enrolled($quiz))->get(route('quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Typography Quiz')
            ->assertSee('Question 1?')
            ->assertSee('Right B')
            ->getContent();

        $this->assertStringNotContainsString('correct_index', $html);
        $this->assertStringNotContainsString('B is the right one.', $html);
    }

    public function test_only_enrolled_students_can_take_it(): void
    {
        $quiz = Quiz::factory()->withQuestions()->create();

        $this->get(route('quizzes.show', $quiz))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('quizzes.show', $quiz))->assertNotFound();
    }

    public function test_checking_an_answer_says_whether_it_is_right(): void
    {
        $quiz = Quiz::factory()->withQuestions(1)->create();
        $student = $this->enrolled($quiz);
        $question = $quiz->questions()->first();

        $this->actingAs($student)->postJson(route('quizzes.check', $quiz), ['question' => $question->id, 'choice' => 0])
            ->assertOk()->assertJson(['correct' => false, 'answer' => 1, 'explanation' => 'B is the right one.']);
        $this->actingAs($student)->postJson(route('quizzes.check', $quiz), ['question' => $question->id, 'choice' => 1])
            ->assertOk()->assertJson(['correct' => true]);
    }

    public function test_the_score_is_worked_out_on_the_server(): void
    {
        $quiz = Quiz::factory()->withQuestions(4)->create();
        $student = $this->enrolled($quiz);
        $ids = $quiz->questions()->pluck('id')->all();

        $res = $this->actingAs($student)->postJson(route('quizzes.attempts', $quiz), ['answers' => [$ids[0] => 1, $ids[1] => 1, $ids[2] => 1, $ids[3] => 0]])
            ->assertOk()->assertJson(['score' => 75, 'correct' => 3, 'total' => 4, 'passed' => true]);

        $attempt = QuizAttempt::query()->sole();
        $this->assertSame(route('quizzes.result', [$quiz, $attempt]), $res->json('url'));

        $this->actingAs($student)->get($res->json('url'))->assertOk()->assertSee('75%')->assertSee('B is the right one.');
        $this->actingAs($this->enrolled($quiz))->get($res->json('url'))->assertNotFound();
    }

    public function test_the_plain_form_also_works(): void
    {
        $quiz = Quiz::factory()->withQuestions(2)->create();
        $student = $this->enrolled($quiz);
        $ids = $quiz->questions()->pluck('id')->all();

        $this->actingAs($student)->post(route('quizzes.attempts', $quiz), ['answers' => [$ids[0] => 0, $ids[1] => 0]])
            ->assertRedirect();

        $this->assertFalse(QuizAttempt::query()->sole()->passed);
    }

    public function test_the_teacher_builds_a_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $course = \App\Models\Course::factory()->create();

        $this->actingAs($admin)->get(route('admin.quizzes.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.quizzes.store'), [
            'course_id' => $course->id,
            'title_en' => 'Colour Theory Test',
            'pass_percent' => 80,
            'is_published' => '1',
            'questions' => [
                ['prompt_en' => 'Complement of blue?', 'options' => [['en' => 'Green'], ['en' => ''], ['en' => 'Orange'], ['en' => 'Purple']], 'correct' => 2],
            ],
        ])->assertRedirect(route('admin.quizzes'));

        $question = Quiz::query()->sole()->questions()->sole();
        $this->assertCount(3, $question->options);
        $this->assertSame(1, $question->correct_index);   // "Orange", after the blank answer is dropped
        $this->actingAs($admin)->get(route('admin.quizzes'))->assertOk()->assertSee('Colour Theory Test');
    }

    public function test_a_question_needs_its_right_answer_filled_in(): void
    {
        $admin = User::factory()->admin()->create();
        $course = \App\Models\Course::factory()->create();

        $this->actingAs($admin)->post(route('admin.quizzes.store'), [
            'course_id' => $course->id, 'title_en' => 'Broken', 'pass_percent' => 70,
            'questions' => [['prompt_en' => 'Q?', 'options' => [['en' => 'A'], ['en' => ''], ['en' => 'C']], 'correct' => 1]],
        ])->assertSessionHasErrors('questions.0.options');

        $this->assertSame(0, Quiz::query()->count());
    }
}
