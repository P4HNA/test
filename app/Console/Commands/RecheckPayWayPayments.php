<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PayWayService;
use App\Services\PayWayVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The safety net: finds payments the callback never told us about.
 *
 * Normally ABA PayWay POSTs to /payway/callback the moment a payment lands, and
 * nothing here is needed. But if that callback is lost -- a tunnel down, a deploy
 * mid-request, a dropped retry -- and the customer has closed the page, their
 * money has moved and their course is still locked. Nothing else in the system
 * would ever notice. This does.
 *
 * It also picks up the other loose end a dropped connection leaves behind: a
 * payment that IS settled but whose email never went out -- the customer's
 * receipt, or the admins' warning about a payment held for review. Both are
 * claimed on a timestamp column that is handed back when the mail server
 * refuses, so an unsent one is a real failure and not a row still in flight.
 *
 * Safe to schedule: PayWay's check-transaction has no daily quota, and each run
 * is bounded by --limit anyway.
 */
class RecheckPayWayPayments extends Command
{
    protected $signature = 'payway:recheck
                            {--hours=24 : How far back to look for unpaid QRs}
                            {--limit=60 : Most payments to ask about in one run}
                            {--pretend : List what would be checked and change nothing}';

    protected $description = 'Ask PayWay about pending payments whose callback never arrived, and unlock what was paid';

    public function handle(PayWayVerifier $verifier, PayWayService $payway): int
    {
        if ($problems = $payway->configProblems()) {
            // Not a failure worth alarming a cron about every ten minutes, but it
            // must be visible: with broken settings this net catches nothing.
            $this->components->error('ABA PayWay is not configured:');
            foreach ($problems as $problem) {
                $this->line('  - '.$problem);
            }

            return self::FAILURE;
        }

        $hours = max(1, (int) $this->option('hours'));
        $limit = max(1, (int) $this->option('limit'));

        $payments = Payment::query()
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            // Bakong-era rows have no PayWay transaction to ask about.
            ->filter(fn (Payment $payment) => $verifier->isCheckable($payment));

        if ($payments->isEmpty()) {
            $this->components->info("Nothing pending in the last {$hours}h.");
        } else {
            $this->components->info("Asking PayWay about {$payments->count()} pending ".str('payment')->plural($payments->count()).'…');
        }

        $unlocked = 0;
        $stillWaiting = 0;
        $closed = 0;
        $failed = 0;

        foreach ($payments as $payment) {
            if ($this->option('pretend')) {
                $this->line("  would check {$payment->invoice_no} ({$payment->formattedAmount()}, {$payment->created_at->diffForHumans()})");

                continue;
            }

            try {
                $state = $verifier->settle($payment);
            } catch (Throwable $e) {
                $failed++;
                $this->line("  <fg=yellow>?</> {$payment->invoice_no} — could not reach PayWay: {$e->getMessage()}");

                continue;
            }

            match ($state) {
                'paid' => [$unlocked++, $this->line("  <fg=green>✓</> {$payment->invoice_no} — PAID, course unlocked")],
                'review' => [$closed++, $this->line("  <fg=yellow>!</> {$payment->invoice_no} — held for review (amount mismatch)")],
                'declined', 'expired' => [$closed++, $this->line("  <fg=gray>·</> {$payment->invoice_no} — {$state}")],
                default => [$stillWaiting++, null],
            };
        }

        if ($this->option('pretend')) {
            return self::SUCCESS;
        }

        // Only worth a log line when it actually rescued something -- otherwise a
        // command on a ten-minute timer would fill the log with "nothing found".
        if ($unlocked > 0) {
            Log::warning('The PayWay sweeper unlocked payments the callback never reported', [
                'unlocked' => $unlocked,
                'hours' => $hours,
            ]);
        }

        // Nothing pending means nothing to tally -- a run every ten minutes
        // should not print a table of zeroes.
        if ($payments->isNotEmpty()) {
            $this->newLine();
            $this->components->twoColumnDetail('Unlocked (callback had been missed)', (string) $unlocked);
            $this->components->twoColumnDetail('Still waiting', (string) $stillWaiting);
            $this->components->twoColumnDetail('Closed (declined / expired / review)', (string) $closed);

            if ($failed > 0) {
                $this->components->twoColumnDetail('Could not reach PayWay', (string) $failed);
            }
        }

        $this->resendMissedNotices($verifier, $hours, $limit);

        return self::SUCCESS;
    }

    /**
     * Send the emails that were owed but never went out.
     *
     * sendReceipt() and alertReview() each claim their one-and-only send on a
     * timestamp column, and each hands that claim back if the mail server
     * refuses. So a settled payment still holding a NULL there is one whose
     * email was genuinely lost -- and nothing else in the system would ever
     * retry it.
     *
     * Costs no PayWay calls: this is the database and the mailer only.
     */
    private function resendMissedNotices(PayWayVerifier $verifier, int $hours, int $limit): void
    {
        $receipts = Payment::query()
            ->where('status', 'paid')
            ->whereNull('receipt_sent_at')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        // Deliberately NOT limited by age. A payment held for review is money
        // that probably moved, sitting behind a locked course until a human
        // looks at it; one that slipped through a week ago is the most urgent
        // kind, not the least.
        $held = Payment::query()
            ->where('status', 'review')
            ->whereNull('review_notified_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($receipts->isEmpty() && $held->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->components->info('Sending emails that were owed but never went out…');

        $sent = 0;
        foreach ($receipts as $payment) {
            if ($verifier->sendReceipt($payment)) {
                $sent++;
                $this->line("  <fg=green>✓</> {$payment->invoice_no} — receipt sent");
            }
        }

        $warned = 0;
        foreach ($held as $payment) {
            if ($verifier->alertReview($payment)) {
                $warned++;
                $this->line("  <fg=yellow>!</> {$payment->invoice_no} — admins warned (held for review)");
            }
        }

        if ($warned > 0) {
            Log::warning('Payments are held for review and the admins had not been told', [
                'warned' => $warned,
            ]);
        }

        $this->newLine();
        $this->components->twoColumnDetail('Receipts re-sent', (string) $sent);
        $this->components->twoColumnDetail('Review alerts sent', (string) $warned);
    }
}
