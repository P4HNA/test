<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A finished run through a quiz, scored on the server. */
class QuizAttempt extends Model
{
    use Concerns\HasUuid;

    protected $fillable = ['quiz_id', 'user_id', 'answers', 'correct', 'total', 'score', 'passed'];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'correct' => 'integer',
            'total' => 'integer',
            'score' => 'integer',
            'passed' => 'boolean',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
