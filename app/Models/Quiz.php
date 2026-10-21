<?php

namespace App\Models;

use App\Support\CoursePicture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A multiple-choice quiz for a course ("Typography Knowledge Quiz"). */
class Quiz extends Model
{
    use Concerns\HasBilingualFields, Concerns\HasUuid, HasFactory;

    protected $fillable = ['course_id', 'title_en', 'title_km', 'topic', 'cover_url', 'pass_percent', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'pass_percent' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_published'), true);
    }

    public function pictureSrc(): string
    {
        return filled($this->cover_url) ? $this->cover_url : CoursePicture::for($this->course);
    }

    public function isOpenTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || ($this->is_published && $user->isEnrolledIn($this->course));
    }
}
