<x-app-layout :activeNav="'ubookings'">
    <main style="max-width:1000px;margin:0 auto;padding:34px 26px 60px">
        <a href="{{ route('bookings.index') }}" style="font-size:13px;font-weight:700;color:var(--muted);text-decoration:none;display:inline-block;margin-bottom:12px">&larr; Back to bookings</a>

        @if(session('success'))
            <div style="padding:14px 18px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.2);color:var(--ok);font-size:14px;margin-bottom:20px">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="padding:14px 18px;border-radius:12px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--err);font-size:14px;margin-bottom:20px">{{ session('error') }}</div>
        @endif

        <div style="background:var(--surface);border:1px solid var(--border);border-radius:20px;overflow:hidden">
            {{-- Header strip --}}
            <div style="background:linear-gradient(120deg,var(--primary-dark,#0b2545),var(--primary));padding:26px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                <div>
                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,.78)">Booking Reference</div>
                    <div style="font-size:26px;font-weight:800;color:#fff;letter-spacing:-.6px">{{ $booking->reference }}</div>
                </div>
                @php
                    $statusColor = match($booking->status->value) {
                        'confirmed' => 'var(--ok)',
                        'pending' => 'var(--warn)',
                        'cancelled', 'expired' => 'var(--err)',
                        default => '#fff',
                    };
                @endphp
                <div style="padding:8px 14px;border-radius:10px;background:rgba(255,255,255,.2);color:#fff;font-size:12px;font-weight:800;text-transform:uppercase">{{ $booking->status->label() }}</div>
            </div>

            {{-- Body --}}
            <div style="padding:26px;display:grid;grid-template-columns:1fr 300px;gap:26px">
                {{-- Left --}}
                <div>
                    <h2 style="font-size:21px;font-weight:800;margin:0 0 4px">{{ $booking->event->title ?? 'Event' }}</h2>
                    <p style="font-size:13.5px;color:var(--muted);margin:0 0 20px">{{ $booking->event->starts_at?->format('M d, Y H:i') ?? '' }} &middot; {{ $booking->event->location ?? '' }}</p>

                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;color:var(--muted);margin-bottom:10px">Tickets</div>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
                        @forelse($booking->tickets as $ticket)
                            <div style="border:1px solid var(--border);border-radius:14px;padding:14px;display:flex;align-items:center;gap:14px;{{ $booking->status->value === 'cancelled' ? 'opacity:.55;' : '' }}">
                                <div data-ticket-qr="{{ $ticket->code }}" style="width:60px;height:60px;background:#fff;border:1px solid var(--border);border-radius:6px;padding:2px;flex-shrink:0"></div>
                                <div style="flex:1">
                                    <div style="font-size:14px;font-weight:700">{{ $ticket->ticketType?->name ?? 'Ticket' }}</div>
                                    <div style="font-size:12px;color:var(--muted)">{{ $ticket->code }}</div>
                                </div>
                                @php
                                    $tcColor = match($ticket->status->value) {
                                        'valid' => 'var(--ok)',
                                        'used', 'expired' => 'var(--muted)',
                                        'cancelled' => 'var(--err)',
                                        default => 'var(--muted)',
                                    };
                                    $tcBg = match($ticket->status->value) {
                                        'valid' => 'rgba(22,163,74,.12)',
                                        'used', 'expired' => 'rgba(91,119,148,.16)',
                                        'cancelled' => 'rgba(220,38,38,.12)',
                                        default => 'var(--chip)',
                                    };
                                @endphp
                                <div style="padding:4px 8px;border-radius:8px;background:{{ $tcBg }};color:{{ $tcColor }};font-size:11px;font-weight:800;text-transform:uppercase">{{ $ticket->status->label() }}</div>
                            </div>
                        @empty
                            <p style="color:var(--muted);font-size:14px">No tickets yet.</p>
                        @endforelse
                    </div>

                    <div style="font-size:12px;font-weight:800;text-transform:uppercase;color:var(--muted);margin-bottom:10px">Activity</div>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <div style="display:flex;gap:10px;align-items:flex-start">
                            <div style="width:10px;height:10px;border-radius:50%;background:var(--primary);box-shadow:0 0 0 4px var(--chip);margin-top:5px;flex-shrink:0"></div>
                            <div>
                                <div style="font-size:13.5px;font-weight:700">Booking created</div>
                                <div style="font-size:12px;color:var(--muted)">{{ $booking->created_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                        @if($booking->confirmed_at)
                            <div style="display:flex;gap:10px;align-items:flex-start">
                                <div style="width:10px;height:10px;border-radius:50%;background:var(--ok);box-shadow:0 0 0 4px rgba(22,163,74,.15);margin-top:5px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:13.5px;font-weight:700">Payment completed via Stripe</div>
                                    <div style="font-size:12px;color:var(--muted)">{{ $booking->confirmed_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                        @if($booking->cancelled_at)
                            <div style="display:flex;gap:10px;align-items:flex-start">
                                <div style="width:10px;height:10px;border-radius:50%;background:var(--err);box-shadow:0 0 0 4px rgba(220,38,38,.15);margin-top:5px;flex-shrink:0"></div>
                                <div>
                                    <div style="font-size:13.5px;font-weight:700">Booking cancelled</div>
                                    <div style="font-size:12px;color:var(--muted)">{{ $booking->cancelled_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right aside --}}
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:16px;padding:18px">
                        <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:12px">Payment</div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;font-weight:600;color:var(--muted)">
                            <span>Subtotal</span><span>{{ $booking->subtotal > 0 ? number_format($booking->subtotal, 0).' '.$booking->currency : 'Free' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;font-weight:600;color:var(--muted)">
                            <span>Service fee</span><span>{{ $booking->fees > 0 ? number_format($booking->fees, 0).' '.$booking->currency : 'Free' }}</span>
                        </div>
                        <div style="border-top:1px solid var(--border);padding-top:8px;margin-top:4px;display:flex;justify-content:space-between;font-size:17px;font-weight:800">
                            <span>Total paid</span><span>{{ $booking->total > 0 ? number_format($booking->total, 0).' '.$booking->currency : 'Free' }}</span>
                        </div>
                        <div style="margin-top:8px;font-size:12px;font-weight:800;text-transform:uppercase;color:{{ match($booking->status->value) { 'confirmed' => 'var(--ok)', 'pending' => 'var(--warn)', default => 'var(--err)' } }}">{{ $booking->status->label() }}</div>
                    </div>

                    @if($canPay)
                        <form action="{{ route('bookings.confirm-payment', $booking) }}" method="POST">
                            @csrf
                            <button type="submit" style="width:100%;height:46px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary-dark,#0b2545));color:#fff;font-size:14px;font-weight:700;cursor:pointer">Confirm Payment</button>
                        </form>
                    @endif

                    @if($canCancel)
                        <form action="{{ route('bookings.cancel', $booking) }}" method="POST"
                              x-on:submit.prevent="$dispatch('confirm-ask', { form: $event.target, title: 'Cancel booking?', message: 'This will cancel your booking and release your tickets. Refunds are processed within 5-10 days.', confirmLabel: 'Cancel booking' })">
                            @csrf
                            <button type="submit"
                                    style="width:100%;min-height:48px;padding:13px;border:1px solid rgba(220,38,38,.35);border-radius:12px;background:rgba(220,38,38,.07);color:var(--err);font-size:13.5px;font-weight:800;cursor:pointer">Cancel Booking</button>
                        </form>
                        <p style="font-size:11.5px;color:var(--muted);text-align:center">Refunds are processed manually within 5-10 days.</p>
                    @endif
                </div>
            </div>
        </div>
    </main>

    {{-- Load the QR module only on this page (qrcode itself is lazy-loaded by qr.js) --}}
    @vite('resources/js/qr.js')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.EventlyQr) return;
            document.querySelectorAll('[data-ticket-qr]').forEach(function (el) {
                window.EventlyQr.renderQrCode(el, el.getAttribute('data-ticket-qr'), { size: 60 }).catch(function () {
                    // Lazy chunk failed to load — show the muted fallback in the QR box.
                    window.EventlyQr.showQrFallback(el);
                });
            });
        });
    </script>
</x-app-layout>
