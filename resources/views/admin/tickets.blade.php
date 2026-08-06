<x-app-layout :activeNav="'admin.tickets'">

    @php $filters ??= []; @endphp

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('admin.events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to admin console</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">All Tickets</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Overview of every issued ticket across the platform.</p>

        @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        {{-- Filter bar: GET to admin.tickets.index (contract: $filters {event_id?, status?, search?} — no events list is passed to this view yet, so status + search only) --}}
        <form method="GET" action="{{ route('admin.tickets.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
            <select name="status" aria-label="Status" style="min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
                <option value="">All statuses</option>
                @foreach(\App\Enums\TicketStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search code, holder or event&hellip;" aria-label="Search tickets" style="flex:1;min-width:220px;min-height:40px;padding:10px 13px;border:1px solid var(--border);background:var(--surface2);border-radius:10px;font-size:13px;outline:none">
            <button type="submit" style="border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Filter</button>
            <a href="{{ route('admin.tickets.index') }}" style="border:1px solid var(--border);background:var(--surface2);color:var(--text);text-decoration:none;display:inline-flex;align-items:center;font-weight:700;font-size:13px;padding:10px 16px;border-radius:10px;min-height:40px">Clear</a>
        </form>

        <div role="table" aria-label="Tickets" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div role="row" style="display:grid;grid-template-columns:1fr 1.4fr 1.4fr 1fr 1fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span role="columnheader">Code</span><span role="columnheader">Holder</span><span role="columnheader">Event</span><span role="columnheader">Type</span><span role="columnheader">Status</span>
            </div>
            @forelse($tickets as $ticket)
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
                <div role="row" style="display:grid;grid-template-columns:1fr 1.4fr 1.4fr 1fr 1fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;font-family:monospace;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->code }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->user?->name ?? '—' }}</div>
                        <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $ticket->user?->email ?? '' }}</div>
                    </div>
                    <div role="cell" style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ticket->event?->title ?? '—' }}</div>
                    </div>
                    <span role="cell" style="font-weight:600;color:var(--muted)">{{ $ticket->ticketType?->name ?? '—' }}</span>
                    <div role="cell">
                        <span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $tcBg }};color:{{ $tcFg }}">{{ $ticket->status->label() }}</span>
                        @if($ticket->status->value === 'used' && $ticket->checked_in_at)
                            <div style="font-size:11.5px;color:var(--muted);font-weight:600;margin-top:3px">Checked in {{ $ticket->checked_in_at->format('M j, H:i') }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding:44px 20px;text-align:center">
                    <div style="font-size:15px;font-weight:800;margin-bottom:6px">No tickets found</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">Issued tickets will appear here.</div>
                </div>
            @endforelse
        </div>

        @if($tickets->hasPages())
            <div style="margin-top:20px">{{ $tickets->links() }}</div>
        @endif
    </div>

</x-app-layout>
