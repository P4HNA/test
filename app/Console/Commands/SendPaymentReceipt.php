<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PayWayVerifier;
use Illuminate\Console\Command;

/**
 * Send -- or send again -- the receipt for one payment.
 *
 * For the customer who says they never got it, and for the payments that were
 * made before receipts existed at all.
 *
 * Receipts are normally sent once, automatically, the moment a payment is
 * confirmed, and the "once" is enforced with a claim on receipt_sent_at that no
 * retry can win twice. This is the deliberate way past that claim: it releases it
 * and sends again, which is why it asks first and says out loud who is about to
 * be emailed.
 */
class SendPaymentReceipt extends Command
{
    protected $signature = 'payments:receipt
                            {invoice : Invoice number, e.g. PD260924122806BCP1}
                            {--force : Send again even if a receipt has already gone out}';

    protected $description = 'Email the receipt for one paid payment, or send it again';

    public function handle(PayWayVerifier $verifier): int
    {
        $invoice = trim((string) $this->argument('invoice'));
        $payment = Payment::query()->where('invoice_no', $invoice)->first();

        if (! $payment) {
            $this->components->error("No payment found with invoice {$invoice}.");

            return self::FAILURE;
        }

        if (! $payment->isPaid()) {
            $this->components->error("This payment is \"{$payment->status}\", not paid. Only a paid payment has a receipt.");

            return self::FAILURE;
        }

        $email = $payment->payerEmail();

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // An orphaned row whose snapshot kept no usable address.
            $this->components->error('This payment has no usable email address to send to.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Invoice', $payment->invoice_no);
        $this->components->twoColumnDetail('Course', $payment->courseTitle());
        $this->components->twoColumnDetail('Amount', $payment->formattedAmount());
        $this->components->twoColumnDetail('Send to', $email);
        $this->components->twoColumnDetail(
            'Already sent',
            $payment->receipt_sent_at ? $payment->receipt_sent_at->toDayDateTimeString() : 'no'
        );
        $this->newLine();

        if ($payment->receipt_sent_at && ! $this->option('force')) {
            $this->components->warn('A receipt already went out for this payment. Use --force to send it again.');

            return self::FAILURE;
        }

        if (! $this->confirm("Email this receipt to {$email}?", true)) {
            return self::FAILURE;
        }

        // Release the once-only claim so sendReceipt() can take it again. Every
        // other guard inside it still applies.
        if ($payment->receipt_sent_at) {
            $payment->forceFill(['receipt_sent_at' => null])->save();
        }

        if (! $verifier->sendReceipt($payment->fresh())) {
            $this->components->error('The receipt could not be sent. The reason is in storage/logs/laravel.log.');

            return self::FAILURE;
        }

        $this->components->info("Receipt sent to {$email}.");

        return self::SUCCESS;
    }
}
