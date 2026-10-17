<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PayWayVerifier;
use Illuminate\Console\Command;
use Throwable;

/**
 * php artisan payments:confirm PD260924122806BCP1
 *
 * Asks ABA PayWay about one invoice. With --force, records it manually (only
 * after you have actually seen the money arrive).
 *
 * The payments table still holds rows from the Bakong era, before the PayWay
 * switch. Those cannot be asked about any more -- the integration that knew how
 * to read them is gone -- so this refuses them rather than guessing, and says
 * where to go instead.
 */
class ConfirmPayment extends Command
{
    protected $signature = 'payments:confirm {invoice : Invoice number, e.g. PD260924122806BCP1} {--force : Confirm without asking the gateway (you already saw the money arrive)}';

    protected $description = 'Confirm one payment by invoice number and unlock the course';

    public function handle(PayWayVerifier $payway): int
    {
        $payment = Payment::query()->where('invoice_no', trim((string) $this->argument('invoice')))->first();

        if (! $payment) {
            $this->error('No payment found with that invoice number.');

            return self::FAILURE;
        }

        $this->line("Invoice {$payment->invoice_no}: {$payment->currency} {$payment->amount}, status {$payment->status}, payer {$payment->payerName()}, course {$payment->courseTitle()}");

        if ($payment->isOrphaned()) {
            $this->warn('The student or course this payment was for has been deleted. The payment record is kept, but there is nothing left to unlock.');
        }

        if ($payment->isPaid()) {
            $payway->ensureEnrollment($payment);
            $this->info('Already paid — course is unlocked.');

            return self::SUCCESS;
        }

        // A pre-switch row. Marking it paid here would stamp it as a PayWay
        // payment, which it never was, so the books would start lying about
        // where the money came from.
        if (! $payway->isPayWay($payment)) {
            $this->warn('This is a pre-PayWay (Bakong) payment. The integration that could check it has been removed, so it cannot be confirmed here.');
            $this->line('To give the student their course, use Admin → Students → the student → Give course access.');
            $this->line('That grants access without pretending this row was a PayWay payment.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            return $this->askPayWay($payway, $payment);
        }

        if (! $this->confirm('Did you see this exact amount arrive in your ABA/bank account?')) {
            return self::FAILURE;
        }

        // No amount is passed, so the mismatch check has nothing to compare and
        // the payment is taken at face value -- which is the point of --force:
        // you are the one vouching for it.
        $payway->markPaid($payment, [
            'status' => 'MANUAL',
            'amount' => null,
            'currency' => null,
            'data' => [],
            'raw' => [],
        ], 'manual');

        $this->info('Marked as paid manually — course unlocked.');

        return self::SUCCESS;
    }

    private function askPayWay(PayWayVerifier $payway, Payment $payment): int
    {
        try {
            $state = $payway->settle($payment);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->line('If you are sure the money arrived, run again with --force');

            return self::FAILURE;
        }

        if ($state === 'paid') {
            $this->info('PayWay confirmed the payment — course unlocked.');

            return self::SUCCESS;
        }

        $this->warn(match ($state) {
            'review' => 'Held for review: PayWay reported an amount that does not match the order. Check it by hand.',
            'declined' => 'PayWay says this payment did not go through.',
            'expired' => 'This QR expired without being paid.',
            default => "PayWay has no completed payment for this transaction yet (state: {$state}).",
        });
        $this->line('If you are sure the money arrived, run again with --force');

        return self::FAILURE;
    }
}
