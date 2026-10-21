<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The teacher's side of the Resources library: upload a design file (or link
 * to where it lives), describe it, and choose whether students see it.
 *
 * Files go to the private disk under design-resources/ and are only handed
 * out by the public download route. Pictures go to the public disk, like
 * course covers.
 */
class ResourceController extends Controller
{
    /** Largest file a resource can be, in kilobytes (200 MB). PHP's own upload limit may be lower. */
    public const MAX_FILE_KB = 204800;

    public function index(): View
    {
        $resources = DesignResource::query()->withCount('bookmarkedBy')->libraryOrder()->get();

        return view('admin.resources.index', [
            'resources' => $resources,
            'totalDownloads' => (int) $resources->sum('downloads_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.resources.form', [
            'resource' => new DesignResource(['is_published' => true, 'category' => 'Photoshop', 'software' => 'Photoshop']),
            'categories' => DesignResource::CATEGORIES,
        ]);
    }

    public function edit(DesignResource $resource): View
    {
        return view('admin.resources.form', [
            'resource' => $resource,
            'categories' => DesignResource::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resource = new DesignResource;
        $this->fill($request, $resource);
        $resource->save();

        Log::info('Admin added a resource', ['admin_id' => $request->user()->getKey(), 'resource_id' => $resource->id]);

        return redirect()->route('admin.resources')->with('status', "\"{$resource->title_en}\" is added".($resource->is_published ? ' and students can download it.' : ' as hidden.'));
    }

    public function update(Request $request, DesignResource $resource): RedirectResponse
    {
        $this->fill($request, $resource);
        $resource->save();

        Log::info('Admin updated a resource', ['admin_id' => $request->user()->getKey(), 'resource_id' => $resource->id]);

        return redirect()->route('admin.resources')->with('status', "\"{$resource->title_en}\" is saved.");
    }

    /** Show or hide it for students without opening it. */
    public function toggle(DesignResource $resource): RedirectResponse
    {
        $resource->forceFill(['is_published' => ! $resource->is_published])->save();

        return back()->with('status', $resource->is_published
            ? "\"{$resource->title_en}\" is visible to students."
            : "\"{$resource->title_en}\" is hidden from students.");
    }

    public function destroy(Request $request, DesignResource $resource): RedirectResponse
    {
        $title = $resource->title_en;

        $this->forgetFile($resource->file_path);
        $this->forgetPicture($resource->thumbnail_url);
        $resource->delete();   // bookmarks go with it (cascade)

        Log::info('Admin deleted a resource', ['admin_id' => $request->user()->getKey(), 'title' => $title]);

        return redirect()->route('admin.resources')->with('status', "\"{$title}\" is deleted.");
    }

    // ------------------------------------------------------------------------

    private function fill(Request $request, DesignResource $resource): void
    {
        $isNew = ! $resource->exists;

        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:160'],
            'title_km' => ['nullable', 'string', 'max:160'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_km' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'string', 'max:80'],
            'software' => ['required', 'string', 'max:40'],
            'category' => ['required', Rule::in(DesignResource::CATEGORIES)],
            'tags' => ['nullable', 'string', 'max:200'],
            'file' => ['nullable', 'file', 'max:'.self::MAX_FILE_KB],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'thumbnail_url' => ['nullable', 'url:http,https', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ], [
            'file.max' => 'The file must be smaller than 200 MB.',
            'file.uploaded' => 'The file could not be uploaded. It is probably bigger than your server allows (upload_max_filesize in php.ini). Share a link to it instead.',
            'external_url.url' => 'The link must start with http:// or https://',
            'thumbnail.max' => 'The picture must be smaller than 2 MB.',
            'thumbnail.image' => 'Please choose a JPG, PNG, WebP or GIF picture.',
            'thumbnail.mimes' => 'Please choose a JPG, PNG, WebP or GIF picture.',
            'thumbnail.dimensions' => 'This picture is too large. Please use one no wider or taller than 4000 pixels.',
            'thumbnail_url.url' => 'The picture link must start with http:// or https://',
        ], [
            'title_en' => 'English title',
            'title_km' => 'Khmer title',
            'description_en' => 'English description',
            'description_km' => 'Khmer description',
            'external_url' => 'link',
        ]);

        // Something to download: a new file, a link, or (when editing) the file it already has.
        $removeFile = $request->boolean('remove_file');
        $keepsFile = ! $isNew && filled($resource->file_path) && ! $removeFile;
        if (! $request->hasFile('file') && blank($data['external_url'] ?? null) && ! $keepsFile) {
            throw ValidationException::withMessages([
                'file' => 'Upload a file or paste a link to it.',
            ]);
        }

        $resource->fill([
            'title_en' => $data['title_en'],
            'title_km' => $data['title_km'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_km' => $data['description_km'] ?? null,
            'type' => $data['type'],
            'software' => $data['software'],
            'category' => $data['category'],
            'tags' => $this->tags($data['tags'] ?? ''),
            'external_url' => $data['external_url'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        if ($request->hasFile('file')) {
            $upload = $request->file('file');
            $this->forgetFile($resource->file_path);
            $resource->file_path = $upload->store('design-resources', 'local');
            $resource->file_name = $this->safeName($upload->getClientOriginalName());
            $resource->file_size = $upload->getSize();
        } elseif ($removeFile) {
            $this->forgetFile($resource->file_path);
            $resource->file_path = $resource->file_name = $resource->file_size = null;
        }

        $resource->thumbnail_url = $this->resolvePicture($request, $resource->thumbnail_url);
    }

    /** "mockup, poster ,  grain" -> ['mockup', 'poster', 'grain'] (at most 8, no repeats). */
    private function tags(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn ($t) => Str::limit(trim(ltrim(trim($t), '#')), 30, ''))
            ->filter()
            ->unique(fn ($t) => mb_strtolower($t))
            ->take(8)
            ->values()
            ->all();
    }

    /** Keeps the downloaded file's name readable but free of path tricks. */
    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN._ -]+/u', '-', $name) ?: 'resource';

        return Str::limit($name, 150, '');
    }

    /** Same rules as course covers: a new upload, removed, a pasted link, or unchanged. */
    private function resolvePicture(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('resource-pictures', 'public');
            $this->forgetPicture($current);

            return '/storage/'.$path;
        }

        if ($request->boolean('remove_thumbnail')) {
            $this->forgetPicture($current);

            return null;
        }

        $link = trim((string) $request->input('thumbnail_url'));

        if ($link !== '' && $link !== $current) {
            $this->forgetPicture($current);

            return $link;
        }

        return $current;
    }

    private function forgetPicture(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/resource-pictures/')) {
            Storage::disk('public')->delete('resource-pictures/'.basename($url));
        }
    }

    private function forgetFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'design-resources/')) {
            Storage::disk('local')->delete($path);
        }
    }
}
