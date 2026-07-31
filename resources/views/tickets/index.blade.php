{{-- My tickets — pixel-port of design rTickets (lines 755–784) --}}

<x-app-layout :activeRole="'user'" :navRole="'user'" :avatarRole="'user'">
@php
    $tickets = [
        [
            'event' => 'Saad Lamjarred Concert', 'date' => 'Sat, Jun 15, 2026', 'venue' => 'OLM Souissi',
            'type' => 'General admission', 'code' => 'BK-4C19A7-1', 'status' => 'Valid',
            'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)',
            'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)', 'op' => '1',
        ],
        [
            'event' => 'Saad Lamjarred Concert', 'date' => 'Sat, Jun 15, 2026', 'venue' => 'OLM Souissi',
            'type' => 'General admission', 'code' => 'BK-4C19A7-2', 'status' => 'Valid',
            'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)',
            'grad' => 'linear-gradient(135deg,#1E3A8A,#7C3AED)', 'op' => '1',
        ],
        [
            'event' => 'Digital Future Summit', 'date' => 'Tue, Jun 11, 2026', 'venue' => 'Hôtel Sofitel',
            'type' => 'General admission', 'code' => 'BK-77B210-1', 'status' => 'Valid',
            'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)',
            'grad' => 'linear-gradient(135deg,#082F49,#14B8A6)', 'op' => '1',
        ],
        [
            'event' => 'Casablanca Street Food Fest', 'date' => 'Sat, Jun 20, 2026', 'venue' => 'Ain Diab Corniche',
            'type' => 'General admission', 'code' => 'BK-2E90FF-1', 'status' => 'Valid',
            'badgeBg' => 'rgba(22,163,74,.12)', 'badgeFg' => 'var(--ok)',
            'grad' => 'linear-gradient(135deg,#7C2D12,#F59E0B)', 'op' => '1',
        ],
        [
            'event' => 'Casablanca Street Food Fest', 'date' => 'Sat, Jun 20, 2026', 'venue' => 'Ain Diab Corniche',
            'type' => 'General admission', 'code' => 'BK-2E90FF-2', 'status' => 'Used',
            'badgeBg' => 'rgba(91,119,148,.16)', 'badgeFg' => 'var(--muted)',
            'grad' => 'linear-gradient(135deg,#7C2D12,#F59E0B)', 'op' => '.6',
        ],
    ];

    $qrSvg = '<path d="M1 1h6v6H1zM19 1h6v6h-6zM1 19h6v6H1zM4 4h1v1H4zM7 1h1v1H7zM1 7h1v1H1zM4 10h1v1H4zM1 13h1v1H1zM10 1h1v1h-1zM13 4h1v1h-1zM10 7h1v1h-1zM7 10h1v1H7zM10 13h1v1h-1zM13 10h1v1h-1zM16 7h1v1h-1zM13 16h1v1h-1zM16 13h1v1h-1zM19 10h1v1h-1zM22 13h1v1h-1zM19 16h1v1h-1zM22 19h1v1h-1zM19 22h1v1h-1zM16 19h1v1h-1zM13 22h1v1h-1zM10 19h1v1h-1zM7 16h1v1H7zM4 19h1v1H4zM7 22h1v1H7zM10 22h1v1h-1zM16 16h1v1h-1zM19 19h1v1h-1zM22 22h1v1h-1zM1 4h1v1H1zM4 7h1v1H4zM16 1h1v1h-1zM19 4h1v1h-1zM22 1h1v1h-1zM22 7h1v1h-1zM1 16h1v1H1z" fill="#0B2545"/>';
@endphp

<div style="max-width:1100px;margin:0 auto;padding:34px 26px 60px">
    <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">My tickets</h1>
    <p style="margin:0 0 22px;color:var(--muted);font-size:14.5px">Show the QR code at the door. Works offline.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
        @foreach ($tickets as $t)
            <article style="background:var(--surface);border:1px solid var(--border);border-radius:18px;overflow:hidden;opacity:{{ $t['op'] }}">
                {{-- Gradient header band --}}
                <div style="padding:16px 18px;background:{{ $t['grad'] }};color:#fff">
                    <div style="font-size:16px;font-weight:800;letter-spacing:-.3px">{{ $t['event'] }}</div>
                    <div style="font-size:12.5px;font-weight:600;opacity:.85;margin-top:4px">{{ $t['date'] }} · {{ $t['venue'] }}</div>
                </div>
                {{-- Body --}}
                <div style="padding:18px;display:flex;align-items:center;gap:18px">
                    {{-- QR code 104x104 --}}
                    <svg width="104" height="104" viewBox="0 0 29 29" style="flex:0 0 auto;background:#fff;border-radius:8px;padding:2px">{!! $qrSvg !!}</svg>
                    <div style="display:flex;flex-direction:column;gap:9px">
                        <div>
                            <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.6px">Ticket type</div>
                            <div style="font-size:14px;font-weight:700">{{ $t['type'] }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.6px">Reference</div>
                            <div style="font-size:13.5px;font-weight:700">{{ $t['code'] }}</div>
                        </div>
                        <span style="align-self:flex-start;padding:6px 11px;border-radius:8px;font-size:11px;font-weight:800;text-transform:uppercase;background:{{ $t['badgeBg'] }};color:{{ $t['badgeFg'] }}">{{ $t['status'] }}</span>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
</x-app-layout>
