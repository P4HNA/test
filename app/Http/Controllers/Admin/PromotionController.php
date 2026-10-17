<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Discounts and announcements.
 *
 * A discount lowers the price charged at checkout, from its start to its end,
 * for what it applies to. An announcement only shows a message. The homepage
 * bar shows one promotion at a time: the live one with the highest priority.
 */
class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::query()->with('course')->latest('id')->get();

        // Live first, then scheduled, then ended / turned off.
        $order = ['live' => 0, 'scheduled' => 1, 'off' => 2, 'ended' => 3];
        $promotions = $promotions->sortBy(fn (Promotion $p) => [$order[$p->status()], -$p->priority])->values();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'onHomepage' => Promotion::forHomepage(),
        ]);
    }

    public function create()
    {
        return view('admin.promotions.form', $this->formData(new Promotion([
            'kind' => 'discount',
            'discount_percent' => 20,
            'applies_to' => 'course',
            'is_active' => true,
            'show_on_home' => true,
            'priority' => 0,
        ])));
    }

    public function edit(Promotion $promotion)
    {
        return view('admin.promotions.form', $this->formData($promotion));
    }

    public function store(Request $request): RedirectResponse
    {
        $promotion = Promotion::query()->create($this->validated($request));

        Log::info('Admin created a promotion', ['admin_id' => $request->user()->getKey(), 'promotion_id' => $promotion->id]);

        return redirect()->route('admin.promotions')->with('status', $this->savedMessage($promotion, 'created'));
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validated($request));

        Log::info('Admin updated a promotion', ['admin_id' => $request->user()->getKey(), 'promotion_id' => $promotion->id]);

        return redirect()->route('admin.promotions')->with('status', $this->savedMessage($promotion->fresh(), 'saved'));
    }

    /** Turn a promotion on or off without opening it. */
    public function toggle(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->forceFill(['is_active' => ! $promotion->is_active])->save();

        return back()->with('status', $promotion->is_active
            ? "\"{$promotion->title_en}\" is turned on."
            : "\"{$promotion->title_en}\" is turned off. Prices are back to normal and it has left the homepage.");
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        $title = $promotion->title_en;
        $promotion->delete();   // payments made at its price keep their amount; their promotion_id just clears

        Log::info('Admin deleted a promotion', ['admin_id' => $request->user()->getKey(), 'title' => $title]);

        return redirect()->route('admin.promotions')->with('status', "\"{$title}\" is deleted.");
    }

    // ------------------------------------------------------------------------

    private function formData(Promotion $promotion): array
    {
        $courses = Course::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'title_en', 'title_km', 'price', 'access_type', 'category', 'is_published']);

        return [
            'promotion' => $promotion,
            'courses' => $courses,
            'categories' => $courses->pluck('category')->filter()->unique()->sort()->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(Promotion::KINDS)],
            'title_en' => ['required', 'string', 'max:80'],
            'title_km' => ['nullable', 'string', 'max:80'],
            'message_en' => ['nullable', 'string', 'max:160'],
            'message_km' => ['nullable', 'string', 'max:160'],
            'discount_percent' => ['exclude_unless:kind,discount', 'required', 'integer', 'min:1', 'max:'.Promotion::MAX_PERCENT],
            'applies_to' => ['required', Rule::in(Promotion::TARGETS)],
            'course_id' => ['exclude_unless:applies_to,course', 'required', 'integer', 'exists:courses,id'],
            'category' => ['exclude_unless:applies_to,category', 'required', 'string', 'max:64', Rule::exists('courses', 'category')],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), 'after:starts_at')],
            'cta_label' => ['nullable', 'string', 'max:40'],
            // A page on this site ("/courses") or a full web address. Nothing else,
            // so a link can never run script.
            'cta_url' => ['nullable', 'string', 'max:2048', 'regex:#^(/(?!/)|https?://)#i'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'discount_percent.required' => 'Enter how much to take off, for example 20.',
            'discount_percent.max' => 'A discount can be at most '.Promotion::MAX_PERCENT.'%.',
            'course_id.required' => 'Choose the course this is for.',
            'category.required' => 'Choose the category this is for.',
            'category.exists' => 'No course is in that category.',
            'ends_at.after' => 'The end has to be after the start.',
            'cta_url.regex' => 'The link must be a page on this site, like /courses, or start with https://',
        ], [
            'title_en' => 'English title',
            'title_km' => 'Khmer title',
            'message_en' => 'English message',
            'message_km' => 'Khmer message',
            'discount_percent' => 'discount',
            'starts_at' => 'start',
            'ends_at' => 'end',
            'cta_label' => 'button label',
            'cta_url' => 'button link',
        ]);

        return [
            'kind' => $data['kind'],
            'title_en' => $data['title_en'],
            'title_km' => $data['title_km'] ?? null,
            'message_en' => $data['message_en'] ?? null,
            'message_km' => $data['message_km'] ?? null,
            'discount_percent' => $data['kind'] === 'discount' ? (int) $data['discount_percent'] : null,
            'applies_to' => $data['applies_to'],
            'course_id' => $data['applies_to'] === 'course' ? (int) $data['course_id'] : null,
            'category' => $data['applies_to'] === 'category' ? $data['category'] : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'show_on_home' => $request->boolean('show_on_home'),
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'priority' => (int) ($data['priority'] ?? 0),
        ];
    }

    private function savedMessage(Promotion $promotion, string $verb): string
    {
        $state = match ($promotion->status()) {
            'live' => $promotion->show_on_home && Promotion::forHomepage()?->is($promotion)
                ? 'It is live and showing on the homepage.'
                : 'It is live.',
            'scheduled' => 'It starts '.$promotion->starts_at->format('j M, H:i').'.',
            'ended' => 'Its end date has passed, so it is not showing.',
            default => 'It is turned off.',
        };

        return "\"{$promotion->title_en}\" is {$verb}. {$state}";
    }
}
