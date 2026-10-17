<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Certificate requests.
 *
 * A student who finished a course sends the name to print and a Telegram
 * username. The admin opens that Telegram, checks the person is real, and then
 * approves (which issues the certificate in the name they typed) or rejects
 * with a note the student can read before sending it again.
 */
class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', CertificateRequest::PENDING);
        if (! in_array($status, [CertificateRequest::PENDING, CertificateRequest::APPROVED, CertificateRequest::REJECTED, 'all'], true)) {
            $status = CertificateRequest::PENDING;
        }

        $requests = CertificateRequest::query()
            ->with(['user', 'course', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.certificates.index', [
            'requests' => $requests,
            'status' => $status,
            'counts' => [
                CertificateRequest::PENDING => CertificateRequest::where('status', CertificateRequest::PENDING)->count(),
                CertificateRequest::APPROVED => CertificateRequest::where('status', CertificateRequest::APPROVED)->count(),
                CertificateRequest::REJECTED => CertificateRequest::where('status', CertificateRequest::REJECTED)->count(),
            ],
        ]);
    }

    public function approve(Request $request, CertificateRequest $certificateRequest): RedirectResponse
    {
        if ($certificateRequest->status === CertificateRequest::APPROVED) {
            return back()->with('status', 'That request is already approved.');
        }

        $course = $certificateRequest->course;
        $student = $certificateRequest->user;

        // Never issue for someone who has not really finished the course.
        if (! $course || ! $student || $course->progressPercent($student) < 100) {
            return back()->with('error', 'This student has not finished the course, so no certificate was issued.');
        }

        DB::transaction(function () use ($request, $certificateRequest) {
            $certificate = Certificate::query()
                ->where('user_id', $certificateRequest->user_id)
                ->where('course_id', $certificateRequest->course_id)
                ->first();

            if ($certificate) {
                // One issued before this flow existed: keep its code and date, only
                // print the real name the admin has now checked.
                $certificate->update(['holder_name' => $certificateRequest->real_name]);
            } else {
                Certificate::query()->create([
                    'user_id' => $certificateRequest->user_id,
                    'course_id' => $certificateRequest->course_id,
                    'code' => $this->newCode(),
                    'holder_name' => $certificateRequest->real_name,
                    'issued_at' => now(),
                ]);
            }

            $certificateRequest->update([
                'status' => CertificateRequest::APPROVED,
                'admin_note' => null,
                'reviewed_by' => $request->user()->getKey(),
                'reviewed_at' => now(),
            ]);
        });

        Log::info('Admin approved a certificate request', [
            'admin_id' => $request->user()->getKey(),
            'request_id' => $certificateRequest->id,
        ]);

        return back()->with('status', 'Certificate issued to '.$certificateRequest->real_name.'.');
    }

    public function reject(Request $request, CertificateRequest $certificateRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'admin_note.required' => 'Write a short reason so the student knows what to fix.',
        ]);

        if ($certificateRequest->status === CertificateRequest::APPROVED) {
            return back()->with('error', 'This request was already approved and the certificate issued.');
        }

        $certificateRequest->update([
            'status' => CertificateRequest::REJECTED,
            'admin_note' => trim($data['admin_note']),
            'reviewed_by' => $request->user()->getKey(),
            'reviewed_at' => now(),
        ]);

        Log::info('Admin rejected a certificate request', [
            'admin_id' => $request->user()->getKey(),
            'request_id' => $certificateRequest->id,
        ]);

        return back()->with('status', 'Request rejected. The student can read your note and send it again.');
    }

    /**
     * Undo an approval (a mis-click): the request goes back to Waiting and the
     * certificate it issued is withdrawn, so the admin can decide again.
     * A certificate that was issued before this request existed is left alone,
     * since it was not this approval that created it.
     */
    public function revoke(Request $request, CertificateRequest $certificateRequest): RedirectResponse
    {
        if ($certificateRequest->status !== CertificateRequest::APPROVED) {
            return back()->with('error', 'Only an approved request can be taken back.');
        }

        DB::transaction(function () use ($certificateRequest) {
            $certificate = Certificate::query()
                ->where('user_id', $certificateRequest->user_id)
                ->where('course_id', $certificateRequest->course_id)
                ->first();

            if ($certificate && $certificate->issued_at && $certificate->issued_at->gte($certificateRequest->created_at)) {
                $certificate->delete();
            }

            $certificateRequest->update([
                'status' => CertificateRequest::PENDING,
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        });

        Log::info('Admin took back a certificate approval', [
            'admin_id' => $request->user()->getKey(),
            'request_id' => $certificateRequest->id,
        ]);

        return back()->with('status', 'Approval taken back. The request is waiting again.');
    }

    /** A fresh certificate code, retried on the unlikely chance it is already taken. */
    private function newCode(): string
    {
        do {
            $code = 'PD-CERT-'.now()->format('Y').'-'.Str::upper(Str::random(4));
        } while (Certificate::where('code', $code)->exists());

        return $code;
    }
}
