<x-app-layout :activeNav="'oevents'">

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('organizer.events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to my events</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Bookings — {{ $event->title }}</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">All bookings and attendees for this event.</p>

        @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        {{-- Bookings table --}}
        <div style="margin-bottom:12px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">Bookings</div>
        <div role="table" aria-label="Bookings" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;margin-bottom:36px">
            <div role="row" style="display:grid;grid-template-columns:1.2fr 1.5fr .6fr .8fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span role="columnheader">Reference</span><span role="columnheader">Customer</span><span role="columnheader">Tickets</span><span role="columnheader">Total</span><span role="columnheader">Status</span>
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
                <div role="row" style="display:grid;grid-template-columns:1.2fr 1.5fr .6fr .8fr 1fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $booking->reference }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $booking->user?->name ?? '—' }}</div>
                        <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $booking->user?->email ?? '' }}</div>
                    </div>
                    <span role="cell" style="font-weight:700">{{ $booking->tickets_count }}</span>
                    <span role="cell" style="font-weight:700">{{ $booking->total > 0 ? number_format($booking->total, 0).' '.$booking->currency : 'Free' }}</span>
                    <span role="cell"><span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $scBg }};color:{{ $scFg }}">{{ $booking->status->label() }}</span></span>
                </div>
            @empty
                <div style="padding:44px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No bookings yet</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">Bookings for this event will appear here.</div>
                </div>
            @endforelse
        </div>

        @if($bookings->hasPages())
            <div style="margin-bottom:36px">{{ $bookings->links() }}</div>
        @endif

        {{-- Attendees table --}}
        <div style="margin-bottom:12px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">Attendees</div>
        <div role="table" aria-label="Attendees" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div role="row" style="display:grid;grid-template-columns:1.2fr 1.5fr 1.2fr 1fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span role="columnheader">Name</span><span role="columnheader">Email</span><span role="columnheader">Ticket Type</span><span role="columnheader">Code</span><span role="columnheader">Status</span>
            </div>
            @forelse($attendees as $ticket)
                @php
                    $tcBg = match($ticket->status->value) {
                        'valid' => 'rgba(22,163,74,.12)',
                        'used', 'expired' => 'rgba(91,119,148,.16)',
                        'cancelled' => 'rgba(220,38,38,.12)',
                        default => 'var(--chip)',
                    };
                    $tcFg = match($ticket->status->value) {
                        'valid' => 'var(--ok)',
                        'used', 'expired' => 'var(--muted)',
                        'cancelled' => 'var(--err)',
                        default => 'var(--muted)',
                    };
                @endphp
                <div role="row" style="display:grid;grid-template-columns:1.2fr 1.5fr 1.2fr 1fr 1fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->user?->name ?? '—' }}</div>
                    </div>
                    <span role="cell" style="color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->user?->email ?? '' }}</span>
                    <span role="cell" style="font-weight:600">{{ $ticket->ticketType?->name ?? '—' }}</span>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;font-family:monospace;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->code }}</div>
                    </div>
                    <span role="cell"><span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $tcBg }};color:{{ $tcFg }}">{{ $ticket->status->label() }}</span></span>
                </div>
            @empty
                <div style="padding:44px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No attendees yet</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">Attendees will appear here once tickets are purchased.</div>
                </div>
            @endforelse
        </div>

        @if($attendees->hasPages())
            <div style="margin-top:20px">{{ $attendees->links() }}</div>
        @endif
    </div>

</x-app-layout>
