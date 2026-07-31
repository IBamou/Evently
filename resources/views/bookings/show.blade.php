{{-- Booking detail — pixel-port of design rBooking (lines 696–753) --}}

<x-app-layout :activeRole="'user'" :navRole="'user'" :avatarRole="'user'">
@php
    $booking = [
        'ref' => 'BK-4C19A7',
        'status' => 'Confirmed',
        'event' => 'Saad Lamjarred Concert',
        'date' => 'Sat, Jun 15, 2026 · 8:00 PM',
        'venue' => 'OLM Souissi, Rabat',
        'tickets' => [
            ['type' => 'General admission', 'code' => 'BK-4C19A7-1', 'status' => 'Valid', 'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)', 'op' => '1'],
            ['type' => 'General admission', 'code' => 'BK-4C19A7-2', 'status' => 'Valid', 'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)', 'op' => '1'],
        ],
        'timeline' => [
            ['label' => 'Booking created', 'when' => '12 May 2026 · 14:02'],
            ['label' => 'Payment completed via Stripe', 'when' => '12 May 2026 · 14:03'],
            ['label' => 'Tickets issued with QR codes', 'when' => '12 May 2026 · 14:03'],
        ],
        'subtotal' => '630 MAD',
        'fee' => '32 MAD',
        'total' => '662 MAD',
    ];

    $qrSvg = '<path d="M1 1h6v6H1zM19 1h6v6h-6zM1 19h6v6H1zM4 4h1v1H4zM7 1h1v1H7zM1 7h1v1H1zM4 10h1v1H4zM1 13h1v1H1zM10 1h1v1h-1zM13 4h1v1h-1zM10 7h1v1h-1zM7 10h1v1H7zM10 13h1v1h-1zM13 10h1v1h-1zM16 7h1v1h-1zM13 16h1v1h-1zM16 13h1v1h-1zM19 10h1v1h-1zM22 13h1v1h-1zM19 16h1v1h-1zM22 19h1v1h-1zM19 22h1v1h-1zM16 19h1v1h-1zM13 22h1v1h-1zM10 19h1v1h-1zM7 16h1v1H7zM4 19h1v1H4zM7 22h1v1H7zM10 22h1v1h-1zM16 16h1v1h-1zM19 19h1v1h-1zM22 22h1v1h-1zM1 4h1v1H1zM4 7h1v1H4zM16 1h1v1h-1zM19 4h1v1h-1zM22 1h1v1h-1zM22 7h1v1h-1zM1 16h1v1H1z" fill="#0B2545"/>';
@endphp

<div style="max-width:1000px;margin:0 auto;padding:34px 26px 60px">
    {{-- Back link --}}
    <a href="{{ url('/preview/ubookings?role=user') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:12px;display:inline-block;text-decoration:none">&larr; Back to bookings</a>

    <div style="background:var(--surface);border:1px solid var(--border);border-radius:20px;overflow:hidden">
        {{-- Gradient header strip --}}
        <div style="padding:26px;background:linear-gradient(120deg,var(--primary-dark),var(--primary));color:#fff;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
            <div>
                <div style="font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;opacity:.78">Booking reference</div>
                <div style="font-size:26px;font-weight:800;letter-spacing:-.6px;margin-top:4px">{{ $booking['ref'] }}</div>
            </div>
            <div style="flex:1"></div>
            <span style="padding:8px 14px;border-radius:10px;background:rgba(255,255,255,.2);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $booking['status'] }}</span>
        </div>

        {{-- Body: 2-col grid --}}
        <div style="padding:26px;display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:26px;align-items:start">
            {{-- Left column --}}
            <div>
                <h2 style="margin:0 0 4px;font-size:21px;font-weight:800;letter-spacing:-.5px">{{ $booking['event'] }}</h2>
                <div style="font-size:13.5px;color:var(--muted);font-weight:600;margin-bottom:22px">{{ $booking['date'] }} · {{ $booking['venue'] }}</div>

                {{-- Tickets --}}
                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:12px">Tickets</div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach ($booking['tickets'] as $t)
                        <div style="border:1px solid var(--border);border-radius:14px;padding:14px;display:flex;align-items:center;gap:14px;opacity:{{ $t['op'] }}">
                            {{-- QR code placeholder --}}
                            <svg width="60" height="60" viewBox="0 0 29 29" style="flex:0 0 auto;background:#fff;border-radius:6px">{!! $qrSvg !!}</svg>
                            <div style="flex:1">
                                <div style="font-size:14px;font-weight:700">{{ $t['type'] }}</div>
                                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:3px">{{ $t['code'] }}</div>
                            </div>
                            <span style="padding:6px 11px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $t['badgeBg'] }};color:{{ $t['badgeFg'] }}">{{ $t['status'] }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Activity timeline --}}
                <div style="margin-top:24px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:12px">Activity</div>
                <div style="display:flex;flex-direction:column;gap:0">
                    @foreach ($booking['timeline'] as $a)
                        <div style="display:flex;gap:14px;padding:10px 0">
                            <div style="width:10px;height:10px;border-radius:50%;background:var(--primary);margin-top:5px;flex:0 0 auto;box-shadow:0 0 0 4px var(--chip)"></div>
                            <div>
                                <div style="font-size:13.5px;font-weight:700">{{ $a['label'] }}</div>
                                <div style="font-size:12px;color:var(--muted);font-weight:600">{{ $a['when'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right aside --}}
            <aside style="display:flex;flex-direction:column;gap:14px">
                {{-- Payment card --}}
                <div style="background:var(--surface2);border:1px solid var(--border);border-radius:16px;padding:18px">
                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:12px">Payment</div>
                    <div style="display:flex;flex-direction:column;gap:9px">
                        <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600"><span style="color:var(--muted)">Subtotal</span><span>{{ $booking['subtotal'] }}</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600"><span style="color:var(--muted)">Service fee</span><span>{{ $booking['fee'] }}</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:800"><span>Total paid</span><span>{{ $booking['total'] }}</span></div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:800"><span>Status</span><span>Completed</span></div>
                    </div>
                </div>

                {{-- Cancel button --}}
                <button type="button" style="border:1px solid rgba(220,38,38,.35);background:rgba(220,38,38,.07);color:var(--err);cursor:pointer;font-size:13.5px;font-weight:800;padding:13px;border-radius:12px;min-height:48px">Cancel booking</button>
                <p style="margin:0;font-size:11.5px;color:var(--muted)">Refunds are issued to the original payment method within 5–10 days.</p>
            </aside>
        </div>
    </div>
</div>
</x-app-layout>
