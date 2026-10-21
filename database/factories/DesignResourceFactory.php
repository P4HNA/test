<?php

namespace Database\Factories;

use App\Models\DesignResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesignResource>
 */
class DesignResourceFactory extends Factory
{
    protected $model = DesignResource::class;

    public function definition(): array
    {
        return [
            'title_en' => fake()->unique()->words(4, true),
            'title_km' => null,
            'description_en' => fake()->sentence(14),
            'description_km' => null,
            'type' => 'Mockup & PSD Kit',
            'software' => 'Photoshop',
            'category' => 'Photoshop',
            'tags' => ['mockup', 'poster'],
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'external_url' => 'https://example.com/files/kit.zip',
            'thumbnail_url' => null,
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
