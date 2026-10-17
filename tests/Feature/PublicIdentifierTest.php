<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * URLs identify records by uuid, not by a sequential id.
 *
 * Counting ids is how you learn how many customers and payments a business
 * has, and it is how you walk a record you were never linked to. These tests
 * pin the two halves of that: the uuid is always there, and the old numeric
 * URL no longer resolves.
 */
class PublicIdentifierTest extends TestCase
{
    use RefreshDatabase;

    /** Every model that appears in a URL, and the key it should route on. */
    public static function routedModels(): array
    {
        return [
            'user' => [User::class, 'uuid'],
            'course' => [Course::class, 'slug'],
            'module' => [Module::class, 'uuid'],
            'lesson' => [Lesson::class, 'uuid'],
            'payment' => [Payment::class, 'uuid'],
            'certificate' => [Certificate::class, 'uuid'],
            'promotion' => [Promotion::class, 'uuid'],
        ];
    }

    #[DataProvider('routedModels')]
    public function test_every_routed_model_has_a_uuid_column(string $class, string $routeKey): void
    {
        $this->assertTrue(
            in_array('uuid', Schema::getColumnListing((new $class)->getTable()), true),
            $class.' should carry a uuid.'
        );

        $this->assertSame($routeKey, (new $class)->getRouteKeyName());
    }

    public function test_a_new_record_is_given_a_uuid_automatically(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $user->uuid
        );
    }

    public function test_uuids_are_not_reused(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->assertNotSame($a->uuid, $b->uuid);
    }

    /**
     * A course is the one model that keeps a readable key, because its URL is
     * public and meant to be shared. Admin still routes it on the uuid, so
     * renaming a course cannot break an admin bookmark.
     */
    public function test_a_course_stays_on_its_slug_in_public_urls_but_not_in_admin(): void
    {
        $course = Course::factory()->create(['slug' => 'a-readable-slug']);

        $this->assertSame('/courses/a-readable-slug', route('courses.show', $course, false));
        $this->assertSame(
            '/admin/courses/'.$course->uuid.'/edit',
            route('admin.courses.edit', $course, false)
        );
    }

    public function test_admin_urls_carry_uuids_rather_than_ids(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        foreach ([
            route('admin.users.show', $user, false),
            route('admin.users.ban', $user, false),
            route('admin.courses.edit', $course, false),
            route('admin.videos.show', $course, false),
        ] as $url) {
            $this->assertStringNotContainsString('/'.$user->id.'/', $url.'/');
            $this->assertDoesNotMatchRegularExpression('#/\d+(/|$)#', $url, $url.' still exposes a numeric id.');
        }
    }

    public function test_a_sequential_id_no_longer_opens_an_admin_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();

        // The record is reachable by uuid...
        $this->actingAs($admin)->get(route('admin.users.show', $target))->assertOk();

        // ...but guessing its position in the table gets you nothing.
        $this->actingAs($admin)->get('/admin/users/'.$target->id)->assertNotFound();
        $this->actingAs($admin)->get('/admin/users/1')->assertNotFound();
    }

    public function test_a_students_own_pages_are_not_walkable_by_id(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $certificate = Certificate::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'code' => 'CERT-TEST-1',
            'issued_at' => now(),
        ]);

        $this->assertSame(
            '/certificates/'.$certificate->uuid,
            route('certificates.show', $certificate, false)
        );

        $this->actingAs($student)->get('/certificates/'.$certificate->id)->assertNotFound();
    }
}
