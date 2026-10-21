<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assignment> */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'number' => 3,
            'title_en' => 'Typography Poster',
            'title_km' => 'ផ្ទាំងរូបភាព Typography',
            'brief_en' => 'Create a poster using typography as the main visual element.',
            'requirements' => ['Dimensions: 1080 × 1350 px', 'File format: JPG or PNG'],
            'format' => 'High-Res PNG / JPG / PDF',
            'due_at' => now()->addDays(2),
            'is_published' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
