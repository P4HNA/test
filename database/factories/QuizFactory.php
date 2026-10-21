<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quiz> */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title_en' => 'Typography Quiz',
            'title_km' => 'តេស្តចំណេះដឹង Typography',
            'topic' => 'Typography & Font Psychology',
            'pass_percent' => 70,
            'is_published' => true,
        ];
    }

    /** Adds $count questions whose right answer is always B (index 1). */
    public function withQuestions(int $count = 3): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($count) {
            foreach (range(1, $count) as $n) {
                $quiz->questions()->create([
                    'prompt_en' => "Question $n?",
                    'options' => [['en' => 'Wrong A', 'km' => ''], ['en' => 'Right B', 'km' => ''], ['en' => 'Wrong C', 'km' => ''], ['en' => 'Wrong D', 'km' => '']],
                    'correct_index' => 1,
                    'explanation_en' => 'B is the right one.',
                    'sort_order' => $n,
                ]);
            }
        });
    }
}
