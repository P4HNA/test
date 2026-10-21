<?php

namespace App\Http\Controllers;

use App\Models\DesignResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Resources library students see: browse, bookmark and download the
 * design files the teacher shares. Uploading and editing them is the admin's
 * job (Admin\ResourceController).
 */
class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $resources = DesignResource::query()->published()->libraryOrder()->get();

        $saved = $request->user()
            ? $request->user()->savedResources()->pluck('design_resources.id')->all()
            : [];

        return view('resources.index', [
            'resources' => $resources,
            'saved' => $saved,
            'categories' => DesignResource::CATEGORIES,
        ]);
    }

    /**
     * Hands out the file (or sends the student to where it lives) and counts
     * the download. Uploaded files stay on the private disk, so this is the
     * only way to them.
     */
    public function download(DesignResource $resource): Response
    {
        abort_unless($resource->is_published && $resource->isDownloadable(), 404);

        $disk = Storage::disk('local');
        $hasFile = filled($resource->file_path) && $disk->exists($resource->file_path);

        abort_unless($hasFile || filled($resource->external_url), 404);

        // Counted without touching updated_at: a download is not an edit.
        DesignResource::query()->whereKey($resource->getKey())->toBase()->increment('downloads_count');

        if ($hasFile) {
            return $disk->download($resource->file_path, $resource->file_name ?: basename($resource->file_path));
        }

        return redirect()->away($resource->external_url);
    }

    /** Bookmark or un-bookmark a resource. Answers JSON to the page's script, a redirect otherwise. */
    public function bookmark(Request $request, DesignResource $resource): JsonResponse|RedirectResponse
    {
        abort_unless($resource->is_published, 404);

        $result = $request->user()->savedResources()->toggle($resource->getKey());
        $saved = $result['attached'] !== [];

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => $saved,
                'count' => $request->user()->savedResources()->count(),
            ]);
        }

        return back();
    }
}
