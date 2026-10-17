<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'last_lesson_id',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lastLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }

    public function markLessonComplete(Lesson $lesson): void
    {
        LessonCompletion::query()->firstOrCreate(
            [
                'user_id' => $this->user_id,
                'lesson_id' => $lesson->id,
            ],
            [
                'completed_at' => now(),
            ]
        );

        $this->forceFill(['last_lesson_id' => $lesson->id])->save();
    }

    public function continueLesson(): ?Lesson
    {
        $this->loadMissing('course.modules.lessons', 'lastLesson');

        $ordered = $this->course->modules->flatMap->lessons->values();
        if ($ordered->isEmpty()) {
            return null;
        }

        $completedIds = LessonCompletion::query()
            ->where('user_id', $this->user_id)
            ->whereIn('lesson_id', $ordered->pluck('id'))
            ->pluck('lesson_id');

        $nextIncomplete = $ordered->first(
            fn (Lesson $lesson) => ! $completedIds->contains($lesson->id)
        );

        return $nextIncomplete ?? $this->lastLesson ?? $ordered->first();
    }
}
