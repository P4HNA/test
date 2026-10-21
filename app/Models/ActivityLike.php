<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One student's like on one post of the Activity page (see App\Support\ActivityFeed). */
class ActivityLike extends Model
{
    protected $fillable = [
        'user_id',
        'activity_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
