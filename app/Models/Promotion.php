<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A discount or an announcement that runs for a while.
 *
 * - A discount lowers the price a student is charged, for one course, one
 *   category or every course. Discounts never stack: a course takes the single
 *   best one that applies (see Course::activeDiscount()).
 * - An announcement is a message only; prices stay the same.
 *
 * "Live" means turned on and inside its dates. Nothing needs to run when a
 * promotion starts or ends: every read checks the clock.
 */
class Promotion extends Model
{
    use Concerns\HasBilingualFields, Concerns\HasUuid;

    public const KINDS = ['discount', 'announcement'];

    public const TARGETS = ['all', 'course', 'category'];

    /** A discount can take a course down to this, never to nothing. */
    public const MIN_PRICE = 0.01;

    public const MAX_PERCENT = 90;

    protected $fillable = [
        'kind',
        'title_en',
        'title_km',
        'message_en',
        'message_km',
        'discount_percent',
        'applies_to',
        'course_id',
        'category',
        'starts_at',
        'ends_at',
        'is_active',
        'show_on_home',
        'cta_label',
        'cta_url',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'discount_percent' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Turned on, and inside its dates right now. */
    public function scopeLive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    public function isDiscount(): bool
    {
        return $this->kind === 'discount' && (int) $this->discount_percent > 0;
    }

    public function isLive(): bool
    {
        return $this->status() === 'live';
    }

    /** live | scheduled | ended | off, for the admin list. */
    public function status(): string
    {
        if (! $this->is_active) {
            return 'off';
        }

        if ($this->ends_at && $this->ends_at->lte(now())) {
            return 'ended';
        }

        if ($this->starts_at && $this->starts_at->gt(now())) {
            return 'scheduled';
        }

        return 'live';
    }

    public function appliesTo(Course $course): bool
    {
        return match ($this->applies_to) {
            'course' => (int) $this->course_id === (int) $course->id,
            'category' => filled($this->category) && strcasecmp((string) $this->category, (string) $course->category) === 0,
            default => true,
        };
    }

    /** What a course costs with this discount: never below MIN_PRICE, never above the price. */
    public function priceFor(Course $course): float
    {
        $price = round((float) $course->price, 2);

        if (! $this->isDiscount() || $price <= 0) {
            return $price;
        }

        $sale = round($price * (100 - (int) $this->discount_percent) / 100, 2);

        return min($price, max(self::MIN_PRICE, $sale));
    }

    /** "All courses", a course title or a category, for people. */
    public function targetLabel(): string
    {
        return match ($this->applies_to) {
            'course' => $this->course?->localized('title') ?? __('A deleted course'),
            'category' => __((string) $this->category),
            default => __('All courses'),
        };
    }

    /** Where the button goes: the admin's own link, or the thing on offer. */
    public function ctaUrl(): string
    {
        if (filled($this->cta_url)) {
            return (string) $this->cta_url;
        }

        return match ($this->applies_to) {
            'course' => $this->course ? route('courses.show', $this->course) : route('courses'),
            'category' => route('courses', ['category' => $this->category]),
            default => route('courses'),
        };
    }

    public function ctaLabel(): string
    {
        if (filled($this->cta_label)) {
            return (string) $this->cta_label;
        }

        return match ($this->applies_to) {
            'course' => __('See the course'),
            default => __('See the courses'),
        };
    }

    /**
     * Live discounts, loaded once per request: every price on a page is worked
     * out from the same list, and a catalogue of courses costs one query.
     *
     * @return Collection<int, Promotion>
     */
    public static function liveDiscounts(): Collection
    {
        // Kept on the request itself, so it can never outlive it.
        $bag = request()->attributes;

        if (! $bag->has('pd.live_discounts')) {
            $bag->set('pd.live_discounts', static::query()
                ->live()
                ->where('kind', 'discount')
                ->where('discount_percent', '>', 0)
                ->orderByDesc('discount_percent')
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->get());
        }

        return $bag->get('pd.live_discounts');
    }

    /**
     * The one promotion the homepage shows, or null. Highest priority first,
     * then the one ending soonest. A promotion for a course students cannot see
     * (a draft) is skipped: its button would lead nowhere.
     */
    public static function forHomepage(): ?self
    {
        return static::query()
            ->live()
            ->where('show_on_home', true)
            ->with('course')
            ->orderByDesc('priority')
            ->orderByRaw('ends_at is null')
            ->orderBy('ends_at')
            ->orderByDesc('id')
            ->get()
            ->first(fn (self $p) => $p->applies_to !== 'course' || ($p->course && $p->course->isPublished()));
    }
}
