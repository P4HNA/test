<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Concerns\HasUuid;

    protected $fillable = [
        'user_id',
        'course_id',
        'payer_name',
        'payer_email',
        'course_title',
        'invoice_no',
        'amount',
        'list_price',
        'promotion_id',
        'currency',
        'qr',
        'qr_image',
        'deeplink',
        'md5',
        'transaction_hash',
        'from_account_id',
        'to_account_id',
        'status',
        'paid_at',
        'receipt_sent_at',
        'review_notified_at',
        'refunded_at',
        'archived_at',
        'expires_at',
        'description',
        'gateway_response',
    ];

    /**
     * Every status a payment row can hold, in the order a human reads them.
     *
     * Kept here so the admin filters and the customer's list cannot drift apart
     * from what PayWayVerifier actually writes.
     */
    public const STATUSES = ['paid', 'pending', 'review', 'refunded', 'declined', 'expired'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'list_price' => 'decimal:2',
            'paid_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
            'review_notified_at' => 'datetime',
            'refunded_at' => 'datetime',
            'archived_at' => 'datetime',
            'expires_at' => 'datetime',
            'gateway_response' => 'array',
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

    /** The promotion whose discount this payment was charged at, if any. */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** Charged less than the regular price because of a promotion. */
    public function wasDiscounted(): bool
    {
        return $this->list_price !== null && (float) $this->list_price > (float) $this->amount;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at?->isPast();
    }

    /** Money given back. The row stays; the course closed again. */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /** Hidden from the lists by an admin, but still in the books. */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Rows that have not been archived away. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * What the customer should be told this payment is doing.
     *
     * "declined" is shown as "Not paid" rather than the gateway's own word: from
     * the customer's side a declined card and an abandoned QR are the same story,
     * and "declined" reads like an accusation.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'paid' => __('Paid'),
            'pending' => $this->isExpired() ? __('Expired') : __('Waiting for payment'),
            'review' => __('Being checked'),
            'refunded' => __('Refunded'),
            'declined' => __('Not paid'),
            'expired' => __('Expired'),
            default => __('Unknown'),
        };
    }

    /**
     * Which badge colour the status gets. The admin stylesheet has ok/warn/bad
     * variants on .ad-badge, and the classroom side uses the same three words.
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'paid' => 'ok',
            'pending' => $this->isExpired() ? 'bad' : 'warn',
            'review' => 'warn',
            'refunded' => 'warn',
            default => 'bad',
        };
    }

    /** Amount with its currency, the way it should be read back. */
    public function formattedAmount(): string
    {
        $amount = (float) $this->amount;

        return strtoupper((string) $this->currency) === 'KHR'
            ? number_format($amount).' ៛'
            : '$'.number_format($amount, 2);
    }

    /**
     * Snapshot the payer and course names onto the payment itself.
     *
     * The money outlives the records it points at: user_id and course_id null
     * out when a student or course is deleted, so these copies are what keeps
     * the row readable in the books afterwards.
     */
    public function captureParties(?User $user, ?Course $course): void
    {
        $this->payer_name = $user?->name;
        $this->payer_email = $user?->email;
        $this->course_title = $course?->title_en;
    }

    /** True once the student or the course this payment was for is gone. */
    public function isOrphaned(): bool
    {
        return $this->user_id === null || $this->course_id === null;
    }

    /** Payer's name, from the live account if it still exists. */
    public function payerName(): string
    {
        return $this->user?->name ?? $this->payer_name ?? __('Deleted student');
    }

    /** Payer's email, from the live account if it still exists. */
    public function payerEmail(): string
    {
        return $this->user?->email ?? $this->payer_email ?? '';
    }

    /** Course name, from the live course if it still exists. */
    public function courseTitle(): string
    {
        return $this->course?->title_en ?? $this->course_title ?? __('Deleted course');
    }
}
