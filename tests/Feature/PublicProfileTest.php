<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Module;
use App\Models\User;
use App\Support\LearningStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_about_you_fields_are_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile/public', [
            'headline' => 'Motion designer',
            'bio' => 'Learning After Effects.',
            'location' => 'Phnom Penh',
            'website' => 'piseth.design',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Motion designer', $user->headline);
        $this->assertSame('Learning After Effects.', $user->bio);
        $this->assertSame('Phnom Penh', $user->location);
        // A bare domain is accepted and stored with a scheme, so the profile
        // link is always clickable.
        $this->assertSame('https://piseth.design', $user->website);
    }

    public function test_clearing_a_field_stores_null_rather_than_an_empty_string(): void
    {
        $user = User::factory()->create(['headline' => 'Designer', 'bio' => 'Hello']);

        $this->actingAs($user)->patch('/profile/public', [
            'headline' => '',
            'bio' => '',
        ])->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNull($user->headline);
        $this->assertNull($user->bio);
    }

    public function test_an_uploaded_photo_replaces_the_previous_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile/public', [
            'avatar' => UploadedFile::fake()->image('first.jpg', 400, 400),
        ])->assertSessionHasNoErrors();

        $first = $user->refresh()->avatar_path;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        $this->actingAs($user)->patch('/profile/public', [
            'avatar' => UploadedFile::fake()->image('second.jpg', 400, 400),
        ])->assertSessionHasNoErrors();

        $second = $user->refresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_remove_avatar_clears_the_photo_and_deletes_the_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile/public', [
            'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
        ]);

        $path = $user->refresh()->avatar_path;

        $this->actingAs($user)->patch('/profile/public', ['remove_avatar' => 1])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_large_photo_is_squared_off_and_shrunk_before_it_is_stored(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile/public', [
            // A big, non-square phone-shaped photo.
            'avatar' => UploadedFile::fake()->image('phone.jpg', 3000, 4000),
        ])->assertSessionHasNoErrors();

        $stored = Storage::disk('public')->path($user->refresh()->avatar_path);
        [$width, $height] = getimagesize($stored);

        $this->assertSame(512, $width, 'the long edge should be capped');
        $this->assertSame($width, $height, 'avatars are shown in circles, so they are stored square');
    }

    public function test_a_photo_smaller_than_the_cap_is_not_upscaled(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile/public', [
            'avatar' => UploadedFile::fake()->image('small.jpg', 200, 200),
        ])->assertSessionHasNoErrors();

        [$width] = getimagesize(Storage::disk('public')->path($user->refresh()->avatar_path));

        $this->assertSame(200, $width);
    }

    public function test_a_tiny_image_and_a_bad_url_are_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile/public', ['avatar' => UploadedFile::fake()->image('tiny.jpg', 40, 40)])
            ->assertSessionHasErrors('avatar');

        $this->actingAs($user)
            ->patch('/profile/public', ['website' => 'not a website'])
            ->assertSessionHasErrors('website');
    }

    public function test_deleting_the_account_removes_the_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile/public', [
            'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
        ]);

        $path = $user->refresh()->avatar_path;

        $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_completeness_counts_the_filled_in_fields(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $bare = $user->profileCompleteness();

        $user->update(['headline' => 'Designer', 'bio' => 'Hi', 'location' => 'Siem Reap', 'phone' => '12345678']);

        $this->assertGreaterThan($bare, $user->refresh()->profileCompleteness());
    }

    public function test_initials_handle_one_word_and_multi_byte_names(): void
    {
        $this->assertSame('MP', User::factory()->make(['name' => 'Mao Panha'])->initials());
        $this->assertSame('M', User::factory()->make(['name' => 'maopanha'])->initials());
        $this->assertSame('ស', User::factory()->make(['name' => 'សុខ'])->initials());
    }

    public function test_streaks_and_hours_come_from_the_completion_record(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::create(['course_id' => $course->id, 'title_en' => 'Basics', 'title_km' => 'មូលដ្ឋាន', 'sort_order' => 1]);

        // Three consecutive days ending today, plus an older isolated day.
        foreach ([0, 1, 2, 9] as $index => $daysAgo) {
            $lesson = Lesson::create([
                'module_id' => $module->id,
                'title_en' => 'Lesson '.$index,
                'title_km' => 'មេរៀន '.$index,
                'duration_minutes' => 30,
                'sort_order' => $index,
            ]);

            LessonCompletion::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'completed_at' => Carbon::today()->subDays($daysAgo),
            ]);
        }

        $stats = new LearningStats($user->refresh());

        $this->assertSame(3, $stats->currentStreak());
        $this->assertSame(3, $stats->longestStreak());
        $this->assertSame(4, $stats->activeDays());
        $this->assertSame(120, $stats->minutesLearned());
        $this->assertSame('2.0', $stats->hoursLearned());
    }

    public function test_the_activity_grid_covers_whole_weeks_and_includes_today(): void
    {
        $user = User::factory()->create();
        $grid = (new LearningStats($user))->activityGrid();

        $this->assertCount(LearningStats::WEEKS, $grid);

        foreach ($grid as $week) {
            $this->assertCount(7, $week);
        }

        $dates = collect($grid)->flatten(1)->pluck('date');
        $this->assertContains(Carbon::today()->toDateString(), $dates);
    }

    public function test_the_profile_page_renders_for_a_student(): void
    {
        $user = User::factory()->create(['headline' => 'Motion designer', 'location' => 'Phnom Penh']);
        $course = Course::factory()->create();

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/my-profile')
            ->assertOk()
            ->assertSee('Motion designer')
            ->assertSee('Phnom Penh')
            ->assertSee('Milestones');
    }
}
