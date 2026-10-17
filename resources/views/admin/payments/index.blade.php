<x-admin-layout title="Payments" active="payments">

    @php
        $tabs = [
            'all' => 'All',
            'paid' => 'Paid',
            'pending' => 'Waiting',
            'review' => 'Being checked',
            'refunded' => 'Refunded',
            'declined' => 'Not paid',
            'expired' => 'Expired',
            'archived' => 'Archived',
        ];
        $currentUrl = fn (array $overrides = []) => route('admin.payments', array_filter(array_merge([
            'status' => $status !== 'all' ? $status : null,
            'q' => $q !== '' ? $q : null,
        ], $overrides), fn ($v) => $v !== null && $v !== ''));
        $exportSummary = trim(implode(' ', array_filter([
            number_format($payments->total()),
            Str::plural('row', $payments->total()),
            $status !== 'all' ? strtolower($tabs[$status] ?? $status).' only' : null,
            $q !== '' ? 'matching "'.$q.'"' : null,
        ])));
        $exportUrl = fn () => route('admin.payments.export', array_filter([
            'status' => $status !== 'all' ? $status : null,
            'q' => $q !== '' ? $q : null,
        ], fn ($v) => $v !== null && $v !== ''));
        $money = fn (string $currency, $value) => $currency === 'KHR' ? number_format($value).' ៛' : '$'.number_format($value, 2);
    @endphp

    <div style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Payments</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:62ch">
                    @if ($q !== '')
                        {{ number_format($payments->total()) }} {{ Str::plural('result', $payments->total()) }} for "{{ $q }}".
                        <a href="{{ $currentUrl(['q' => null]) }}" style="color:var(--accent-text); font-weight:600">Clear search</a>
                    @else
                        Every payment and what happened to it. A course unlocks only when ABA PayWay
                        confirms the payment. This page reports; it does not unlock.
                    @endif
                </p>
            </div>

            <form action="{{ route('admin.payments') }}" method="GET" role="search"
                style="display:flex; gap:8px; align-items:center; flex:1 1 320px; max-width:420px">
                @if ($status !== 'all')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div style="position:relative; flex:1; min-width:0">
                    <x-admin.icon name="search" class="ad-i16" style="position:absolute; left:12px; top:12px; color:var(--text-3); pointer-events:none" />
                    <input type="search" name="q" value="{{ $q }}" class="ad-input" style="padding-left:36px"
                        placeholder="Invoice, name, email or course" aria-label="Search payments">
                </div>
                <button type="submit" class="ad-btn">Search</button>
            </form>
        </div>

        {{-- The accountant's copy. Exports the rows this tab and search are
             showing, not the whole book, so what you download is what you see. --}}
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px">
            <a href="{{ $exportUrl() }}" class="ad-btn ad-btn-sm">
                <x-admin.icon name="download" class="ad-i16" />
                Download CSV
            </a>
            <p class="ad-muted ad-xs" style="margin:0">
                {{ $exportSummary }} · opens in Excel or Google Sheets
            </p>
        </div>

        {{-- Money actually taken. Never adds two currencies into one number. --}}
        @if ($totals['currencies'])
            <div class="ad-grid ad-grid-16 ad-cols-sm-2 ad-cols-xl-4">
                @foreach ($totals['currencies'] as $currency => $sum)
                    <div class="ad-card" style="padding:18px 20px">
                        <p class="ad-sm ad-muted" style="margin:0; font-weight:600">Total received ({{ $currency }})</p>
                        <p class="ad-kpi-value" style="margin-bottom:0">{{ $money($currency, $sum['total']) }}</p>
                        <p class="ad-muted ad-xs" style="margin:10px 0 0">
                            {{ number_format($sum['count']) }} paid {{ Str::plural('payment', $sum['count']) }}
                            @if (! empty($totals['this_month'][$currency]))
                                · {{ $money($currency, $totals['this_month'][$currency]) }} this month
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        <nav class="ad-seg" aria-label="Filter by status" style="justify-self:start; max-width:100%">
            @foreach ($tabs as $key => $label)
                <a href="{{ $currentUrl(['status' => $key !== 'all' ? $key : null]) }}" @if ($status === $key) aria-current="page" @endif>
                    {{ $label }} <span>{{ number_format($counts[$key] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>

        @if ($status === 'archived')
            <div class="ad-callout">
                <x-admin.icon name="archive" class="ad-i18" style="flex:none; margin-top:1px; color:var(--text-3)" />
                <div>
                    Archived payments are hidden from the other tabs but are <strong>not deleted</strong>.
                    They still count in the totals above. Payment records are kept for accounting, and a paid
                    record is what explains why a student has access to a course.
                </div>
            </div>
        @endif

        @if ($payments->isEmpty())
            <div class="ad-card" style="padding:56px 24px; text-align:center">
                <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="card" /></span>
                <p class="ad-h2" style="font-size:17px">
                    @if ($q !== '')
                        Nothing matches "{{ $q }}"
                    @elseif ($status === 'archived')
                        Nothing has been archived
                    @elseif ($status !== 'all')
                        No {{ strtolower($tabs[$status]) }} payments
                    @else
                        No payments yet
                    @endif
                </p>
                <p class="ad-muted ad-sm" style="margin:6px auto 0; max-width:42ch">
                    @if ($q !== '' || $status !== 'all')
                        Try another tab or clear the search.
                    @else
                        They appear here as soon as someone starts a checkout.
                    @endif
                </p>
                @if ($q !== '' || $status !== 'all')
                    <a href="{{ route('admin.payments') }}" class="ad-btn ad-btn-sm" style="margin-top:16px">Show all payments</a>
                @endif
            </div>
        @else
            <div class="ad-card">
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th class="ad-num">Amount</th>
                                <th>Status</th>
                                <th style="text-align:right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>
                                        <span class="pd-mono" style="display:block; font-size:12.5px; font-weight:600">{{ $payment->invoice_no }}</span>
                                        <span class="ad-muted ad-xs">
                                            {{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y, H:i') }}
                                            @if ($payment->isPaid()) · paid @endif
                                        </span>
                                        @if ($payment->isArchived())
                                            <span class="ad-badge" style="display:flex; width:max-content; margin-top:6px">Archived</span>
                                        @endif
                                    </td>
                                    <td class="ad-col-main">
                                        <span style="display:block; font-weight:600">{{ $payment->payerName() }}</span>
                                        <span class="ad-muted ad-xs">{{ $payment->payerEmail() }}</span>
                                    </td>
                                    <td class="ad-sm" style="white-space:normal; min-width:160px">
                                        @if ($payment->course)
                                            <a href="{{ route('courses.show', $payment->course) }}" target="_blank" rel="noopener"
                                                style="color:var(--text); font-weight:550; text-decoration:underline; text-decoration-color:var(--border-strong); text-underline-offset:3px">
                                                {{ $payment->courseTitle() }}
                                            </a>
                                        @else
                                            {{-- The course is gone; the snapshot on the row is all that is left. --}}
                                            <span class="ad-muted">{{ $payment->courseTitle() }}</span>
                                        @endif
                                    </td>
                                    <td class="ad-num ad-sm" style="font-weight:600">{{ $payment->formattedAmount() }}</td>
                                    <td>
                                        <span class="ad-badge dot {{ $payment->statusTone() }}">{{ $payment->statusLabel() }}</span>
                                        @if ($payment->status === 'review')
                                            <div class="ad-xs" style="margin-top:6px; max-width:22ch; white-space:normal; color:var(--warning-text)">
                                                Amount did not match. Check by hand.
                                            </div>
                                        @elseif ($payment->isRefunded())
                                            <div class="ad-muted ad-xs" style="margin-top:6px">
                                                Refunded {{ $payment->refunded_at?->format('j M Y') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display:grid; justify-items:end; gap:6px">
                                            <div style="display:flex; gap:6px; align-items:center">
                                                {{--
                                                    Refunding is the one action here that takes something away from a
                                                    paying customer, so it is the one with real friction: PayWay is asked
                                                    first, and the override needs the invoice number typed.
                                                --}}
                                                @if ($payment->isPaid())
                                                    <form action="{{ route('admin.payments.refund', $payment) }}" method="POST"
                                                        data-confirm="{{ 'Ask PayWay whether invoice '.$payment->invoice_no.' was refunded?'
                                                            ."

".'If PayWay confirms it, '.$payment->payerName().' loses access to “'.$payment->courseTitle().'”.'
                                                            ."
".'If PayWay still says it is paid, nothing changes.'
                                                            ."

".'This does NOT move any money. Refund it in the ABA portal.' }}"
                                                        data-confirm-title="Check this refund with PayWay?" data-confirm-ok="Ask PayWay" data-confirm-tone="danger">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="ad-btn ad-btn-sm">
                                                            <x-admin.icon name="undo" class="ad-i16" /> Refund
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($payment->isArchived())
                                                    <form action="{{ route('admin.payments.restore', $payment) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="ad-btn ad-btn-sm">
                                                            <x-admin.icon name="undo" class="ad-i16" /> Restore
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.payments.archive', $payment) }}" method="POST"
                                                        data-confirm="{{ 'Invoice '.$payment->invoice_no }} stays in the database and in the totals. You can restore it from the Archived tab." data-confirm-title="Hide this invoice?" data-confirm-ok="Hide invoice" data-confirm-tone="primary">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn" style="width:32px"
                                                            title="Archive" aria-label="Archive invoice {{ $payment->invoice_no }}">
                                                            <x-admin.icon name="archive" class="ad-i16" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            {{-- The override: deliberately quieter than a button, and it still asks for the invoice number. --}}
                                            @if ($payment->isPaid())
                                                <form action="{{ route('admin.payments.refund-anyway', $payment) }}" method="POST"
                                                    x-data
                                                    @submit.prevent="
                                                        (() => {
                                                            const typed = window.prompt(
                                                                'Close access WITHOUT asking PayWay.\n\n'
                                                                + 'Only do this if you are certain the money has gone back.\n\n'
                                                                + 'Type the invoice number to confirm:'
                                                            );
                                                            if (typed === null) return;
                                                            $refs.typed.value = typed.trim();
                                                            $el.submit();
                                                        })()
                                                    ">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="confirm_invoice" x-ref="typed" value="">
                                                    <button type="submit" class="ad-xs"
                                                        style="padding:0; border:0; background:none; font:inherit; font-size:12px; font-weight:550; color:var(--danger-text); text-decoration:underline; text-underline-offset:2px; cursor:pointer">
                                                        Close access anyway
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $payments->links() }}</div>
        @endif

        <p class="ad-callout ad-muted" style="margin:0">
            <x-admin.icon name="alert" class="ad-i18" style="flex:none; margin-top:1px; color:var(--text-3)" />
            <span>
                A payment stuck on <strong style="color:var(--text)">Waiting</strong> after the money left the customer's account can be
                settled with <code class="pd-mono">php artisan payments:confirm {{ $payments->first()->invoice_no ?? 'PD26...' }}</code>,
                which asks PayWay directly and unlocks the course.
            </span>
        </p>
    </div>

</x-admin-layout>
