<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One version of a student's work for an assignment, with the teacher's
 * review of it. Files live on the private disk under assignment-work/.
 */
class AssignmentSubmission extends Model
{
    use Concerns\HasUuid;

    /** status => [English, Khmer, tone] */
    public const STATUSES = [
        'submitted' => ['Submitted', 'បានបញ្ជូន', 'blue'],
        'review' => ['Under Review', 'កំពុងពិនិត្យ', 'blue'],
        'revision' => ['Revision Required', 'ត្រូវកែសម្រួលឡើងវិញ', 'amber'],
        'approved' => ['Approved', 'បានអនុម័ត', 'green'],
    ];

    protected $fillable = [
        'assignment_id', 'user_id', 'version', 'file_path', 'file_name', 'file_size', 'file_mime',
        'link_url', 'note', 'status', 'grade', 'feedback_en', 'feedback_km', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'file_size' => 'integer',
            'grade' => 'decimal:1',
            'reviewed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(?string $locale = null): string
    {
        [$en, $km] = self::STATUSES[$this->status] ?? self::STATUSES['submitted'];

        return ($locale ?? app()->getLocale()) === 'km' ? $km : $en;
    }

    public function statusTone(): string
    {
        return (self::STATUSES[$this->status] ?? self::STATUSES['submitted'])[2];
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->file_mime, 'image/');
    }

    public function sizeLabel(): string
    {
        $bytes = (int) $this->file_size;

        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => number_format($bytes / 1024).' KB',
            default => $bytes.' B',
        };
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null && in_array($this->status, ['revision', 'approved'], true);
    }

    /** The teacher's note in the reader's language, falling back to the other one. */
    public function feedback(): string
    {
        $km = trim((string) $this->feedback_km);
        $en = trim((string) $this->feedback_en);

        return app()->getLocale() === 'km' && $km !== '' ? $km : ($en !== '' ? $en : $km);
    }
}
