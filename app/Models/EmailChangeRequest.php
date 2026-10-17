<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pending "change my email to X" request, awaiting confirmation of the
 * one-time code sent to the new address. See App\Services\EmailChangeVerifier.
 */
class EmailChangeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'new_email',
        'code_hash',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
