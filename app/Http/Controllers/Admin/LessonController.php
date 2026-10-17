<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LessonController extends Controller
{
    /** The links your lesson player already knows how to embed. */
    private const LINKS = [
        'youtube' => '#^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/#i',
        'drive' => '#^https?://drive\.google\.com/#i',
    ];

    public function store(Request $request, Course $course): RedirectResponse
    {
        if (! Schema::hasColumn('lessons', 'video_path')) {
            return back()->with('error', 'Run "php artisan migrate" first. The video upload columns are not in your database yet.');
        }

        $data = $this->validated($request, $course, updating: false);

        $attrs = [
            'module_id' => (int) $data['module_id'],
            'title_en' => $data['title_en'],
            'title_km' => $data['title_km'],
            'duration_minutes' => $data['duration_minutes'],
            'is_preview' => $data['is_preview'],
            'notes_en' => $data['notes_en'],
            'notes_km' => $data['notes_km'],
            'sort_order' => $this->nextSort((int) $data['module_id']),
        ] + $this->videoAttributes($data);

        try {
            $lesson = new Lesson;
            $lesson->forceFill($attrs)->save();
        } catch (QueryException $e) {
            return back()->withInput()->with('error', $this->dbMessage($e));
        }

        return back()->with('status', 'Lesson added.');
    }

    public function update(Request $request, Course $course, $lessonId): RedirectResponse
    {
        $lesson = $this->find($course, $lessonId);
        $data = $this->validated($request, $course, updating: true, lesson: $lesson);
        $oldPath = $lesson->video_path;

        $attrs = [
            'module_id' => (int) $data['module_id'],
            'title_en' => $data['title_en'],
            'title_km' => $data['title_km'],
            'duration_minutes' => $data['duration_minutes'],
            'is_preview' => $data['is_preview'],
            'notes_en' => $data['notes_en'],
            'notes_km' => $data['notes_km'],
        ];

        // Moved to another module: it goes to the end of that module.
        if ((int) $lesson->module_id !== (int) $data['module_id']) {
            $attrs['sort_order'] = $this->nextSort((int) $data['module_id']);
        }

        if ($data['video_host'] !== 'keep') {
            $attrs += $this->videoAttributes($data);
        }

        try {
            $lesson->forceFill($attrs)->save();
        } catch (QueryException $e) {
            return back()->withInput()->with('error', $this->dbMessage($e));
        }

        // The old file is only removed after the new details are safely saved.
        if ($oldPath && array_key_exists('video_path', $attrs) && $oldPath !== $attrs['video_path']) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('status', 'Lesson saved.');
    }

    public function destroy(Course $course, $lessonId): RedirectResponse
    {
        $lesson = $this->find($course, $lessonId);
        $path = $lesson->video_path;

        $lesson->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('status', 'Lesson deleted.');
    }

    /** Move a lesson one step up or down inside its module, then renumber the module 1..n. */
    public function move(Request $request, Course $course, $lessonId): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $lesson = $this->find($course, $lessonId);

        $ids = Lesson::query()
            ->where('module_id', $lesson->module_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $from = array_search((int) $lesson->id, $ids, true);
        $to = $direction === 'up' ? $from - 1 : $from + 1;

        if ($from !== false && isset($ids[$to])) {
            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $index => $id) {
                Lesson::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        }

        return back();
    }

    // ------------------------------------------------------------------------

    /**
     * A lesson, but only if it really belongs to this course.
     *
     * Matched on the model's own route key rather than on the primary key.
     * These actions take the lesson as a plain string (there is no route-model
     * binding on it), so what arrives is whatever the routes and the admin form
     * emit -- the uuid while public-facing models are routed on one, the id
     * otherwise. whereKey() would always read it as the id, which 404s every
     * lesson edit, delete and reorder the moment the two differ.
     */
    private function find(Course $course, $lessonId): Lesson
    {
        return Lesson::query()
            ->whereIn('module_id', Module::query()->where('course_id', $course->id)->pluck('id'))
            ->where((new Lesson)->getRouteKeyName(), $lessonId)
            ->firstOrFail();
    }

    private function nextSort(int $moduleId): int
    {
        return (int) Lesson::query()->where('module_id', $moduleId)->max('sort_order') + 1;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, Course $course, bool $updating, ?Lesson $lesson = null): array
    {
        $hosts = $updating ? 'youtube,drive,upload,keep' : 'youtube,drive,upload';

        $data = $request->validate([
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->where('course_id', $course->id)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['nullable', 'string', 'max:255'],
            'video_host' => ['required', "in:{$hosts}"],
            'video_url' => ['nullable', 'string', 'max:2048'],
            'video_path' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'is_preview' => ['nullable', 'boolean'],
            'notes_en' => ['nullable', 'string', 'max:20000'],
            'notes_km' => ['nullable', 'string', 'max:20000'],
        ]);

        $host = $data['video_host'];
        $url = trim((string) ($data['video_url'] ?? ''));
        $path = (string) ($data['video_path'] ?? '');

        if (isset(self::LINKS[$host]) && $url !== '' && ! preg_match(self::LINKS[$host], $url)) {
            throw ValidationException::withMessages([
                'video_url' => $host === 'youtube'
                    ? 'Paste a YouTube link, for example https://youtu.be/abc123.'
                    : 'Paste a Google Drive link, for example https://drive.google.com/file/d/.../view.',
            ]);
        }

        if ($host === 'upload') {
            // Only files this panel put in lesson-videos/ are accepted, and never one that
            // already belongs to another lesson.
            $valid = preg_match('#^lesson-videos/[A-Za-z0-9-]+\.(mp4|webm|mov|m4v)$#', $path)
                && Storage::disk('local')->exists($path)
                && ! Lesson::query()
                    ->where('video_path', $path)
                    ->when($lesson, fn ($q) => $q->where('id', '!=', $lesson->id))
                    ->exists();

            if (! $valid) {
                throw ValidationException::withMessages([
                    'video_path' => 'The video file was not uploaded. Choose a file and wait for it to reach 100%.',
                ]);
            }
        }

        $data['video_url'] = $url !== '' ? $url : null;
        $data['video_path'] = $path !== '' ? $path : null;
        $data['title_km'] = filled($data['title_km'] ?? null) ? $data['title_km'] : $data['title_en'];
        $data['duration_minutes'] = (int) ($data['duration_minutes'] ?? 0);
        $data['is_preview'] = (bool) ($data['is_preview'] ?? false);
        $data['notes_en'] = $data['notes_en'] ?? null;
        $data['notes_km'] = $data['notes_km'] ?? null;

        return $data;
    }

    /** The video columns for the chosen source. */
    private function videoAttributes(array $data): array
    {
        if ($data['video_host'] === 'upload') {
            return [
                'video_host' => 'upload',
                'video_url' => null,
                'video_path' => $data['video_path'],
                'video_size' => (int) Storage::disk('local')->size($data['video_path']),
            ];
        }

        return [
            'video_host' => $data['video_host'],
            'video_url' => $data['video_url'],
            'video_path' => null,
            'video_size' => null,
        ];
    }

    private function dbMessage(QueryException $e): string
    {
        return 'The database rejected this change: '.Str::limit((string) ($e->errorInfo[2] ?? $e->getMessage()), 200);
    }
}
