<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The teacher's side of assignments: set one for a course, and review what
 * students hand in (status, grade out of 10, feedback in both languages).
 */
class AssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = Assignment::query()
            ->with('course')
            ->withCount([
                'submissions',
                'submissions as waiting_count' => fn ($q) => $q->whereIn('status', ['submitted', 'review']),
            ])
            ->orderByDesc('id')
            ->get();

        $queue = AssignmentSubmission::query()
            ->with(['assignment.course', 'user'])
            ->whereIn('status', ['submitted', 'review'])
            ->oldest()
            ->take(30)
            ->get();

        return view('admin.assignments.index', compact('assignments', 'queue'));
    }

    public function create(): View
    {
        return view('admin.assignments.form', [
            'assignment' => new Assignment(['is_published' => true, 'number' => (int) Assignment::query()->max('number') + 1]),
            'courses' => Course::query()->orderBy('sort_order')->get(['id', 'title_en']),
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        return view('admin.assignments.form', [
            'assignment' => $assignment,
            'courses' => Course::query()->orderBy('sort_order')->get(['id', 'title_en']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $assignment = new Assignment;
        $this->fill($request, $assignment);
        $assignment->save();

        Log::info('Admin set an assignment', ['admin_id' => $request->user()->getKey(), 'assignment_id' => $assignment->id]);

        return redirect()->route('admin.assignments')->with('status', "Assignment {$assignment->code()} \"{$assignment->title_en}\" is set.");
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->fill($request, $assignment);
        $assignment->save();

        return redirect()->route('admin.assignments')->with('status', "\"{$assignment->title_en}\" is saved.");
    }

    public function toggle(Assignment $assignment): RedirectResponse
    {
        $assignment->forceFill(['is_published' => ! $assignment->is_published])->save();

        return back()->with('status', $assignment->is_published ? "\"{$assignment->title_en}\" is visible to students." : "\"{$assignment->title_en}\" is hidden from students.");
    }

    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $title = $assignment->title_en;

        foreach ($assignment->submissions()->whereNotNull('file_path')->pluck('file_path') as $path) {
            Storage::disk('local')->delete($path);
        }
        if (filled($assignment->brief_path)) {
            Storage::disk('local')->delete($assignment->brief_path);
        }
        $this->forgetPicture($assignment->cover_url);
        $assignment->delete();   // submissions go with it (cascade)

        Log::info('Admin deleted an assignment', ['admin_id' => $request->user()->getKey(), 'title' => $title]);

        return redirect()->route('admin.assignments')->with('status', "\"{$title}\" and the work handed in for it are deleted.");
    }

    // ------------------------------------------------------------------ review

    public function review(AssignmentSubmission $submission): View
    {
        $submission->load(['assignment.course', 'user', 'reviewer']);

        return view('admin.assignments.review', [
            'submission' => $submission,
            'versions' => $submission->assignment->submissions()->where('user_id', $submission->user_id)->orderByDesc('version')->get(),
        ]);
    }

    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['review', 'revision', 'approved'])],
            'grade' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'feedback_en' => ['nullable', 'string', 'max:3000'],
            'feedback_km' => ['nullable', 'string', 'max:3000'],
        ]);

        $finished = $data['status'] !== 'review';

        $submission->fill([
            'status' => $data['status'],
            'grade' => $data['grade'] ?? null,
            'feedback_en' => filled($data['feedback_en'] ?? null) ? trim($data['feedback_en']) : null,
            'feedback_km' => filled($data['feedback_km'] ?? null) ? trim($data['feedback_km']) : null,
            'reviewed_by' => $finished ? $request->user()->getKey() : $submission->reviewed_by,
            'reviewed_at' => $finished ? now() : $submission->reviewed_at,
        ])->save();

        return redirect()->route('admin.assignments')->with('status', "{$submission->user->name}'s work is marked \"{$submission->statusLabel('en')}\".");
    }

    // ------------------------------------------------------------------------

    private function fill(Request $request, Assignment $assignment): void
    {
        $data = $request->validate([
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'number' => ['required', 'integer', 'min:1', 'max:999'],
            'title_en' => ['required', 'string', 'max:160'],
            'title_km' => ['nullable', 'string', 'max:160'],
            'brief_en' => ['nullable', 'string', 'max:5000'],
            'brief_km' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:3000'],
            'format' => ['nullable', 'string', 'max:120'],
            'due_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'brief_file' => ['nullable', 'file', 'max:20480', 'extensions:pdf,zip,jpg,jpeg,png,docx'],
        ], [
            'cover.max' => 'The picture must be smaller than 2 MB.',
            'brief_file.max' => 'The brief must be smaller than 20 MB.',
            'brief_file.extensions' => 'The brief can be a PDF, ZIP, JPG, PNG or DOCX file.',
        ], [
            'course_id' => 'course',
            'title_en' => 'English title',
        ]);

        $assignment->fill([
            'course_id' => (int) $data['course_id'],
            'number' => (int) $data['number'],
            'title_en' => trim($data['title_en']),
            'title_km' => filled($data['title_km'] ?? null) ? trim($data['title_km']) : null,
            'brief_en' => filled($data['brief_en'] ?? null) ? trim($data['brief_en']) : null,
            'brief_km' => filled($data['brief_km'] ?? null) ? trim($data['brief_km']) : null,
            'requirements' => collect(preg_split('/\r\n|\r|\n/', (string) ($data['requirements'] ?? '')))->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->take(12)->values()->all(),
            'format' => filled($data['format'] ?? null) ? trim($data['format']) : null,
            'due_at' => $data['due_at'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ]);

        if ($file = $request->file('cover')) {
            $this->forgetPicture($assignment->cover_url);
            $assignment->cover_url = '/storage/'.$file->store('assignment-covers', 'public');
        } elseif ($request->boolean('remove_cover')) {
            $this->forgetPicture($assignment->cover_url);
            $assignment->cover_url = null;
        }

        if ($brief = $request->file('brief_file')) {
            if (filled($assignment->brief_path)) {
                Storage::disk('local')->delete($assignment->brief_path);
            }
            $assignment->brief_path = $brief->store('assignment-briefs', 'local');
            $assignment->brief_name = Str::limit(preg_replace('/[^\pL\pN ._()-]+/u', '-', $brief->getClientOriginalName()), 150, '');
        } elseif ($request->boolean('remove_brief') && filled($assignment->brief_path)) {
            Storage::disk('local')->delete($assignment->brief_path);
            $assignment->brief_path = null;
            $assignment->brief_name = null;
        }
    }

    private function forgetPicture(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/assignment-covers/')) {
            Storage::disk('public')->delete(Str::after($url, '/storage/'));
        }
    }
}
