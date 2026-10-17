<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    private const CATEGORIES = ['Photoshop', 'Illustrator', 'InDesign', 'Graphic Design', 'UI/UX', 'Motion Graphics'];

    private const LEVELS = ['Beginner', 'Intermediate', 'All Levels'];

    public function index(Request $request)
    {
        $courses = Course::query()
            ->withCount(['modules', 'lessons', 'enrollments'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $uploads = collect();

        if (Schema::hasColumn('lessons', 'video_path')) {
            $uploads = Lesson::query()
                ->join('modules', 'modules.id', '=', 'lessons.module_id')
                ->where('lessons.video_host', 'upload')
                ->select('modules.course_id', DB::raw('COUNT(*) as c'))
                ->groupBy('modules.course_id')
                ->pluck('c', 'course_id');
        }

        // Lessons a student would open to an empty player, per course.
        $missingVideos = Lesson::query()
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where(fn ($q) => Lesson::constrainToMissingVideo($q, 'lessons.'))
            ->select('modules.course_id', DB::raw('COUNT(*) as c'))
            ->groupBy('modules.course_id')
            ->pluck('c', 'course_id');

        $status = in_array($request->query('status'), ['published', 'draft'], true) ? $request->query('status') : 'all';

        return view('admin.courses.index', [
            'courses' => $courses->when($status !== 'all', fn ($all) => $all->filter(
                fn (Course $c) => $c->isPublished() === ($status === 'published')
            )),
            'uploads' => $uploads,
            'missingVideos' => $missingVideos,
            'status' => $status,
            'counts' => [
                'all' => $courses->count(),
                'published' => $courses->filter->isPublished()->count(),
                'draft' => $courses->reject->isPublished()->count(),
            ],
        ]);
    }

    public function create()
    {
        $course = new Course([
            'mark' => '',
            'category' => self::CATEGORIES[0],
            'level' => self::LEVELS[0],
            'price' => 0,
            'instructor_name' => 'Piseth Yoeurn',
        ]);

        return view('admin.courses.form', $this->formData($course));
    }

    public function edit(Course $course)
    {
        return view('admin.courses.form', $this->formData($course));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug((string) ($data['slug'] ?? ''), $data['title_en']);
        $data['sort_order'] = (int) Course::query()->max('sort_order') + 1;
        // A new course starts hidden unless the admin chose to publish it straight away.
        $data['is_published'] = $request->boolean('is_published');
        $data['thumbnail_url'] = $this->resolveThumbnail($request, null);

        try {
            $course = Course::query()->create($data);
        } catch (QueryException $e) {
            return back()->withInput()->with('error', $this->dbMessage($e));
        }

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('status', 'Course created. Add its modules and lessons below.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_published'] = $request->boolean('is_published');
        $wanted = filled($data['slug'] ?? null) ? (string) $data['slug'] : $course->slug;
        $data['slug'] = $this->uniqueSlug($wanted, $data['title_en'], $course->id);
        $data['thumbnail_url'] = $this->resolveThumbnail($request, $course->thumbnail_url);

        try {
            $course->update($data);
        } catch (QueryException $e) {
            return back()->withInput()->with('error', $this->dbMessage($e));
        }

        return back()->with('status', 'Course saved.');
    }

    /** Publish or hide a course without opening the full editor. */
    public function visibility(Request $request, Course $course): RedirectResponse
    {
        $publish = $request->boolean('published');
        $course->forceFill(['is_published' => $publish])->save();

        $title = $course->localized('title');

        return back()->with('status', $publish
            ? "{$title} is published. Students can find and join it."
            : "{$title} is now a draft. It is hidden from the catalogue; students who already have it keep their access.");
    }

    public function destroy(Course $course): RedirectResponse
    {
        // Uploaded video files are removed only after the database delete succeeded.
        $files = [];

        if (Schema::hasColumn('lessons', 'video_path')) {
            $files = Lesson::query()
                ->whereIn('module_id', $course->modules()->pluck('id'))
                ->whereNotNull('video_path')
                ->pluck('video_path')
                ->all();
        }

        try {
            DB::transaction(fn () => $course->delete());   // modules, lessons, enrollments cascade
        } catch (QueryException $e) {
            return back()->with('error', $this->dbMessage($e));
        }

        if ($files) {
            Storage::disk('local')->delete($files);
        }

        $this->forgetThumbnail($course->thumbnail_url);

        return redirect()->route('admin.courses')->with('status', 'Course deleted.');
    }

    // ------------------------------------------------------------------------

    private function formData(Course $course): array
    {
        $modules = collect();

        if ($course->exists) {
            $course->load('modules.lessons');
            $modules = $course->modules;
        }

        return [
            'course' => $course,
            'modules' => $modules,
            'categories' => $this->options('category', self::CATEGORIES),
            'levels' => $this->options('level', self::LEVELS),
            'maxMb' => (int) config('admin.video_max_mb', 2048),
            'videoReady' => Schema::hasColumn('lessons', 'video_path'),
            // Uploaded pictures are served through public/storage (php artisan storage:link).
            'thumbsReady' => $this->storageLinked(),
        ];
    }

    /** Suggestions for the category and level boxes: the usual ones plus anything already in use. */
    private function options(string $column, array $defaults): array
    {
        $existing = Course::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return array_values(array_unique(array_merge($defaults, $existing)));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'subtitle_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            // A monogram, not a word: the card sets it at 6xl and a long mark
            // overruns the artwork. Two letters is the house style, three the ceiling.
            'mark' => ['required', 'string', 'max:3'],
            'category' => ['required', 'string', 'max:64'],
            'level' => ['required', 'string', 'max:64'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'thumbnail_url' => ['nullable', 'url:http,https', 'max:2048'],
            'description_en' => ['required', 'string', 'max:10000'],
            'description_km' => ['nullable', 'string', 'max:10000'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'instructor_title' => ['nullable', 'string', 'max:255'],
        ], [
            'price.decimal' => 'The price can have at most 2 decimals, for example 0.01 or 9.99.',
            'thumbnail.max' => 'The picture must be smaller than 2 MB.',
            'thumbnail.image' => 'Please choose a JPG, PNG, WebP or GIF picture.',
            'thumbnail.mimes' => 'Please choose a JPG, PNG, WebP or GIF picture.',
            'thumbnail.dimensions' => 'This picture is too large. Please use one no wider or taller than 4000 pixels.',
            'thumbnail.uploaded' => 'The picture could not be uploaded. It is probably bigger than your server allows (upload_max_filesize in php.ini).',
            'thumbnail_url.url' => 'The picture link must start with http:// or https://',
        ], [
            // Field names as the form labels say them, not as the database does.
            'title_en' => 'English title',
            'title_km' => 'Khmer title',
            'subtitle_en' => 'English subtitle',
            'subtitle_km' => 'Khmer subtitle',
            'slug' => 'web address',
            'description_en' => 'English description',
            'description_km' => 'Khmer description',
            'instructor_name' => 'instructor',
        ]);

        // The Khmer title is required by the database, so fall back to the English one.
        $data['title_km'] = filled($data['title_km'] ?? null) ? $data['title_km'] : $data['title_en'];
        $data['instructor_name'] = filled($data['instructor_name'] ?? null) ? $data['instructor_name'] : 'Piseth Yoeurn';
        $data['price'] = round((float) $data['price'], 2);   // 0 = free, otherwise 0.01 or more
        $data['access_type'] = $data['price'] > 0 ? 'paid' : 'free';

        // The picture is handled by resolveThumbnail(), not saved straight from the form.
        unset($data['thumbnail'], $data['thumbnail_url']);
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    /**
     * What to keep in thumbnail_url: a newly uploaded picture, nothing (picture removed),
     * a pasted link, or the picture the course already has.
     */
    private function resolveThumbnail(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('course-thumbnails', 'public');
            $this->forgetThumbnail($current);

            return '/storage/'.$path;
        }

        if ($request->boolean('remove_thumbnail')) {
            $this->forgetThumbnail($current);

            return null;
        }

        $link = trim((string) $request->input('thumbnail_url'));

        if ($link !== '' && $link !== $current) {
            $this->forgetThumbnail($current);

            return $link;
        }

        return $current;
    }

    /** Deletes an uploaded picture file. Pasted links are left alone. */
    private function forgetThumbnail(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/course-thumbnails/')) {
            Storage::disk('public')->delete('course-thumbnails/'.basename($url));
        }
    }

    private function uniqueSlug(string $wanted, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($wanted) ?: Str::slug($title) ?: 'course';
        $slug = $base;
        $i = 2;

        while (Course::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function dbMessage(QueryException $e): string
    {
        return 'The database rejected this change: '.Str::limit((string) ($e->errorInfo[2] ?? $e->getMessage()), 200);
    }
}
