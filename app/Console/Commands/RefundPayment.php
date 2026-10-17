<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PayWayVerifier;
use Illuminate\Console\Command;
use Throwable;

/**
 * Close a course again after giving the money back.
 *
 * The refund itself happens in ABA's portal -- this does not move money and
 * cannot. What it does is the part the website is responsible for: mark the
 * payment refunded and take the course away again.
 *
 * Without --force it asks PayWay first and refuses unless PayWay agrees the
 * transaction was refunded, so a mistyped invoice cannot quietly remove a paying
 * student's access.
 */
class RefundPayment extends Command
{
    protected $signature = 'payments:refund
                            {invoice : Invoice number, e.g. PD260924122806BCP1}
                            {--force : Record the refund without asking PayWay (you already refunded it in the ABA portal)}';

    protected $description = 'Record a refund and remove the course access it paid for';

    public function handle(PayWayVerifier $verifier): int
    {
        $invoice = trim((string) $this->argument('invoice'));
        $payment = Payment::query()->where('invoice_no', $invoice)->first();

        if (! $payment) {
            $this->components->error("No payment found with invoice {$invoice}.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Invoice', $payment->invoice_no);
        $this->components->twoColumnDetail('Student', $payment->payerName().' <'.$payment->payerEmail().'>');
        $this->components->twoColumnDetail('Course', $payment->courseTitle());
        $this->components->twoColumnDetail('Amount', $payment->formattedAmount());
        $this->components->twoColumnDetail('Status', $payment->status);
        $this->newLine();

        if ($payment->isRefunded()) {
            $this->components->info('Already recorded as refunded on '.$payment->refunded_at?->toDayDateTimeString().'. Nothing to do.');

            return self::SUCCESS;
        }

        if (! $payment->isPaid()) {
            $this->components->error("This payment is \"{$payment->status}\", not paid — there is nothing to refund.");

            return self::FAILURE;
        }

        if (! $verifier->isPayWay($payment)) {
            $this->components->warn('This is a legacy Bakong payment. PayWay cannot be asked about it; use --force if you have refunded it by hand.');

            if (! $this->option('force')) {
                return self::FAILURE;
            }
        } elseif (! $this->option('force')) {
            // Ask PayWay rather than take the operator's word for it.
            $this->line('Asking PayWay whether this transaction was refunded…');

            try {
                $state = $verifier->recheckPaid($payment);
            } catch (Throwable $e) {
                $this->components->error('Could not reach PayWay: '.$e->getMessage());
                $this->line('Use --force if you have already refunded it in the ABA portal and want to close the course now.');

                return self::FAILURE;
            }

            if ($state === 'refunded') {
                $this->components->info('PayWay confirms the refund — course access removed.');

                return self::SUCCESS;
            }

            $this->components->error('PayWay still reports this payment as paid. Refund it in the ABA portal first, then run this again.');
            $this->line('Use --force only if you are certain the money has gone back.');

            return self::FAILURE;
        }

        if (! $this->confirm("Remove {$payment->payerName()}'s access to \"{$payment->courseTitle()}\"?", false)) {
            return self::FAILURE;
        }

        $verifier->markRefunded($payment, 'manual');

        $this->components->info('Recorded as refunded. The course is closed again; their lesson progress is kept in case they buy it back.');

        return self::SUCCESS;
    }
}
