<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the NEW email address (never the current one) when a profile
 * email change is requested. Entering this code back on the profile page
 * is what proves the user actually controls that inbox.
 *
 * Deliberately NOT queued (no ShouldQueue): the user is waiting on the
 * page for this code, a queued copy would sit unsent whenever no queue
 * worker is running, and the serialized job would store the plaintext
 * code in the jobs table.
 */
class EmailChangeVerificationCode extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly string $newEmail,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new email address')
            ->greeting('Confirm your new email address')
            ->line("Use this code to confirm changing your account email to {$this->newEmail}:")
            ->line("## {$this->code}")
            ->line('This code expires in 10 minutes and can only be used once.')
            ->line("If you didn't request this change, you can safely ignore this email — your account email will stay the same.");
    }
}
