<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the admins when a payment is held for review.
 *
 * This is the one payment state that cannot resolve itself. PayWay told us an
 * amount that does not match the order, so the course is deliberately NOT
 * unlocked and the customer is told to get in touch -- which means somebody has
 * to be there to be got in touch with. Money has very likely moved, so this is
 * the email that should interrupt someone.
 *
 * Sent once per payment, claimed on review_notified_at. Like the receipt, it is
 * never allowed to break anything upstream: the caller swallows failures,
 * because a held payment is already recorded correctly whether or not the mail
 * server is answering.
 */
class PaymentNeedsReview extends Notification
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;
        $mismatch = $payment->gateway_response['amount_mismatch'] ?? [];

        $mail = (new MailMessage)
            ->error()
            ->subject(__('Payment held for review: :invoice', ['invoice' => $payment->invoice_no]))
            ->greeting(__('A payment needs checking by hand'))
            ->line(__('PayWay reported an amount that does not match the order, so the course was NOT unlocked and the customer has been asked to contact you.'));

        $mail->line('')
            ->line('**'.__('Payment').'**')
            ->line(__('Invoice: :invoice', ['invoice' => $payment->invoice_no]))
            ->line(__('Customer: :name (:email)', [
                'name' => $payment->payerName(),
                'email' => $payment->payerEmail() ?: __('no address on record'),
            ]))
            ->line(__('Course: :course', ['course' => $payment->courseTitle()]));

        // The whole point of the alert: the two figures, side by side, so the
        // person reading it can tell a rounding error from a real problem
        // without opening anything.
        if ($mismatch !== []) {
            $mail->line('')
                ->line('**'.__('What does not match').'**')
                ->line(__('Order: :amount', [
                    'amount' => trim(($mismatch['expected'] ?? '?').' '.($mismatch['expected_currency'] ?? '')),
                ]))
                ->line(__('PayWay reported: :amount', [
                    'amount' => trim((string) ($mismatch['paid'] ?? '?').' '.($mismatch['paid_currency'] ?? '')),
                ]));
        }

        return $mail
            ->action(__('Open the payments book'), route('admin.payments', ['status' => 'review']))
            ->line('')
            ->line(__('If the payment is genuine, confirm it with: php artisan payments:confirm :invoice', [
                'invoice' => $payment->invoice_no,
            ]))
            ->salutation(__('Piseth.Design'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->getKey(),
            'invoice_no' => $this->payment->invoice_no,
            'amount' => (string) $this->payment->amount,
            'currency' => $this->payment->currency,
            'amount_mismatch' => $this->payment->gateway_response['amount_mismatch'] ?? null,
        ];
    }
}
