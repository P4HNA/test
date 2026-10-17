<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PayWayVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * The payment book: every payment, what happened to it, and what it earned.
 *
 * Nothing here can mark a payment paid or unlock a course. That direction is
 * PayWayVerifier's alone, decided from PayWay's own answer -- to settle a stuck
 * payment by hand, use "php artisan payments:confirm <invoice>", which goes
 * through the same verifier.
 *
 * What this panel can do is the two things that take nothing on trust: archive a
 * row out of the way, and -- on PayWay's word -- record a refund and close the
 * course again.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = $this->status($request);

        $payments = $this->filtered($status, $q)
            ->with(['user:id,uuid,name,email', 'course:id,uuid,title_en,slug'])
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'q' => $q,
            'status' => $status,
            'counts' => $this->counts(),
            'totals' => $this->totals(),
        ]);
    }

    /**
     * The payment book as a CSV, for the accountant and for tax time.
     *
     * Read-only: it cannot change a payment, unlock a course or move money. It
     * exports exactly the rows the current tab and search box are showing, so
     * what you downloaded is what you were looking at.
     *
     * Streamed row by row rather than built in memory, so the whole book can be
     * exported on cheap shared hosting without hitting memory_limit.
     */
    public function export(Request $request): StreamedResponse
    {
        $q = trim((string) $request->query('q', ''));
        $status = $this->status($request);

        $filename = 'payments-'.$status.'-'.now()->format('Y-m-d').'.csv';

        Log::info('Admin exported the payment book', [
            'admin_id' => $request->user()->getKey(),
            'status' => $status,
            'search' => $q,
        ]);

        $rows = $this->filtered($status, $q)->with(['user:id,name,email', 'course:id,title_en']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // Every fputcsv below spells out its separator, quote and escape:
            // PHP 8.4 deprecated leaving $escape to its default and is changing
            // that default, and "" is the RFC 4180 behaviour spreadsheets expect.

            // Excel reads a CSV as the local code page unless the file opens with
            // a byte-order mark. Without this, every Khmer course title and name
            // arrives as mojibake.
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'Invoice',
                'Created',
                'Paid at',
                'Status',
                'Student',
                'Email',
                'Course',
                'Amount',
                'Currency',
                'List price',
                'Discount',
                'Refunded at',
                'Archived',
            ], ',', '"', '');

            // lazy(), not lazyById(): lazyById would replace the newest-first
            // ordering with its own by id, and the file should read in the same
            // order as the page it was downloaded from. Either way only one
            // chunk is held in memory at a time.
            foreach ($rows->lazy(200) as $payment) {
                $amount = (float) $payment->amount;
                $list = $payment->list_price !== null ? (float) $payment->list_price : null;

                fputcsv($out, array_map($this->csvSafe(...), [
                    $payment->invoice_no,
                    $payment->created_at?->format('Y-m-d H:i'),
                    $payment->paid_at?->format('Y-m-d H:i'),
                    $payment->status,
                    // The snapshot first: it is what the payment was made under,
                    // and it survives the student or course being deleted.
                    $payment->payer_name ?: $payment->user?->name,
                    $payment->payer_email ?: $payment->user?->email,
                    $payment->course_title ?: $payment->course?->title_en,
                    number_format($amount, 2, '.', ''),
                    strtoupper((string) ($payment->currency ?: 'USD')),
                    $list !== null ? number_format($list, 2, '.', '') : '',
                    $list !== null && $list > $amount ? number_format($list - $amount, 2, '.', '') : '',
                    $payment->refunded_at?->format('Y-m-d H:i'),
                    $payment->archived_at ? 'yes' : 'no',
                ]), ',', '"', '');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Stop a spreadsheet treating a cell as a formula.
     *
     * Payer names and emails are typed by customers. Excel and Sheets run any
     * cell starting with = + - @ as a formula when the file is opened, so a name
     * like "=HYPERLINK(...)" would become live content in the accountant's
     * spreadsheet. A leading apostrophe makes it plain text again.
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', chr(9), chr(13)], true) ? "'".$value : $value;
    }

    /**
     * Hide a payment from the lists without destroying it.
     *
     * Not a delete on purpose: the row is what explains why a student has access
     * to a course, and the account-deletion page promises people their payment
     * records are kept for accounting. Archiving keeps both promises while
     * letting you clear abandoned QRs out of the way.
     */
    public function archive(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->isArchived()) {
            return back()->with('status', 'That payment was already archived.');
        }

        $payment->forceFill(['archived_at' => now()])->save();

        Log::info('Admin archived a payment', [
            'admin_id' => $request->user()->getKey(),
            'payment_id' => $payment->getKey(),
            'invoice' => $payment->invoice_no,
            'status' => $payment->status,
        ]);

        return back()->with('status', "Invoice {$payment->invoice_no} archived. It is still in the totals — open the Archived tab to bring it back.");
    }

    /**
     * Close a course again after the money was given back.
     *
     * PayWay is asked first and has the final word: unless it reports the
     * transaction as refunded, nothing happens. The refund itself is made in
     * ABA's portal -- this button does not and cannot move money, it only does
     * the part the website is responsible for.
     *
     * The stricter direction is deliberate. Granting access is never possible
     * from this panel at all; taking it away is, but only on the gateway's word.
     */
    public function refund(Request $request, Payment $payment, PayWayVerifier $verifier): RedirectResponse
    {
        if ($refusal = $this->refundRefusal($payment)) {
            return back()->with('error', $refusal);
        }

        if (! $verifier->isPayWay($payment)) {
            return back()->with('error', "Invoice {$payment->invoice_no} is a legacy Bakong payment, so PayWay has nothing to confirm. Use \"Close access anyway\" if you are certain the money went back.");
        }

        try {
            $state = $verifier->recheckPaid($payment);
        } catch (Throwable $e) {
            // PayWay's own wording names hosts and hashes, so it stays in the log.
            report($e);

            return back()->with('error', "Could not reach PayWay to check invoice {$payment->invoice_no}. Nothing was changed — try again, or use \"Close access anyway\" if you are certain.");
        }

        if ($state !== 'refunded') {
            return back()->with('error', "PayWay still reports invoice {$payment->invoice_no} as paid, so nothing was changed. Refund it in the ABA portal first, then try again.");
        }

        Log::warning('Admin refunded a payment (confirmed by PayWay)', [
            'admin_id' => $request->user()->getKey(),
            'payment_id' => $payment->getKey(),
            'invoice' => $payment->invoice_no,
        ]);

        return back()->with('status', "PayWay confirmed the refund of invoice {$payment->invoice_no}. The course is closed again; their lesson progress is kept.");
    }

    /**
     * Record a refund without PayWay's agreement.
     *
     * For the case PayWay cannot answer for: it is unreachable, or the row is a
     * legacy Bakong payment. The operator is vouching for the money having gone
     * back, so the invoice number has to be typed to get here -- and it is
     * checked again on this side, because a confirmation that only exists in the
     * browser is not a confirmation.
     */
    public function refundAnyway(Request $request, Payment $payment, PayWayVerifier $verifier): RedirectResponse
    {
        if ($refusal = $this->refundRefusal($payment)) {
            return back()->with('error', $refusal);
        }

        $typed = trim((string) $request->input('confirm_invoice'));

        if ($typed !== (string) $payment->invoice_no) {
            return back()->with('error', 'The invoice number did not match, so nothing was changed.');
        }

        $verifier->markRefunded($payment, 'admin-forced');

        Log::warning('Admin forced a refund WITHOUT PayWay confirmation', [
            'admin_id' => $request->user()->getKey(),
            'payment_id' => $payment->getKey(),
            'invoice' => $payment->invoice_no,
            'amount' => (string) $payment->amount,
            'ip' => $request->ip(),
        ]);

        return back()->with('status', "Invoice {$payment->invoice_no} recorded as refunded and the course closed. PayWay was not asked — make sure the money really went back.");
    }

    /** Why this payment cannot be refunded, or null when it can. */
    private function refundRefusal(Payment $payment): ?string
    {
        if ($payment->isRefunded()) {
            return "Invoice {$payment->invoice_no} is already recorded as refunded.";
        }

        if (! $payment->isPaid()) {
            return "Invoice {$payment->invoice_no} is \"{$payment->status}\", not paid — there is nothing to refund.";
        }

        return null;
    }

    public function restore(Request $request, Payment $payment): RedirectResponse
    {
        if (! $payment->isArchived()) {
            return back()->with('status', 'That payment is not archived.');
        }

        $payment->forceFill(['archived_at' => null])->save();

        Log::info('Admin restored a payment', [
            'admin_id' => $request->user()->getKey(),
            'payment_id' => $payment->getKey(),
            'invoice' => $payment->invoice_no,
        ]);

        return back()->with('status', "Invoice {$payment->invoice_no} is back in the list.");
    }

    /** One of the real statuses, "all", or the archived tab. */
    /**
     * The rows the chosen tab and search box are asking for.
     *
     * Shared by the on-screen list and the CSV export so a download can never
     * quietly hold different rows than the page it was started from.
     */
    private function filtered(string $status, string $q): Builder
    {
        $showArchived = $status === 'archived';

        return Payment::query()
            // Archived rows are out of the way everywhere except their own tab.
            ->when($showArchived, fn ($query) => $query->archived(), fn ($query) => $query->visible())
            ->when(! $showArchived && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $this->search($query, $q))
            ->orderByDesc('created_at');
    }

    private function status(Request $request): string
    {
        $wanted = (string) $request->query('status', 'all');
        $allowed = array_merge(['all', 'archived'], Payment::STATUSES);

        return in_array($wanted, $allowed, true) ? $wanted : 'all';
    }

    /**
     * Invoice, payer or course.
     *
     * The snapshot columns are searched alongside the live relations, so a
     * payment whose student or course has since been deleted is still findable
     * by the name it was made under.
     */
    private function search(Builder $query, string $q): Builder
    {
        $like = '%'.addcslashes($q, '%_\\').'%';

        return $query->where(function ($w) use ($like) {
            $w->where('invoice_no', 'like', $like)
                ->orWhere('payer_email', 'like', $like)
                ->orWhere('payer_name', 'like', $like)
                ->orWhere('course_title', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->orWhereHas('course', fn ($c) => $c->where('title_en', 'like', $like));
        });
    }

    /**
     * How many rows each tab holds.
     *
     * Counted over the visible rows only, so the numbers add up to what the "All"
     * tab shows. Archived is counted separately for its own tab.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $byStatus = Payment::query()->visible()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['all' => (int) $byStatus->sum()];

        foreach (Payment::STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
        }

        $counts['archived'] = Payment::query()->archived()->count();

        return $counts;
    }

    /**
     * Money actually taken.
     *
     * Only paid rows, and archived ones are included on purpose: archiving is
     * about tidying the list, not about pretending the money never arrived.
     * Grouped by currency because USD and KHR must never be added together.
     *
     * @return array{currencies: array<string, array{total: float, count: int}>, paid_count: int, this_month: array<string, float>}
     */
    private function totals(): array
    {
        $rows = Payment::query()
            ->where('status', 'paid')
            ->selectRaw('UPPER(currency) as cur, COUNT(*) as n, SUM(amount) as total')
            ->groupBy('cur')
            ->get();

        $currencies = [];
        foreach ($rows as $row) {
            $currencies[(string) ($row->cur ?: 'USD')] = [
                'total' => (float) $row->total,
                'count' => (int) $row->n,
            ];
        }

        $thisMonth = Payment::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->selectRaw('UPPER(currency) as cur, SUM(amount) as total')
            ->groupBy('cur')
            ->pluck('total', 'cur')
            ->map(fn ($v) => (float) $v)
            ->all();

        return [
            'currencies' => $currencies,
            'paid_count' => array_sum(array_column($currencies, 'count')),
            'this_month' => $thisMonth,
        ];
    }
}
