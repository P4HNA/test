<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLibraryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_library_shows_courses_learning_paths_and_latest_lessons(): void
    {
        $course = Course::factory()->create(['title_en' => 'Brand Identity Basics', 'mark' => 'Ai', 'category' => 'Illustrator']);
        $module = Module::query()->create(['course_id' => $course->id, 'title_en' => 'Logo Grids', 'title_km' => 'Logo Grids', 'sort_order' => 1]);
        Lesson::query()->create(['module_id' => $module->id, 'title_en' => 'Golden Ratio Marks', 'title_km' => 'Golden Ratio Marks', 'sort_order' => 1, 'duration_minutes' => 9]);

        $this->get(route('courses'))
            ->assertOk()
            ->assertSeeText('Start your learning journey here')
            ->assertSee('Brand Identity Basics')
            ->assertSee('Deepen your skills with a learning path')
            ->assertSee('Logo Grids')
            ->assertSee('Explore the latest tutorials')
            ->assertSee('Golden Ratio Marks')
            ->assertSee('Video · 9 min');
    }

    public function test_a_category_link_opens_on_that_tab(): void
    {
        Course::factory()->create(['title_en' => 'Ai Course', 'category' => 'Illustrator']);
        Course::factory()->create(['title_en' => 'Ps Course', 'category' => 'Photoshop']);
        Course::factory()->create(['title_en' => 'Id Course', 'category' => 'InDesign']);

        $html = $this->get(route('courses', ['category' => 'illustrator']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-value="Illustrator" aria-selected="true"/', $html);
        $this->assertMatchesRegularExpression('/data-category="Photoshop"\s+hidden/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-category="Illustrator"\s+hidden/', $html);
    }

    public function test_an_empty_search_offers_a_reset(): void
    {
        Course::factory()->create(['title_en' => 'Brand Identity Basics']);

        $this->get(route('courses', ['q' => 'zzzz-missing']))
            ->assertOk()
            ->assertSee('No main courses match your search')
            ->assertDontSee('Deepen your skills with a learning path');
    }
}
