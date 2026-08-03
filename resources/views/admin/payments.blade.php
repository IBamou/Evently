<x-app-layout :activeNav="'payments'">

    @php $filters ??= []; @endphp

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('admin.events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to admin console</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">All Payments</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Payment records for every booking on the platform.</p>

        @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        {{-- Filter bar: GET to admin.payments.index (contract: $filters {status?, reference?, date_from?, date_to?}) --}}
        <form method="GET" action="{{ route('admin.payments.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
            <select name="status" aria-label="Status" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
                <option value="">All statuses</option>
                @foreach(\App\Enums\PaymentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="text" name="reference" value="{{ $filters['reference'] ?? '' }}" placeholder="Search booking reference&hellip;" aria-label="Search booking reference" style="min-width:190px;min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="Date from" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="Date to" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
            <button type="submit" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Filter</button>
            <a href="{{ route('admin.payments.index') }}" style="border:1px solid var(--border);background:var(--surface2);color:var(--text);text-decoration:none;display:inline-flex;align-items:center;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Clear</a>
        </form>

        <div role="table" aria-label="Payments" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div role="row" style="display:grid;grid-template-columns:1.1fr 1.3fr 1.4fr .8fr .7fr 1fr 1.1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span role="columnheader">Booking</span><span role="columnheader">Customer</span><span role="columnheader">Event</span><span role="columnheader">Amount</span><span role="columnheader">Provider</span><span role="columnheader">Status</span><span role="columnheader">Date</span>
            </div>
            @forelse($payments as $payment)
                @php
                    $pcBg = match($payment->status->value) {
                        'succeeded', 'paid' => 'rgba(22,163,74,.12)',
                        'pending' => 'rgba(217,119,6,.14)',
                        'refunded' => 'rgba(91,119,148,.16)',
                        'failed' => 'rgba(220,38,38,.12)',
                        default => 'var(--chip)',
                    };
                    $pcFg = match($payment->status->value) {
                        'succeeded', 'paid' => 'var(--ok)',
                        'pending' => 'var(--warn)',
                        'refunded' => 'var(--muted)',
                        'failed' => 'var(--err)',
                        default => 'var(--muted)',
                    };
                @endphp
                <div role="row" style="display:grid;grid-template-columns:1.1fr 1.3fr 1.4fr .8fr .7fr 1fr 1.1fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;font-family:monospace;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $payment->booking?->reference ?? 'â€”' }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $payment->booking?->user?->name ?? 'â€”' }}</div>
                        <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $payment->booking?->user?->email ?? '' }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $payment->booking?->event?->title ?? 'â€”' }}</div>
                    </div>
                    <span role="cell" style="font-weight:700">{{ $payment->amount > 0 ? number_format($payment->amount, 0).' '.$payment->currency : 'Free' }}</span>
                    <span role="cell" style="font-weight:600;color:var(--muted)">{{ $payment->provider }}</span>
                    <span role="cell"><span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $pcBg }};color:{{ $pcFg }}">{{ $payment->status->label() }}</span></span>
                    <span role="cell" style="font-size:13px;color:var(--muted)">{{ $payment->paid_at?->format('M d, Y H:i') ?? 'â€”' }}</span>
                </div>
            @empty
                <div style="padding:44px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No payments found</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">Payment records will appear here.</div>
                </div>
            @endforelse
        </div>

        @if($payments->hasPages())
            <div style="margin-top:20px">{{ $payments->links() }}</div>
        @endif
    </div>

</x-app-layout>
