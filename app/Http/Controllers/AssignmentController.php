<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Support\StudentWork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The student's side of assignments: the list for their courses, one
 * assignment with its brief and the teacher's review, and handing in work.
 * Every hand-in is kept as its own version.
 */
class AssignmentController extends Controller
{
    /** Largest piece of work a student can upload, in kilobytes (30 MB). */
    public const MAX_WORK_KB = 30720;

    /** File types accepted as work: pictures, PDFs, and design or zip files. */
    public const WORK_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'zip', 'psd', 'ai', 'fig'];

    /** Pictures the browser may show in the page instead of downloading. */
    private const INLINE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $assignments = StudentWork::assignments($user);
        $quizzes = StudentWork::quizzes($user);

        return view('assignments.index', [
            'assignments' => $assignments,
            'latest' => StudentWork::latestSubmissions($user, $assignments),
            'quizzes' => $quizzes,
            'best' => StudentWork::bestAttempts($user, $quizzes),
        ]);
    }

    public function show(Request $request, Assignment $assignment): View
    {
        abort_unless($assignment->isOpenTo($request->user()), 404);

        $assignment->load('course');
        $versions = $assignment->submissions()
            ->where('user_id', $request->user()->getKey())
            ->with('reviewer')
            ->orderByDesc('version')
            ->get();

        return view('assignments.show', [
            'assignment' => $assignment,
            'versions' => $versions,
            'current' => $versions->first(),
        ]);
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($assignment->isOpenTo($user), 404);

        $current = $assignment->submissions()->where('user_id', $user->getKey())->orderByDesc('version')->first();

        if ($current?->status === 'approved') {
            throw ValidationException::withMessages(['work' => __('This assignment is already approved.')]);
        }

        $data = $request->validate([
            'work' => ['nullable', 'file', 'max:'.self::MAX_WORK_KB, 'extensions:'.implode(',', self::WORK_EXTENSIONS)],
            'link' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'work.max' => __('The file may not be larger than 30 MB.'),
            'work.extensions' => __('Upload a JPG, PNG, WebP, PDF, ZIP, PSD, AI or FIG file.'),
        ]);

        if (! $request->hasFile('work') && blank($data['link'] ?? null)) {
            throw ValidationException::withMessages(['work' => __('Choose a file or paste a link to your work.')]);
        }

        $submission = new AssignmentSubmission([
            'assignment_id' => $assignment->id,
            'user_id' => $user->getKey(),
            'version' => ($current?->version ?? 0) + 1,
            'link_url' => $data['link'] ?? null,
            'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
            'status' => 'submitted',
        ]);

        if ($file = $request->file('work')) {
            $submission->fill([
                'file_path' => $file->store('assignment-work/'.$assignment->uuid, 'local'),
                'file_name' => $this->safeName($file->getClientOriginalName()),
                'file_size' => $file->getSize(),
                'file_mime' => $file->getMimeType(),
            ]);
        }

        $submission->save();

        return redirect()->route('assignments.show', $assignment)->with('status', 'work-submitted');
    }

    /** The teacher's brief (a PDF or similar), for students who can open the assignment. */
    public function brief(Request $request, Assignment $assignment): StreamedResponse
    {
        abort_unless($assignment->isOpenTo($request->user()) && filled($assignment->brief_path), 404);
        abort_unless(Storage::disk('local')->exists($assignment->brief_path), 404);

        return Storage::disk('local')->download($assignment->brief_path, $assignment->brief_name ?: 'brief');
    }

    /** A handed-in file: shown in the page when it is a picture, downloaded otherwise. Owner or teacher only. */
    public function work(Request $request, AssignmentSubmission $submission): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $submission->user_id === $user->getKey(), 404);
        abort_unless(filled($submission->file_path) && Storage::disk('local')->exists($submission->file_path), 404);

        $inline = in_array($submission->file_mime, self::INLINE_MIMES, true) && ! $request->boolean('download');

        return Storage::disk('local')->response(
            $submission->file_path,
            $submission->file_name,
            ['Content-Type' => $inline ? $submission->file_mime : 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'],
            $inline ? 'inline' : 'attachment'
        );
    }

    private function safeName(string $name): string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = Str::limit(trim(preg_replace('/[^\pL\pN ._()-]+/u', '-', pathinfo($name, PATHINFO_FILENAME)), '-. '), 120, '');

        return ($base !== '' ? $base : 'work').($ext !== '' ? '.'.$ext : '');
    }
}
