<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use Concerns\HasUuid;

    protected $fillable = [
        'user_id',
        'course_id',
        'code',
        'holder_name',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
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

    /**
     * The name printed on the certificate: the real name the admin approved.
     * Certificates issued before the request flow existed have none saved,
     * so they fall back to the account name.
     */
    public function holderName(): string
    {
        return filled($this->holder_name) ? $this->holder_name : (string) ($this->user->name ?? '');
    }
}
