<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'title_en' => ucwords($title),
            'title_km' => 'វគ្គសិក្សា '.$title,
            'mark' => strtoupper(fake()->lexify('??')),
            'category' => fake()->randomElement(['Photoshop', 'Illustrator', 'InDesign', 'Graphic Design', 'UI/UX', 'Motion Graphics']),
            'level' => fake()->randomElement(['Beginner', 'Intermediate', 'All Levels']),
            'price' => fake()->randomElement([0, 25, 35, 45]),
            'access_type' => fake()->randomElement(['free', 'paid']),
            'description_en' => fake()->paragraph(),
            'description_km' => fake()->paragraph(),
            'instructor_name' => 'Piseth Yoeurn',
            'instructor_title' => 'Senior Graphic Designer & Educator',
            'outcomes' => [
                ['en' => fake()->sentence(), 'km' => fake()->sentence()],
            ],
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
