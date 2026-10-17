<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThumbnailController extends Controller
{
    public function index()
    {
        $courses = Course::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.thumbnails.index', [
            'courses' => $courses,
            // Uploaded pictures are served through public/storage (php artisan storage:link).
            'thumbsReady' => $this->storageLinked(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'thumbnail_url' => ['nullable', 'url:http,https', 'max:2048'],
        ], [
            'thumbnail.max' => 'The picture must be smaller than 2 MB.',
            'thumbnail.image' => 'Please choose a JPG, PNG, WebP or GIF picture.',
            'thumbnail.mimes' => 'Please choose a JPG, PNG, WebP or GIF picture.',
            'thumbnail.dimensions' => 'This picture is too large. Please use one no wider or taller than 4000 pixels.',
            'thumbnail.uploaded' => 'The picture could not be uploaded. It is probably bigger than your server allows (upload_max_filesize in php.ini).',
            'thumbnail_url.url' => 'The picture link must start with http:// or https://',
        ]);

        $course->thumbnail_url = $this->resolveThumbnail($request, $course->thumbnail_url);
        $course->save();

        return back()->with('status', 'Thumbnail updated for '.$course->localized('title').'.');
    }

    /**
     * What to keep in thumbnail_url: a newly uploaded picture, nothing (picture
     * removed), a pasted link, or the picture the course already has.
     *
     * Mirrors Admin\CourseController's resolveThumbnail() so a cover swapped
     * here behaves exactly like one swapped from the full course editor.
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
}
