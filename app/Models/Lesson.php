<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use Concerns\HasBilingualFields, Concerns\HasUuid;

    protected $fillable = [
        'module_id',
        'title_en',
        'title_km',
        'video_host',
        'video_url',
        'video_path',
        'video_size',
        'duration_minutes',
        'is_preview',
        'notes_en',
        'notes_km',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class);
    }

    /**
     * A lesson "has a video" when it is an upload with a file, or a YouTube /
     * Drive lesson with a link. One definition, shared by every admin count.
     * $prefix is the table prefix to use inside joined queries ("lessons.").
     */
    public static function constrainToHasVideo($query, string $prefix = '')
    {
        return $query->where(fn ($q) => $q->where($prefix.'video_host', 'upload')->whereNotNull($prefix.'video_path'))
            ->orWhere(fn ($q) => $q->whereIn($prefix.'video_host', ['youtube', 'drive'])
                ->whereNotNull($prefix.'video_url')
                ->where($prefix.'video_url', '!=', ''));
    }

    /** The opposite: lessons a student would open to an empty player. */
    public static function constrainToMissingVideo($query, string $prefix = '')
    {
        return $query->whereNot(fn ($q) => static::constrainToHasVideo($q, $prefix));
    }

    /**
     * Can this person watch this lesson without owning the course?
     *
     * One definition, shared by the lesson page, the video stream and both
     * curriculum lists, so the gate and the padlocks can never disagree.
     *
     * The course must be published: a draft is only meant to be visible to
     * admins and to students who already have it (Course::isVisibleTo), and a
     * preview flag left on inside an unfinished course must not become a hole
     * in that. $course is passed in rather than walked to through the module
     * so the caller's already-loaded course is reused.
     */
    public function isFreePreviewIn(Course $course): bool
    {
        return $this->is_preview
            && $course->isPublished()
            && $this->module_id !== null
            && $course->modules->contains(fn (Module $module) => $module->id === $this->module_id);
    }

    public function isCompletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return LessonCompletion::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->exists();
    }

    public function youtubeId(): ?string
    {
        $url = $this->video_url;
        if (! $url) {
            return null;
        }

        if (preg_match('/(?:youtube\\.com\\/watch\\?v=|youtu\\.be\\/|youtube\\.com\\/embed\\/)([A-Za-z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function embedSrc(): ?string
    {
        if ($this->video_host === 'youtube') {
            $id = $this->youtubeId();

            return $id ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
        }

        if ($this->video_host === 'drive' && $this->video_url) {
            if (preg_match('#/d/([A-Za-z0-9_-]+)#', $this->video_url, $matches)) {
                return 'https://drive.google.com/file/d/'.$matches[1].'/preview';
            }
        }

        return null;
    }

    /** A video file uploaded from the admin panel (private, streamed to enrolled students). */
    public function isUpload(): bool
    {
        return $this->video_host === 'upload' && filled($this->video_path);
    }

    public function hostLabel(): string
    {
        return match ($this->video_host) {
            'drive' => 'Drive',
            'upload' => 'Upload',
            default => 'YouTube',
        };
    }
}
