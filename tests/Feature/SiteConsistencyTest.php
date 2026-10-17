<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Small promises the whole site keeps: the footer links go somewhere real,
 * "Free Courses" really shows only free courses, and every page carries the
 * current logo rather than the old drawn "P".
 */
class SiteConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_footer_has_no_all_courses_and_free_courses_is_filtered(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('All Courses')
            ->assertSee(route('courses', ['free' => 1]), false);
    }

    public function test_free_courses_shows_free_courses_only(): void
    {
        Course::factory()->create(['title_en' => 'Totally Free Basics', 'title_km' => 'Totally Free Basics', 'price' => 0, 'access_type' => 'free']);
        Course::factory()->create(['title_en' => 'Paid Masterclass', 'title_km' => 'Paid Masterclass', 'price' => 45, 'access_type' => 'paid']);

        foreach (['en', 'km'] as $locale) {
            $this->withSession(['locale' => $locale])
                ->get(route('courses', ['free' => 1]))
                ->assertOk()
                ->assertSee('Totally Free Basics')
                ->assertDontSee('Paid Masterclass');
        }
    }

    public function test_pages_use_the_current_logo(): void
    {
        $course = Course::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach ([route('home'), route('courses'), route('courses.show', $course), route('login'), route('register')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('images/logo-mark-96.png', false)
                ->assertDontSee('>P</text>', false);
        }

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('images/logo-mark-96.png', false);
    }

    public function test_every_layout_links_the_tab_icon(): void
    {
        foreach ([route('home'), route('courses'), route('login')] as $url) {
            $this->get($url)->assertSee('favicon-32.png', false);
        }
    }

    public function test_sign_in_pages_name_the_product_in_the_tab(): void
    {
        foreach ([route('login'), route('register'), route('password.request')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<title>Piseth.Design Classroom</title>', false);
        }
    }
}
