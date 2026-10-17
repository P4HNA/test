<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use Concerns\HasBilingualFields, Concerns\HasUuid, HasFactory;

    protected $fillable = [
        'slug',
        'title_en',
        'title_km',
        'subtitle_en',
        'subtitle_km',
        'mark',
        'category',
        'level',
        'price',
        'access_type',
        'thumbnail_url',
        'description_en',
        'description_km',
        'instructor_name',
        'instructor_title',
        'outcomes',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected $attributes = [
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'outcomes' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    /** Courses the public catalogue may show. Drafts are admin-only. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isPublished(): bool
    {
        return (bool) $this->is_published;
    }

    /**
     * A draft stays reachable for admins (to preview it) and for students who
     * already have it; to everyone else it does not exist.
     */
    public function isVisibleTo(?User $user): bool
    {
        if ($this->isPublished()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $user->isEnrolledIn($this));
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order')->orderBy('id');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Payments taken for this course.
     *
     * Deliberately NOT cascade-deleted: deleting the course nulls the link and
     * leaves the payment behind, so the books still balance. See the
     * 2026_09_26_000001 migration.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')->withTimestamps();
    }

    public function localizedOutcome(array|string $outcome): string
    {
        if (is_string($outcome)) {
            return $outcome;
        }

        if (app()->getLocale() === 'km' && filled($outcome['km'] ?? null)) {
            return (string) $outcome['km'];
        }

        return (string) ($outcome['en'] ?? '');
    }

    public function isFree(): bool
    {
        return $this->access_type === 'free' || (float) $this->price <= 0;
    }

    /**
     * Price without the currency sign, for display: whole amounts stay "35",
     * amounts with cents show two decimals ("5.99", "0.50").
     */
    public function displayPrice(): string
    {
        $price = round((float) $this->price, 2);

        return floor($price) === $price
            ? (string) (int) $price
            : number_format($price, 2, '.', '');
    }

    /** "Free", "$35", "$0.01" or "$12.50" - whole dollars have no cents shown. */
    public function priceLabel(): string
    {
        $price = (float) $this->price;

        if ($price <= 0) {
            return __('Free');
        }

        return static::money($price);
    }

    /** "$35" or "$12.50": whole dollars without cents. */
    public static function money(float $amount): string
    {
        return '$'.number_format($amount, floor($amount) == $amount ? 0 : 2);
    }

    /**
     * The live discount this course gets, if any: the single best one that
     * applies (discounts never stack). Free courses and drafts get none.
     */
    public function activeDiscount(): ?Promotion
    {
        if ($this->isFree() || ! $this->isPublished()) {
            return null;
        }

        return Promotion::liveDiscounts()
            ->filter(fn (Promotion $p) => $p->appliesTo($this))
            ->sortBy(fn (Promotion $p) => [$p->priceFor($this), -$p->priority])
            ->first();
    }

    /**
     * What a student is charged right now. The one number checkout uses, worked
     * out on the server from the database; nothing the browser sends reaches it.
     */
    public function salePrice(): float
    {
        return $this->activeDiscount()?->priceFor($this) ?? round((float) $this->price, 2);
    }

    public function isOnSale(): bool
    {
        return $this->salePrice() < round((float) $this->price, 2);
    }

    /** Like priceLabel(), at the price a student would pay today. */
    public function salePriceLabel(): string
    {
        return $this->isFree() ? __('Free') : static::money($this->salePrice());
    }

    /** Web address of the course picture (an uploaded file or a pasted link), or null. */
    public function thumbnailSrc(): ?string
    {
        $url = trim((string) $this->thumbnail_url);

        if ($url === '') {
            return null;
        }

        return preg_match('#^https?://#i', $url) ? $url : asset(ltrim($url, '/'));
    }

    public function durationMinutes(): int
    {
        if ($this->relationLoaded('lessons')) {
            return (int) $this->lessons->sum('duration_minutes');
        }

        return (int) $this->lessons()->sum('duration_minutes');
    }

    public function formattedDuration(): string
    {
        $minutes = $this->durationMinutes();
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return __(':count min', ['count' => $minutes]);
        }

        if ($rest === 0) {
            return __(':count h', ['count' => $hours]);
        }

        return __(':hours h :minutes m', ['hours' => $hours, 'minutes' => $rest]);
    }

    public function firstLesson(): ?Lesson
    {
        foreach ($this->modules as $module) {
            $lesson = $module->lessons->first();
            if ($lesson) {
                return $lesson;
            }
        }

        return null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array{bg: string, fg: string, rot: int, ty: int}
     */
    public function swatch(): array
    {
        return match ($this->mark) {
            'Ps' => ['bg' => '#1E3A8A', 'fg' => '#EAF1FF', 'rot' => -3, 'ty' => 6],
            'Ai' => ['bg' => '#FF8A00', 'fg' => '#1B0F00', 'rot' => 3, 'ty' => -6],
            'Id' => ['bg' => '#FAF7F0', 'fg' => '#15130F', 'rot' => -5, 'ty' => 4],
            'Gd' => ['bg' => '#15130F', 'fg' => '#FAF7F0', 'rot' => 5, 'ty' => -5],
            'Fg' => ['bg' => '#6B21A8', 'fg' => '#F5E9FF', 'rot' => 4, 'ty' => -4],
            'Ae' => ['bg' => '#E8471E', 'fg' => '#FFF7EF', 'rot' => -4, 'ty' => 5],
            default => ['bg' => '#FAF7F0', 'fg' => '#15130F', 'rot' => 0, 'ty' => 0],
        };
    }

    public function progressPercent(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $this->loadMissing('modules.lessons');
        $lessonIds = $this->modules->flatMap->lessons->pluck('id');
        $total = $lessonIds->count();

        if ($total === 0) {
            return 0;
        }

        $done = LessonCompletion::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        return (int) round(($done / $total) * 100);
    }

    public function completedLessonCount(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $this->loadMissing('modules.lessons');
        $lessonIds = $this->modules->flatMap->lessons->pluck('id');

        return LessonCompletion::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->count();
    }
}
