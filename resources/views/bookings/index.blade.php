<x-app-layout :activeNav="'ubookings'">
    <main style="max-width:1100px;margin:0 auto;padding:34px 26px 60px">
        <h1 style="font-size:28px;font-weight:800;letter-spacing:-.9px;margin:0 0 6px">My bookings</h1>
        <p style="font-size:14.5px;color:var(--muted);margin:0 0 22px">Every order you placed on Evently, newest first.</p>

        @if(session('success'))
            <div style="padding:14px 18px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.2);color:var(--ok);font-size:14px;margin-bottom:20px">{{ session('success') }}</div>
        @endif

        {{-- Filter tabs --}}
        <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
            @php $activeStatus = request('status'); @endphp
            <a href="{{ route('bookings.index') }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ !$activeStatus ? 'var(--primary)' : 'var(--border)' }};background:{{ !$activeStatus ? 'var(--primary)' : 'var(--surface)' }};color:{{ !$activeStatus ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">All <span style="opacity:.6">({{ $counts['all'] }})</span></a>
            <a href="{{ route('bookings.index', ['status' => 'confirmed']) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $activeStatus === 'confirmed' ? 'var(--primary)' : 'var(--border)' }};background:{{ $activeStatus === 'confirmed' ? 'var(--primary)' : 'var(--surface)' }};color:{{ $activeStatus === 'confirmed' ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Confirmed <span style="opacity:.6">({{ $counts['confirmed'] }})</span></a>
            <a href="{{ route('bookings.index', ['status' => 'pending']) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $activeStatus === 'pending' ? 'var(--primary)' : 'var(--border)' }};background:{{ $activeStatus === 'pending' ? 'var(--primary)' : 'var(--surface)' }};color:{{ $activeStatus === 'pending' ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Pending <span style="opacity:.6">({{ $counts['pending'] }})</span></a>
            <a href="{{ route('bookings.index', ['status' => 'cancelled']) }}" style="min-height:40px;padding:9px 15px;border:1px solid {{ $activeStatus === 'cancelled' ? 'var(--primary)' : 'var(--border)' }};background:{{ $activeStatus === 'cancelled' ? 'var(--primary)' : 'var(--surface)' }};color:{{ $activeStatus === 'cancelled' ? '#fff' : 'var(--text)' }};border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Cancelled <span style="opacity:.6">({{ $counts['cancelled'] }})</span></a>
        </div>

        {{-- Booking cards --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            @forelse($bookings as $booking)
                @php
                    $statusColor = match($booking->status->value) {
                        'confirmed' => 'var(--ok)',
                        'pending' => 'var(--warn)',
                        'cancelled', 'expired' => 'var(--err)',
                        default => 'var(--muted)',
                    };
                    $statusBg = match($booking->status->value) {
                        'confirmed' => 'rgba(22,163,74,.12)',
                        'pending' => 'rgba(217,119,6,.14)',
                        'cancelled', 'expired' => 'rgba(220,38,38,.12)',
                        default => 'var(--chip)',
                    };
                @endphp
                <article style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                    <div style="width:58px;height:58px;border-radius:14px;background:linear-gradient(135deg,var(--primary),var(--cyan));flex-shrink:0"></div>
                    <div style="flex:1;min-width:190px">
                        <div style="font-size:16px;font-weight:700;margin-bottom:4px">{{ $booking->event->title ?? 'Event' }}</div>
                        <div style="font-size:12.5px;color:var(--muted);font-weight:600">{{ $booking->event->starts_at?->format('M d, Y') ?? '' }} &middot; {{ $booking->event->city ?? '' }} &middot; {{ $booking->reference }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:11px;color:var(--muted);font-weight:800;text-transform:uppercase;margin-bottom:2px">{{ $booking->tickets_count }} ticket{{ $booking->tickets_count !== 1 ? 's' : '' }}</div>
                        <div style="font-size:16px;font-weight:800">{{ $booking->total > 0 ? number_format($booking->total, 0).' '.$booking->currency : 'Free' }}</div>
                    </div>
                    <div style="padding:7px 12px;border-radius:9px;background:{{ $statusBg }};color:{{ $statusColor }};font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">{{ $booking->status->label() }}</div>
                    <a href="{{ route('bookings.show', $booking) }}" style="padding:11px 16px;border:1px solid var(--border);border-radius:11px;background:var(--surface2);color:var(--text);font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;min-height:44px">Details</a>
                </article>
            @empty
                <div style="border:2px dashed var(--border);border-radius:18px;padding:60px;text-align:center">
                    <div style="font-size:17px;font-weight:800;margin-bottom:6px">No bookings yet</div>
                    <div style="font-size:14px;color:var(--muted);margin-bottom:16px">Browse events to get started</div>
                    <a href="{{ route('events.index') }}" style="display:inline-block;padding:10px 20px;background:var(--primary);color:#fff;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none">Browse events</a>
                </div>
            @endforelse
        </div>

        @if($bookings->hasPages())
            <div style="margin-top:24px;text-align:center">{{ $bookings->links() }}</div>
        @endif
    </main>
</x-app-layout>
