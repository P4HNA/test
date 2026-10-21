<?php

namespace App\Http\Controllers;

use App\Models\ActivityLike;
use App\Support\ActivityFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The Activity page: what is new in the academy, built from real records (App\Support\ActivityFeed). */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        return view('activity.index', [
            'posts' => (new ActivityFeed($request->user()))->posts(),
        ]);
    }

    /** Like or un-like a post. Answers JSON to the page's script, a redirect otherwise. */
    public function like(Request $request): JsonResponse|RedirectResponse
    {
        $key = (string) $request->input('key');
        abort_unless(ActivityFeed::isValidKey($key), 422);

        $user = $request->user();
        $like = ActivityLike::query()->where('user_id', $user->getKey())->where('activity_key', $key)->first();

        if ($like) {
            $like->delete();
        } else {
            ActivityLike::query()->create(['user_id' => $user->getKey(), 'activity_key' => $key]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => ! $like,
                'count' => ActivityLike::query()->where('activity_key', $key)->count(),
            ]);
        }

        return back();
    }
}
