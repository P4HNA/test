<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function courseWithModule(array $course = []): array
    {
        $course = Course::factory()->create($course);
        $module = Module::query()->create([
            'course_id' => $course->id,
            'title_en' => 'Module 01',
            'title_km' => 'ម៉ូឌុល ០១',
            'sort_order' => 1,
        ]);

        return [$course, $module];
    }

    public function test_admin_area_is_hidden_from_other_networks(): void
    {
        $admin = User::factory()->admin()->create();

        config(['admin.allowed_ips' => ['203.0.113.9']]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertNotFound();
    }

    public function test_admin_area_is_closed_when_no_ip_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();

        config(['admin.allowed_ips' => []]);

        $this->actingAs($admin)->get(route('admin.courses'))->assertNotFound();
    }

    public function test_dashboard_and_live_numbers_load(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        [$course] = $this->courseWithModule();

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Welcome back');

        $this->actingAs($admin)->getJson(route('admin.live', ['days' => 7]))
            ->assertOk()
            ->assertJsonPath('kpis.students.value', 1)
            ->assertJsonPath('kpis.enrollments.value', 1)
            ->assertJsonPath('chart.days', 7)
            ->assertJsonStructure(['kpis', 'chart' => ['labels', 'series', 'days', 'totals'], 'categories', 'recent', 'activity', 'courses', 'storage']);
    }

    public function test_admin_can_create_a_course_with_a_module_and_a_lesson(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.courses.store'), [
            'title_en' => 'Figma Basics',
            'title_km' => '',
            'mark' => 'Fg',
            'category' => 'UI/UX',
            'level' => 'Beginner',
            'price' => 0,
            'description_en' => 'Learn Figma from scratch.',
        ])->assertRedirect();

        $course = Course::query()->where('title_en', 'Figma Basics')->firstOrFail();

        $this->assertSame('figma-basics', $course->slug);
        $this->assertSame('free', $course->access_type);
        $this->assertSame('Figma Basics', $course->title_km);   // empty Khmer title falls back to English

        $this->actingAs($admin)->post(route('admin.modules.store', $course), ['title_en' => 'Getting started'])
            ->assertSessionHasNoErrors();

        $module = Module::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.lessons.store', $course), [
            'module_id' => $module->id,
            'title_en' => 'Welcome',
            'video_host' => 'youtube',
            'video_url' => 'https://youtu.be/aqz-KE-bpKQ',
            'duration_minutes' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title_en' => 'Welcome',
            'video_host' => 'youtube',
        ]);
    }

    public function test_a_lesson_cannot_be_put_in_another_courses_module(): void
    {
        $admin = User::factory()->admin()->create();
        [$course] = $this->courseWithModule();
        [, $foreignModule] = $this->courseWithModule();

        $this->actingAs($admin)->post(route('admin.lessons.store', $course), [
            'module_id' => $foreignModule->id,
            'title_en' => 'Sneaky',
            'video_host' => 'youtube',
        ])->assertSessionHasErrors('module_id');

        $this->assertDatabaseMissing('lessons', ['title_en' => 'Sneaky']);
    }

    public function test_fake_video_files_are_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.uploads.chunk'), [
            'upload_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'index' => 0,
            'total' => 1,
            'filename' => 'movie.mp4',
            'chunk' => UploadedFile::fake()->createWithContent('part', 'this is plain text, not a video'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'That file is not a real .mp4 video.']);

        $this->assertSame([], Storage::disk('local')->files('lesson-videos'));
    }

    public function test_uploaded_videos_are_only_streamed_to_enrolled_students(): void
    {
        Storage::fake('local');
        [$course, $module] = $this->courseWithModule(['slug' => 'video-course']);

        Storage::disk('local')->put('lesson-videos/sample.mp4', 'pretend video bytes');

        $lesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title_en' => 'Lesson 01',
            'title_km' => 'មេរៀន ០១',
            'video_host' => 'upload',
            'video_path' => 'lesson-videos/sample.mp4',
            'video_size' => 19,
            'duration_minutes' => 3,
            'sort_order' => 1,
        ]);

        $enrolled = User::factory()->create();
        $outsider = User::factory()->create();

        Enrollment::query()->create([
            'user_id' => $enrolled->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        $url = route('courses.lessons.video', [$course, $lesson]);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($outsider)->get($url)->assertForbidden();
        $this->actingAs($enrolled)->get($url)->assertOk();
    }

    public function test_admin_can_change_a_students_password(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'admin-pass-123']);
        $student = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.password', $student), [
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
            'current_password' => 'admin-pass-123',
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPassw0rd!', $student->fresh()->password));
    }

    public function test_changing_a_password_needs_the_admins_own_password(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'admin-pass-123']);
        $student = User::factory()->create(['password' => 'OldPassw0rd!']);

        $this->actingAs($admin)->put(route('admin.users.password', $student), [
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
            'current_password' => 'wrong-guess',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldPassw0rd!', $student->fresh()->password));
    }

    public function test_students_cannot_change_passwords(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create(['password' => 'OldPassw0rd!']);

        $this->actingAs($student)->put(route('admin.users.password', $other), [
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
            'current_password' => 'password',
        ])->assertForbidden();

        $this->assertTrue(Hash::check('OldPassw0rd!', $other->fresh()->password));
    }

    /** @return array<string, mixed> */
    private function coursePayload(array $overrides = []): array
    {
        return array_merge([
            'title_en' => 'Price Test',
            'mark' => 'Ps',
            'category' => 'Photoshop',
            'level' => 'Beginner',
            'price' => '5.99',
            'description_en' => 'A course used to test prices.',
        ], $overrides);
    }

    /** @return array<string, array{string, string, string}> input, stored value, access type */
    public static function validPrices(): array
    {
        return [
            '0.01' => ['0.01', '0.01', 'paid'],
            '0.50' => ['0.50', '0.50', 'paid'],
            '5.99' => ['5.99', '5.99', 'paid'],
            '10' => ['10', '10.00', 'paid'],
            '10.00' => ['10.00', '10.00', 'paid'],
            '99.99' => ['99.99', '99.99', 'paid'],
            '0' => ['0', '0.00', 'free'],
            '0.00' => ['0.00', '0.00', 'free'],
            '100000' => ['100000', '100000.00', 'paid'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function invalidPrices(): array
    {
        return [
            'negative' => ['-1'],
            'negative cents' => ['-0.01'],
            'above the maximum' => ['100000.01'],
            'far above maximum' => ['100001'],
            'three decimals' => ['1.999'],
            'not a number' => ['abc'],
            'empty' => [''],
        ];
    }

    #[DataProvider('validPrices')]
    public function test_admin_can_create_a_course_with_a_decimal_price(string $input, string $stored, string $access): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.courses.store'), $this->coursePayload(['price' => $input]))
            ->assertSessionHasNoErrors();

        $course = Course::query()->where('title_en', 'Price Test')->firstOrFail();

        $this->assertSame($stored, $course->price);
        $this->assertSame($access, $course->access_type);
    }

    #[DataProvider('validPrices')]
    public function test_admin_can_update_a_course_to_a_decimal_price(string $input, string $stored, string $access): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['price' => 25, 'access_type' => 'paid']);

        $this->actingAs($admin)->put(route('admin.courses.update', $course), $this->coursePayload(['price' => $input]))
            ->assertSessionHasNoErrors();

        $course->refresh();

        $this->assertSame($stored, $course->price);
        $this->assertSame($access, $course->access_type);
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_course_prices_are_still_rejected(string $input): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['price' => 25, 'access_type' => 'paid']);

        $this->actingAs($admin)->post(route('admin.courses.store'), $this->coursePayload(['price' => $input]))
            ->assertSessionHasErrors('price');

        $this->actingAs($admin)->put(route('admin.courses.update', $course), $this->coursePayload(['price' => $input]))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseMissing('courses', ['title_en' => 'Price Test']);
        $this->assertSame('25.00', $course->fresh()->price);
    }

    public function test_course_edit_form_price_field_allows_cents(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['price' => 5.99, 'access_type' => 'paid']);

        $html = $this->actingAs($admin)->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('value="5.99"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/<input\s[^>]*name="price"[^>]*step="0\.01"/', $html);
    }

    public function test_decimal_prices_show_cents_and_whole_prices_stay_whole(): void
    {
        Course::factory()->create(['title_en' => 'Cents Course', 'price' => 5.99, 'access_type' => 'paid']);
        Course::factory()->create(['title_en' => 'Whole Course', 'price' => 35, 'access_type' => 'paid']);

        $this->get('/courses')
            ->assertOk()
            ->assertSee('$5.99', false)
            ->assertSee('$35<', false);
    }
}
