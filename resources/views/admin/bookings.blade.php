<x-app-layout :activeNav="'bookings'">

    @php $filters ??= []; @endphp

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('admin.events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to admin console</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">All Bookings</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Track every booking across all events on the platform.</p>

        @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        {{-- Filter bar: GET to admin.bookings.index (contract: $filters {status?, search?}) --}}
        <form method="GET" action="{{ route('admin.bookings.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
            <select name="status" aria-label="Status" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
                <option value="">All statuses</option>
                @foreach(\App\Enums\BookingStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search reference, customer or event&hellip;" aria-label="Search bookings" style="flex:1;min-width:220px;min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
            <button type="submit" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Filter</button>
            <a href="{{ route('admin.bookings.index') }}" style="border:1px solid var(--border);background:var(--surface2);color:var(--text);text-decoration:none;display:inline-flex;align-items:center;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Clear</a>
        </form>

        <div role="table" aria-label="Bookings" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div role="row" style="display:grid;grid-template-columns:1.1fr 1.4fr 1.4fr .5fr .8fr 1fr .8fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span role="columnheader">Reference</span><span role="columnheader">Customer</span><span role="columnheader">Event</span><span role="columnheader">Items</span><span role="columnheader">Total</span><span role="columnheader">Status</span><span role="columnheader" style="text-align:right">Actions</span>
            </div>
            @forelse($bookings as $booking)
                @php
                    $scBg = match($booking->status->value) {
                        'confirmed' => 'rgba(22,163,74,.12)',
                        'pending' => 'rgba(217,119,6,.14)',
                        'cancelled', 'expired' => 'rgba(220,38,38,.12)',
                        default => 'var(--chip)',
                    };
                    $scFg = match($booking->status->value) {
                        'confirmed' => 'var(--ok)',
                        'pending' => 'var(--warn)',
                        'cancelled', 'expired' => 'var(--err)',
                        default => 'var(--muted)',
                    };
                @endphp
                <div role="row" style="display:grid;grid-template-columns:1.1fr 1.4fr 1.4fr .5fr .8fr 1fr .8fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;font-family:monospace;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $booking->reference }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $booking->user?->name ?? '—' }}</div>
                        <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $booking->user?->email ?? '' }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $booking->event?->title ?? '—' }}</div>
                    </div>
                    <span role="cell" style="font-weight:700">{{ $booking->tickets_count }}</span>
                    <span role="cell" style="font-weight:700">{{ $booking->total > 0 ? number_format($booking->total, 0).' '.$booking->currency : 'Free' }}</span>
                    <span role="cell"><span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $scBg }};color:{{ $scFg }}">{{ $booking->status->label() }}</span></span>
                    <div role="cell" style="display:flex;gap:6px;justify-content:flex-end;align-items:center">
                        @if(!in_array($booking->status->value, ['cancelled', 'expired']))
                            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST" x-on:submit.prevent="$dispatch('confirm-ask', { form: $event.target, title: 'Cancel this booking?', message: 'This will cancel the attendee\'s booking and release their tickets. This action cannot be undone.', confirmLabel: 'Cancel booking' })">
                                @csrf
                                <button type="submit" title="Cancel" aria-label="Cancel booking" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid rgba(220,38,38,.35);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--err)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M5.6 5.6l12.8 12.8"></path></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding:44px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No bookings found</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">Bookings will appear here once customers start purchasing.</div>
                </div>
            @endforelse
        </div>

        @if($bookings->hasPages())
            <div style="margin-top:20px">{{ $bookings->links() }}</div>
        @endif
    </div>

</x-app-layout>
