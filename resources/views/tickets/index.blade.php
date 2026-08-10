<x-app-layout :activeNav="'tickets'">
    <style>
        summary::-webkit-details-marker{display:none}
        .ticket-summary{list-style:none}
        .ticket-summary::-webkit-details-marker{display:none}
        .ticket-summary:focus-visible{outline:2px solid var(--primary);outline-offset:2px}
        details[open] .chev{transform:rotate(180deg)}
    </style>

    <main style="max-width:1100px;margin:0 auto;padding:34px 26px 60px">
        <h1 style="font-size:28px;font-weight:800;letter-spacing:-.9px;margin:0 0 6px">My tickets</h1>
        <p style="font-size:14.5px;color:var(--muted);margin:0 0 24px">Show the QR code at the door. Works offline.</p>

        {{-- Status pills (mirror bookings/index) --}}
        <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
            <a href="{{ route('tickets.index') }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ !$status ? 'var(--primary)' : 'var(--border)' }};background:{{ !$status ? 'var(--primary)' : 'var(--surface)' }};color:{{ !$status ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">All <span style="opacity:.6">({{ $counts['all'] }})</span></a>
            <a href="{{ route('tickets.index', ['status' => 'valid']) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $status === 'valid' ? 'var(--primary)' : 'var(--border)' }};background:{{ $status === 'valid' ? 'var(--primary)' : 'var(--surface)' }};color:{{ $status === 'valid' ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Valid <span style="opacity:.6">({{ $counts['valid'] }})</span></a>
            <a href="{{ route('tickets.index', ['status' => 'used']) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $status === 'used' ? 'var(--primary)' : 'var(--border)' }};background:{{ $status === 'used' ? 'var(--primary)' : 'var(--surface)' }};color:{{ $status === 'used' ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Used <span style="opacity:.6">({{ $counts['used'] }})</span></a>
            <a href="{{ route('tickets.index', ['status' => 'cancelled']) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $status === 'cancelled' ? 'var(--primary)' : 'var(--border)' }};background:{{ $status === 'cancelled' ? 'var(--primary)' : 'var(--surface)' }};color:{{ $status === 'cancelled' ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Cancelled <span style="opacity:.6">({{ $counts['cancelled'] }})</span></a>
        </div>

        {{-- Tickets grouped by event --}}
        @forelse($eventGroups as $group)
            @php $event = $group['event']; @endphp
            <section aria-labelledby="tix-group-{{ $event->id }}" style="margin:0 0 14px">
                <details data-ticket-group {{ $loop->first ? 'open' : '' }} style="background:var(--surface);border:1px solid var(--border);border-radius:18px;overflow:hidden">
                    <summary class="ticket-summary" style="display:flex;align-items:center;gap:14px;padding:14px 18px;cursor:pointer;list-style:none">
                        <div style="width:40px;height:40px;border-radius:10px;background:{{ $event->category_gradient }};flex-shrink:0"></div>
                        <div style="display:flex;flex-direction:column;flex:1;min-width:0;gap:2px">
                            <h2 id="tix-group-{{ $event->id }}" style="margin:0;font-size:15px;font-weight:700;letter-spacing:-.2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $event->title }}</h2>
                            <div style="font-size:12.5px;font-weight:600;color:var(--muted)">{{ $event->starts_at?->format('M j, Y') ?? '' }} &middot; {{ $event->location ?? '' }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
                            @if($loop->first && $event->starts_at?->isFuture())
                                <span style="background:var(--chip);color:var(--primary);font-size:10px;font-weight:800;text-transform:uppercase;padding:4px 10px;border-radius:8px">Next up</span>
                            @endif
                            <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--muted);white-space:nowrap">{{ $group['total'] }} ticket{{ $group['total'] !== 1 ? 's' : '' }}</div>
                            <svg class="chev" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="transition:transform .18s"><path d="M4 6l4 4 4-4"/></svg>
                        </div>
                    </summary>
                    <div style="border-top:1px solid var(--border);padding:16px;display:flex;flex-direction:column;gap:10px">
                        @foreach($group['tickets'] as $ticket)
                            @php
                                $isUsed = $ticket->status->value === 'used';
                                $tcColor = match($ticket->status->value) {
                                    'valid' => 'var(--ok)',
                                    'used' => 'var(--muted)',
                                    'cancelled' => 'var(--err)',
                                    default => 'var(--muted)',
                                };
                                $tcBg = match($ticket->status->value) {
                                    'valid' => 'rgba(22,163,74,.12)',
                                    'used' => 'rgba(91,119,148,.16)',
                                    'cancelled' => 'rgba(220,38,38,.12)',
                                    default => 'var(--chip)',
                                };
                            @endphp
                            <div style="border:1px solid var(--border);border-radius:14px;padding:14px;display:flex;align-items:center;gap:14px;{{ $isUsed ? 'opacity:.6;' : '' }}">
                                <div data-ticket-qr="{{ $ticket->code }}" role="img" aria-label="QR code for ticket {{ $ticket->code }}" style="width:104px;height:104px;background:#fff;border:1px solid var(--border);border-radius:8px;padding:2px;flex-shrink:0"></div>
                                <div style="display:flex;flex-direction:column;gap:9px;flex:1;min-width:0">
                                    <div>
                                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--muted)">Ticket Type</div>
                                        <div style="font-size:14px;font-weight:700">{{ $ticket->ticketType?->name ?? 'Ticket' }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--muted)">Reference</div>
                                        <div style="font-size:13.5px;font-weight:700">{{ $ticket->code }}</div>
                                    </div>
                                </div>
                                <div style="align-self:center;padding:6px 11px;border-radius:8px;background:{{ $tcBg }};color:{{ $tcColor }};font-size:11px;font-weight:800;text-transform:uppercase;white-space:nowrap;flex-shrink:0">{{ $ticket->status->label() }}</div>
                            </div>
                        @endforeach
                    </div>
                </details>
            </section>
        @empty
            @if($status)
                <div style="border:2px dashed var(--border);border-radius:18px;padding:60px;text-align:center">
                    <div style="font-size:17px;font-weight:800;margin-bottom:6px">No {{ $status }} tickets</div>
                    <div style="font-size:14px;color:var(--muted);margin-bottom:16px">You don't have any {{ $status }} tickets.</div>
                    <a href="{{ route('tickets.index') }}" style="display:inline-block;padding:10px 20px;background:var(--primary);color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Show all tickets</a>
                </div>
            @else
                <div style="border:2px dashed var(--border);border-radius:18px;padding:60px;text-align:center">
                    <div style="font-size:17px;font-weight:800;margin-bottom:6px">No tickets yet</div>
                    <div style="font-size:14px;color:var(--muted);margin-bottom:16px">Book an event to get your tickets.</div>
                    <a href="{{ route('events.index') }}" style="display:inline-block;padding:10px 20px;background:var(--primary);color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Browse events</a>
                </div>
            @endif
        @endforelse
    </main>

    {{-- Load the QR module only on this page (qrcode itself is lazy-loaded by qr.js) --}}
    @vite('resources/js/qr.js')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.EventlyQr) return;
            var render = function (el) {
                if (el.dataset.qrRendered) return;             // render once
                el.dataset.qrRendered = '1';
                window.EventlyQr.renderQrCode(el, el.getAttribute('data-ticket-qr'), { size: 104 })
                    .catch(function () { window.EventlyQr.showQrFallback(el); });
            };
            document.querySelectorAll('details[open] [data-ticket-qr]').forEach(render);       // auto-opened group only
            document.querySelectorAll('details[data-ticket-group]').forEach(function (d) {
                d.addEventListener('toggle', function () {     // lazy render on first expand
                    if (d.open) d.querySelectorAll('[data-ticket-qr]').forEach(render);
                });
            });
        });
    </script>
</x-app-layout>
