<?php

namespace App\Models;

use App\Support\CoursePicture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A project the teacher sets for a course ("Assignment #03 · Typography
 * Poster"). Students enrolled in the course hand in work for it; each upload
 * is an AssignmentSubmission.
 */
class Assignment extends Model
{
    use Concerns\HasBilingualFields, Concerns\HasUuid, HasFactory;

    protected $fillable = [
        'course_id', 'number', 'title_en', 'title_km', 'brief_en', 'brief_km', 'requirements',
        'format', 'cover_url', 'due_at', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'requirements' => 'array',
            'due_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_published'), true);
    }

    /** "#03" */
    public function code(): string
    {
        return '#'.str_pad((string) $this->number, 2, '0', STR_PAD_LEFT);
    }

    public function pictureSrc(): string
    {
        return filled($this->cover_url) ? $this->cover_url : CoursePicture::for($this->course);
    }

    /** @return array<int, string> */
    public function requirementList(): array
    {
        return array_values(array_filter(array_map('trim', (array) $this->requirements)));
    }

    /** Enrolled students, and the teacher, can open it. */
    public function isOpenTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || ($this->is_published && $user->isEnrolledIn($this->course));
    }

    /** Every version a student has handed in, newest first. */
    public function submissionsBy(User $user)
    {
        return $this->submissions()->where('user_id', $user->getKey())->orderByDesc('version')->get();
    }
}
