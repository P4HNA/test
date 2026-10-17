<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** An admin can take back a certificate approval made by mistake. */
class CertificateRevokeTest extends TestCase
{
    use RefreshDatabase;

    private function approvedRequest(?\DateTimeInterface $issuedAt = null): array
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $request = CertificateRequest::query()->create([
            'user_id' => $student->id, 'course_id' => $course->id, 'real_name' => 'Sok Dara',
            'telegram_username' => 'sokdara', 'status' => CertificateRequest::APPROVED, 'reviewed_at' => now(),
        ]);
        $certificate = Certificate::query()->create([
            'user_id' => $student->id, 'course_id' => $course->id, 'code' => 'PD-CERT-TEST-0001',
            'holder_name' => 'Sok Dara', 'issued_at' => $issuedAt ?? now()->addMinute(),
        ]);

        return [$request, $certificate];
    }

    public function test_taking_back_an_approval_withdraws_the_certificate_and_waits_again(): void
    {
        [$request, $certificate] = $this->approvedRequest();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.certificates.revoke', $request))->assertRedirect();

        $this->assertSame(CertificateRequest::PENDING, $request->fresh()->status);
        $this->assertNull($request->fresh()->reviewed_by);
        $this->assertModelMissing($certificate);
    }

    public function test_a_certificate_that_existed_before_the_request_is_kept(): void
    {
        [$request, $certificate] = $this->approvedRequest(now()->subYear());
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.certificates.revoke', $request))->assertRedirect();

        $this->assertSame(CertificateRequest::PENDING, $request->fresh()->status);
        $this->assertModelExists($certificate);
    }

    public function test_only_approved_requests_can_be_taken_back_and_only_by_admins(): void
    {
        [$request] = $this->approvedRequest();
        $this->actingAs(User::factory()->create())->patch(route('admin.certificates.revoke', $request))->assertForbidden();

        $pending = CertificateRequest::query()->create([
            'user_id' => $request->user_id, 'course_id' => Course::factory()->create()->id, 'real_name' => 'X Y',
            'telegram_username' => 'xy', 'status' => CertificateRequest::PENDING,
        ]);
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.certificates.revoke', $pending))
            ->assertSessionHas('error');
        $this->assertSame(CertificateRequest::PENDING, $pending->fresh()->status);
    }

    public function test_the_page_offers_undo_and_a_reject_dialog(): void
    {
        [$request] = $this->approvedRequest();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.certificates', ['status' => 'approved']))
            ->assertOk()->assertSee('Undo')->assertSee('data-confirm-title="Take back this approval?"', false)->assertSee('Reject this request?');
    }
}
