<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateRequestController extends Controller
{
    /** The page a student sees after finishing a course: form, or the status of their request. */
    public function show(Request $request, Course $course): View
    {
        return view('certificates.request', $this->state($request, $course));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $state = $this->state($request, $course);

        // Only someone who really finished the course can ask.
        abort_unless($state['finished'], 403);

        if ($state['certificate']) {
            return redirect()->route('certificates.show', $state['certificate']);
        }

        // Pending or approved requests are not sent twice. A rejected one can be sent again.
        $existing = $state['certRequest'];
        if ($existing && $existing->status !== CertificateRequest::REJECTED) {
            return redirect()->route('certificates.request', $course);
        }

        // "@name", "t.me/name" and "https://t.me/name" all become "name".
        $request->merge([
            'telegram_username' => preg_replace(
                '#^(https?://)?(t\.me/|telegram\.me/)?@?#i',
                '',
                trim((string) $request->input('telegram_username'))
            ),
        ]);

        $data = $request->validate([
            'real_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\pM][\pL\pM\s.\'\-]*$/u'],
            'telegram_username' => ['required', 'string', 'regex:/^[A-Za-z][A-Za-z0-9_]{4,31}$/'],
        ], [
            'real_name.regex' => __('Use letters only, as it is written on your ID.'),
            'telegram_username.regex' => __('A Telegram username has 5 to 32 letters, numbers or underscores, and starts with a letter.'),
        ]);

        CertificateRequest::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            [
                'real_name' => trim($data['real_name']),
                'telegram_username' => $data['telegram_username'],
                'status' => CertificateRequest::PENDING,
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return redirect()->route('certificates.request', $course);
    }

    private function state(Request $request, Course $course): array
    {
        $user = $request->user();

        $enrolled = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        $progress = $enrolled ? $course->progressPercent($user) : 0;

        return [
            'course' => $course,
            'enrolled' => $enrolled,
            'progress' => $progress,
            'finished' => $enrolled && $progress >= 100,
            'certificate' => Certificate::where('user_id', $user->id)->where('course_id', $course->id)->first(),
            'certRequest' => CertificateRequest::where('user_id', $user->id)->where('course_id', $course->id)->first(),
        ];
    }
}
