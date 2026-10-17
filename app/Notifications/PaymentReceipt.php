<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The customer's receipt, sent once when a course payment is confirmed.
 *
 * Proof of purchase and a way back in. Everything on it comes from the payment
 * row, including the payer and course names, so a receipt still reads correctly
 * if the course is taken down afterwards.
 *
 * Nothing here is allowed to break the unlock: the caller sends it in a
 * try/catch, because a student who paid must get their course whether or not the
 * mail server is answering.
 */
class PaymentReceipt extends Notification
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
        $course = $payment->course;
        $paidAt = $payment->paid_at ?? $payment->created_at;

        $mail = (new MailMessage)
            ->subject(__('Your receipt for :course', ['course' => $payment->courseTitle()]))
            ->greeting(__('Thank you, :name!', ['name' => $payment->payerName()]))
            ->line(__('Your payment is confirmed and your course is unlocked.'));

        // A plain table beats a designed invoice here: it survives every mail
        // client, and it is the part people forward to their accountant.
        $mail->line('')
            ->line('**'.__('Receipt').'**')
            ->line(__('Invoice: :invoice', ['invoice' => $payment->invoice_no]))
            ->line(__('Course: :course', ['course' => $payment->courseTitle()]))
            ->line(__('Amount: :amount', ['amount' => $payment->formattedAmount()]))
            ->line(__('Paid on: :date', ['date' => $paidAt?->format('j F Y, H:i')]))
            ->line(__('Paid with: ABA PayWay (KHQR)'));

        // The course can already be gone by the time this is sent. Then there is
        // nothing to link to, and saying so is better than a dead button.
        if ($course) {
            $mail->action(__('Start learning'), route('courses.show', $course));
        } else {
            $mail->line('')
                ->line(__('That course is no longer available. Please contact us with the invoice number above.'));
        }

        return $mail
            ->line('')
            ->line(__('Keep this email as your proof of purchase.'))
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
        ];
    }
}
