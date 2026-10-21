<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * MustVerifyEmail: new sign-ups get Laravel's signed verification link and
 * can't enrol, pay or open lessons (the "verified" route middleware) until
 * they click it. Google sign-ins arrive verified -- see SocialAuthController.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Concerns\HasUuid, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'banned_at',
        'ban_reason',
        'avatar_path',
        'headline',
        'bio',
        'location',
        'website',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Blocked from signing in (by password or Google), but the account and its history stay. */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * Web address of the uploaded profile picture, or null to fall back to
     * initials.
     *
     * Storage::url() builds the address out of APP_URL, which pins the picture
     * to one host and port. Open the same site any other way -- "localhost"
     * instead of 127.0.0.1, a phone on the LAN, a second `artisan serve` on a
     * free port -- and the browser asks the wrong origin for the file and the
     * avatar never loads. For a local disk the origin is always our own, so
     * hand back a root-relative address and let the page's own host answer.
     * A remote disk (S3 and friends) is left exactly as configured.
     */
    public function avatarUrl(): ?string
    {
        $path = trim((string) $this->avatar_path);

        if ($path === '') {
            return null;
        }

        $url = Storage::disk('public')->url($path);
        $origin = rtrim((string) config('app.url'), '/');

        return $origin !== '' && str_starts_with($url, $origin.'/')
            ? substr($url, strlen($origin))
            : $url;
    }

    /**
     * One or two letters for the fallback avatar. Takes the first letter of the
     * first and last word, so "Mao Panha" reads MP. mb_* throughout: Khmer names
     * are multi-byte and substr() would cut a character in half.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '?';
        }

        $first = mb_substr($words[0], 0, 1);

        return count($words) === 1
            ? mb_strtoupper($first)
            : mb_strtoupper($first.mb_substr(end($words), 0, 1));
    }

    /**
     * A stable hue for the fallback avatar, derived from the account id so the
     * same person keeps the same colour on every page and across devices.
     */
    public function avatarHue(): int
    {
        return (int) (crc32((string) $this->getKey()) % 360);
    }

    /**
     * What is still missing from the profile, as [label key => filled?].
     * Drives the completeness meter on /my-profile; the order is the order the
     * meter suggests them in.
     *
     * @return array<string, bool>
     */
    public function profileChecklist(): array
    {
        return [
            'Add a profile photo' => $this->avatarUrl() !== null,
            'Add a headline' => filled($this->headline),
            'Write a short bio' => filled($this->bio),
            'Add your location' => filled($this->location),
            'Add a phone number' => filled($this->phone),
            'Verify your email' => $this->hasVerifiedEmail(),
        ];
    }

    /** Percentage of profileChecklist() that is filled in, 0-100. */
    public function profileCompleteness(): int
    {
        $checks = $this->profileChecklist();

        return (int) round(count(array_filter($checks)) / count($checks) * 100);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->latest('enrolled_at');
    }

    public function lessonCompletions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->latest('issued_at');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Payments this student made.
     *
     * Deliberately NOT cascade-deleted: deleting the account nulls the link and
     * leaves the payment behind, so the books still balance. See the
     * 2026_09_26_000001 migration.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /** Resources this student bookmarked in the Resources library. */
    public function savedResources(): BelongsToMany
    {
        return $this->belongsToMany(DesignResource::class, 'design_resource_bookmarks')->withTimestamps();
    }

    public function enrollmentFor(Course $course): ?Enrollment
    {
        if ($this->relationLoaded('enrollments')) {
            return $this->enrollments->firstWhere('course_id', $course->id);
        }

        return $this->enrollments()->where('course_id', $course->id)->first();
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrollmentFor($course) !== null;
    }
}
