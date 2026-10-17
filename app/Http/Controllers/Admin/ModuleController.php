<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ModuleController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $titles = $this->titles($request);

        if ($titles instanceof RedirectResponse) {
            return $titles;
        }

        $last = (int) Module::query()->where('course_id', $course->id)->max('sort_order');

        Module::query()->create([
            'course_id' => $course->id,
            'title_en' => $titles['title_en'],
            'title_km' => $titles['title_km'],
            'sort_order' => $last + 1,
        ]);

        return back()->with('status', 'Module added. Now add lessons to it.');
    }

    public function update(Request $request, Course $course, $moduleId): RedirectResponse
    {
        $module = $this->find($course, $moduleId);
        $titles = $this->titles($request);

        if ($titles instanceof RedirectResponse) {
            return $titles;
        }

        $module->update($titles);

        return back()->with('status', 'Module renamed.');
    }

    public function destroy(Course $course, $moduleId): RedirectResponse
    {
        $module = $this->find($course, $moduleId);

        // Video files of the lessons inside are removed only after the database delete worked.
        $files = Schema::hasColumn('lessons', 'video_path')
            ? $module->lessons()->whereNotNull('video_path')->pluck('video_path')->all()
            : [];

        $module->delete();   // its lessons go with it (cascade)

        if ($files) {
            Storage::disk('local')->delete($files);
        }

        return back()->with('status', 'Module deleted.');
    }

    /** Move a module one step up or down, then renumber the whole course 1..n. */
    public function move(Request $request, Course $course, $moduleId): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $ids = Module::query()
            ->where('course_id', $course->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $from = array_search((int) $moduleId, $ids, true);
        abort_if($from === false, 404);

        $to = $direction === 'up' ? $from - 1 : $from + 1;

        if (isset($ids[$to])) {
            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $index => $id) {
                Module::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        }

        return back();
    }

    // ------------------------------------------------------------------------

    private function find(Course $course, $moduleId): Module
    {
        return Module::query()->where('course_id', $course->id)->whereKey($moduleId)->firstOrFail();
    }

    /**
     * These forms are tiny, so a mistake is shown as a message at the top of the page.
     *
     * @return array{title_en: string, title_km: string}|RedirectResponse
     */
    private function titles(Request $request): array|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $data = $validator->validated();

        return [
            'title_en' => $data['title_en'],
            'title_km' => filled($data['title_km'] ?? null) ? $data['title_km'] : $data['title_en'],
        ];
    }
}
