<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\DesignResource;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_photo_replaces_only_the_picture(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'x');
        $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg', 'headline' => 'Motion designer', 'location' => 'Phnom Penh']);

        $this->actingAs($user)
            ->patch(route('profile.photo'), ['avatar' => UploadedFile::fake()->image('me.jpg', 400, 400)])
            ->assertRedirect(route('learning.profile'))
            ->assertSessionHas('status', 'photo-updated');

        $user->refresh();
        $this->assertNotSame('avatars/old.jpg', $user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        $this->assertSame('Motion designer', $user->headline);
        $this->assertSame('Phnom Penh', $user->location);
    }

    public function test_change_photo_needs_a_real_picture(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.photo'), [])->assertSessionHasErrors('avatar');
        $this->actingAs($user)
            ->patch(route('profile.photo'), ['avatar' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_the_profile_shows_my_courses_saved_resources_and_my_own_activity(): void
    {
        $me = User::factory()->create(['name' => 'Sothea Chan']);
        $other = User::factory()->create(['name' => 'Rithy Kem']);
        $course = Course::factory()->create(['title_en' => 'Brand Identity Basics']);
        $elsewhere = Course::factory()->create(['title_en' => 'Kinetic Type Lab']);
        Enrollment::query()->create(['user_id' => $me->id, 'course_id' => $course->id, 'enrolled_at' => now()]);
        Enrollment::query()->create(['user_id' => $other->id, 'course_id' => $elsewhere->id, 'enrolled_at' => now()]);

        $kept = DesignResource::factory()->create(['title_en' => 'Paper Grain Mockups']);
        DesignResource::factory()->create(['title_en' => 'Unsaved Brush Pack']);
        $me->savedResources()->attach($kept);

        $this->actingAs($me)->get(route('learning.profile'))
            ->assertOk()
            ->assertSee('Active Account')
            ->assertSee('Brand Identity Basics')
            ->assertSee('Paper Grain Mockups')
            ->assertDontSee('Unsaved Brush Pack')
            ->assertSee('joined the course')
            ->assertDontSee('Rithy');  // someone else's enrolment is not part of my activity
    }

    public function test_guests_cannot_change_a_photo(): void
    {
        $this->patch(route('profile.photo'))->assertRedirect(route('login'));
    }
}
