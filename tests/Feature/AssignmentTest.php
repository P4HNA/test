<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function enrolled(Assignment $assignment): User
    {
        $user = User::factory()->create();
        Enrollment::query()->create(['user_id' => $user->id, 'course_id' => $assignment->course_id, 'enrolled_at' => now()]);

        return $user;
    }

    public function test_enrolled_students_see_their_assignments(): void
    {
        $assignment = Assignment::factory()->create();
        $other = Assignment::factory()->create(['title_en' => 'Logo Grid Study']);
        $student = $this->enrolled($assignment);

        $this->actingAs($student)->get(route('assignments.index'))
            ->assertOk()
            ->assertSee('Assignments &amp;', false)
            ->assertSee('Typography Poster')
            ->assertDontSee('Logo Grid Study');

        $this->actingAs($student)->get(route('assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Assignment #03')
            ->assertSee('Dimensions: 1080 × 1350 px')
            ->assertSee('Not Submitted');

        $this->actingAs($student)->get(route('assignments.show', $other))->assertNotFound();
    }

    public function test_hidden_assignments_stay_hidden(): void
    {
        $assignment = Assignment::factory()->hidden()->create();

        $this->actingAs($this->enrolled($assignment))->get(route('assignments.show', $assignment))->assertNotFound();
    }

    public function test_a_student_hands_in_work_and_each_upload_is_a_new_version(): void
    {
        Storage::fake('local');
        $assignment = Assignment::factory()->create();
        $student = $this->enrolled($assignment);

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [
            'work' => UploadedFile::fake()->image('poster.png', 1080, 1350),
            'note' => 'Bold display headline with a geometric sans.',
        ])->assertRedirect(route('assignments.show', $assignment));

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [
            'link' => 'https://www.behance.net/gallery/123/poster',
        ])->assertRedirect();

        $versions = AssignmentSubmission::query()->orderBy('version')->get();
        $this->assertSame([1, 2], $versions->pluck('version')->all());
        $this->assertSame('poster.png', $versions[0]->file_name);
        Storage::disk('local')->assertExists($versions[0]->file_path);
        $this->assertSame('submitted', $versions[1]->status);

        $this->actingAs($student)->get(route('assignments.work', $versions[0]))->assertOk();
    }

    public function test_work_needs_a_file_or_a_link_of_an_allowed_type(): void
    {
        $assignment = Assignment::factory()->create();
        $student = $this->enrolled($assignment);

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [])->assertSessionHasErrors('work');
        $this->actingAs($student)->post(route('assignments.submit', $assignment), ['work' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('work');
        $this->assertSame(0, AssignmentSubmission::query()->count());
    }

    public function test_nobody_else_can_open_a_students_work(): void
    {
        Storage::fake('local');
        $assignment = Assignment::factory()->create();
        $student = $this->enrolled($assignment);
        $this->actingAs($student)->post(route('assignments.submit', $assignment), ['work' => UploadedFile::fake()->image('a.png')]);
        $submission = AssignmentSubmission::query()->sole();

        $this->actingAs($this->enrolled($assignment))->get(route('assignments.work', $submission))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get(route('assignments.work', $submission))->assertOk();
    }

    public function test_the_teacher_reviews_and_the_student_sees_the_critique(): void
    {
        $assignment = Assignment::factory()->create();
        $student = $this->enrolled($assignment);
        $submission = AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'version' => 1, 'link_url' => 'https://example.com/work', 'status' => 'submitted']);
        $admin = User::factory()->admin()->create(['name' => 'Piseth Yoeurn']);

        $this->actingAs($admin)->get(route('admin.assignments'))->assertOk()->assertSee('Waiting for your review')->assertSee($student->name);
        $this->actingAs($admin)->get(route('admin.submissions.review', $submission))->assertOk();
        $this->actingAs($admin)->patch(route('admin.submissions.grade', $submission), [
            'status' => 'revision',
            'grade' => '8.8',
            'feedback_en' => 'Improve hierarchy and spacing between the header and the body.',
        ])->assertRedirect(route('admin.assignments'));

        $this->actingAs($student)->get(route('assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Revision Required')
            ->assertSee('Grade: 8.8 / 10')
            ->assertSee('Improve hierarchy and spacing')
            ->assertSee('Resubmit Assignment v2');
    }

    public function test_approved_work_cannot_be_replaced(): void
    {
        $assignment = Assignment::factory()->create();
        $student = $this->enrolled($assignment);
        AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'version' => 1, 'link_url' => 'https://example.com/a', 'status' => 'approved', 'reviewed_at' => now()]);

        $this->actingAs($student)->post(route('assignments.submit', $assignment), ['link' => 'https://example.com/b'])->assertSessionHasErrors('work');
        $this->assertSame(1, AssignmentSubmission::query()->count());
    }

    public function test_the_teacher_sets_an_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $course = \App\Models\Course::factory()->create();

        $this->actingAs($admin)->get(route('admin.assignments.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.assignments.store'), [
            'course_id' => $course->id,
            'number' => 4,
            'title_en' => 'Brand Identity Board',
            'requirements' => "- Logo on light and dark\n- Colour palette with HEX codes\n\n",
            'is_published' => '1',
        ])->assertRedirect(route('admin.assignments'));

        $assignment = Assignment::query()->sole();
        $this->assertSame(['Logo on light and dark', 'Colour palette with HEX codes'], $assignment->requirements);
        $this->actingAs($admin)->get(route('admin.assignments.edit', $assignment))->assertOk()->assertSee('Brand Identity Board');
    }

    public function test_students_cannot_reach_the_assignment_admin(): void
    {
        $submission = AssignmentSubmission::query()->create([
            'assignment_id' => Assignment::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'submitted',
        ]);

        $this->actingAs(User::factory()->create())->get(route('admin.assignments'))->assertForbidden();
        $this->actingAs(User::factory()->create())->patch(route('admin.submissions.grade', $submission), ['status' => 'approved'])->assertForbidden();
    }
}
