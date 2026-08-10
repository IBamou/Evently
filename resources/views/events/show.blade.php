{{-- Event detail — pixel-port of design rDetail (lines 503–598).
     Real data: $event (published, organizer + category loaded). No ticket/pricing
     tables exist yet, so the booking widget is a neutral "coming soon" card. --}}

<x-app-layout :activeNav="'events'">
@php
    // Hero background: banner image when uploaded, else the category gradient
    // from the shared helper. Blade {{ }} escapes once — e() here would double-
    // escape & into &amp;amp; and break the imgix w= param (full-res downloads).
    $heroBg = $event->banner_url
        ? "url('" . $event->banner_url . "') center/cover"
        : $event->category_gradient;

    $dateLong = $event->starts_at?->format('D, M j, Y · g:i A') ?: '';
    $isEnded = ! $event->isUpcoming();
    $badgeLabel = $isEnded ? 'Ended' : 'On sale';
    $badgeBg = $isEnded ? '#334155' : 'var(--ok)';

@endphp

<div style="max-width:1380px;margin:0 auto;padding:22px 26px 60px">
    {{-- Back link --}}
    <a href="{{ route('events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:12px;display:inline-block;text-decoration:none">&larr; Back to events</a>

    {{-- Hero image/art area --}}
    <div style="position:relative;height:320px;border-radius:20px;overflow:hidden;background:{{ $heroBg }};display:flex;align-items:flex-end;padding:28px">
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(4,20,40,0) 30%,rgba(4,20,40,.78))"></div>
        <div style="position:relative;display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <span style="padding:6px 11px;border-radius:8px;background:rgba(255,255,255,.92);color:#0B2545;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $event->category?->name ?? 'Event' }}</span>
                <span style="padding:6px 11px;border-radius:8px;background:{{ $badgeBg }};color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $badgeLabel }}</span>
            </div>
            <h1 style="margin:0;color:#fff;font-size:38px;font-weight:800;letter-spacing:-1.2px;max-width:22ch;line-height:1.08">{{ $event->title }}</h1>
            <div style="display:flex;gap:18px;flex-wrap:wrap;color:rgba(255,255,255,.9);font-size:14px;font-weight:600">
                <span>{{ $dateLong }}</span>
                <span>{{ $event->location }}, {{ $event->city }}</span>
            </div>
        </div>
    </div>

    {{-- Content grid --}}
    <div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:26px;margin-top:26px;align-items:start">

        {{-- Left column --}}
        <div style="display:flex;flex-direction:column;gap:20px">

            {{-- About card --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
                <h2 style="margin:0 0 12px;font-size:18px;font-weight:800;letter-spacing:-.4px">About this event</h2>
                <p style="margin:0;color:var(--muted);font-size:14.5px;line-height:1.75;text-wrap:pretty">{{ $event->description }}</p>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:22px">
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Date & time</div>
                        <div style="font-size:14px;font-weight:700">{{ $dateLong }}</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Format</div>
                        <div style="font-size:14px;font-weight:700">{{ $event->format?->label() ?? 'In person' }}</div>
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:13px;padding:14px">
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">Category</div>
                        <div style="font-size:14px;font-weight:700">{{ $event->category?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Organizer card --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--cyan));color:#fff;display:grid;place-items:center;font-weight:800">{{ mb_strtoupper(mb_substr($event->organizer?->name ?? 'E', 0, 1)) }}</div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px">Organizer</div>
                        <div style="font-size:15px;font-weight:700">{{ $event->organizer?->name ?? 'Evently' }}</div>
                    </div>
                    <div style="flex:1"></div>
                    <button type="button" id="share-btn" aria-label="Share event" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:13px;font-weight:700;padding:11px 16px;border-radius:11px;min-height:44px">Share</button>
                </div>
            </div>

            {{-- You may also like --}}
            @if ($related->isNotEmpty())
                <div>
                    <h2 style="margin:0 0 14px;font-size:18px;font-weight:800;letter-spacing:-.4px">You may also like</h2>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        @foreach ($related as $e)
                            <a href="{{ route('events.show', $e) }}" style="border:1px solid var(--border);border-radius:15px;overflow:hidden;background:var(--surface);cursor:pointer;display:block;text-decoration:none;color:inherit" class="ev-card">
                                <div style="height:96px;background:{{ $e->category_gradient }}"></div>
                                <div style="padding:13px">
                                    <div style="font-size:14px;font-weight:700;margin-bottom:6px">{{ $e->title }}</div>
                                    <div style="font-size:12px;color:var(--muted);font-weight:600">{{ $e->starts_at?->format('D, j M') }} · {{ $e->city }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right aside (sticky) --}}
        <aside style="position:sticky;top:82px;display:flex;flex-direction:column;gap:16px">

            {{-- Booking card — real ticket types (design rDetail L561-596) --}}
            @php
                $hasTypes = $ticketTypes->contains(fn ($tt) => $tt['is_sales_open'] && $tt['available_quantity'] > 0);
                $widgetCurrency = $ticketTypes->first()['currency'] ?? 'MAD';
            @endphp
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:var(--shadow)">
                <div style="font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:14px">Select tickets</div>

                @forelse($ticketTypes as $tt)
                    @php
                        $open = $tt['is_sales_open'] && $tt['available_quantity'] > 0;
                        $stockLabel = $tt['available_quantity'] > 0
                            ? $tt['available_quantity'].' left'
                            : ($tt['is_sales_open'] ? 'Sold out' : 'Unavailable');
                        $windowLabel = $tt['is_sales_open']
                            ? 'On sale now'
                            : ($tt['sales_end_at']?->isPast()
                                ? 'Sales ended'
                                : ($tt['sales_start_at']?->isFuture()
                                    ? 'On sale '.$tt['sales_start_at']->format('M j')
                                    : 'On sale now'));
                    @endphp
                    <div data-widget-row data-tt-id="{{ $tt['id'] }}" data-price="{{ $tt['price'] }}" data-max="{{ min($tt['max_per_booking'], $tt['available_quantity']) }}"
                         style="border:1px solid var(--border);border-radius:14px;padding:14px;margin-bottom:10px;transition:border-color .18s,background .18s">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px">
                            <div style="flex:1;min-width:0">
                                <div style="font-size:14px;font-weight:700">{{ $tt['name'] }}</div>
                                <div style="font-size:12px;color:var(--muted);margin-top:2px">{{ $stockLabel }}</div>
                            </div>
                            <div style="font-size:14px;font-weight:800;color:var(--primary);white-space:nowrap">{{ $tt['price'] > 0 ? number_format($tt['price'], 0).' '.$widgetCurrency : 'Free' }}</div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                            <div style="font-size:11.5px;font-weight:700;color:var(--muted)">{{ $windowLabel }}</div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <button type="button" data-widget-dec aria-label="Remove one"
                                        style="width:36px;height:36px;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--text);font-size:16px;font-weight:700;line-height:1">−</button>
                                <span data-widget-count style="min-width:22px;text-align:center;font-size:15px;font-weight:800">0</span>
                                <button type="button" data-widget-inc aria-label="Add one"
                                        style="width:36px;height:36px;border:0;background:var(--primary);border-radius:9px;cursor:pointer;color:#fff;font-size:16px;font-weight:700;line-height:1">+</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="border:1px dashed var(--border);border-radius:14px;padding:18px;text-align:center;color:var(--muted);font-size:13px;margin-bottom:12px">No tickets available yet.</div>
                @endforelse

                @if($hasTypes)
                    <div style="border-top:1px solid var(--border);margin-top:14px;padding-top:12px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;font-weight:600;color:var(--muted)"><span>Subtotal</span><span id="widget-subtotal">Free</span></div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;font-weight:600;color:var(--muted)"><span>Service fee</span><span id="widget-fee">Free</span></div>
                        <div style="border-top:1px solid var(--border);padding-top:8px;display:flex;justify-content:space-between;font-size:17px;font-weight:800"><span>Total</span><span id="widget-total">Free</span></div>
                    </div>
                    <a id="widget-cta" data-base-href="{{ route('bookings.checkout', ['event' => $event->id]) }}" href="{{ route('bookings.checkout', ['event' => $event->id]) }}"
                       style="display:block;width:100%;margin-top:14px;border:0;text-decoration:none;background:var(--disabled);color:#fff;font-weight:800;font-size:15px;padding:15px 0;border-radius:13px;min-height:52px;text-align:center;opacity:.9;cursor:not-allowed;pointer-events:none;box-sizing:border-box"><span id="widget-cta-label">Select tickets to continue</span></a>
                @else
                    <button type="button" disabled style="width:100%;margin-top:16px;border:0;cursor:not-allowed;background:var(--disabled);color:#fff;font-weight:800;font-size:15px;padding:15px;border-radius:13px;min-height:52px;opacity:.9">Tickets coming soon</button>
                @endif
                <p style="margin:10px 0 0;font-size:11.5px;color:var(--muted);text-align:center">Secure payment via Stripe · instant QR ticket</p>
            </div>

            @if($hasTypes)
                {{-- Shared booking helpers (money) — audit FIX-2, loaded only here --}}
                @vite('resources/js/booking.js')

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var rows = document.querySelectorAll('[data-widget-row]');
                        var cta = document.getElementById('widget-cta');
                        var subEl = document.getElementById('widget-subtotal');
                        var feeEl = document.getElementById('widget-fee');
                        var totalEl = document.getElementById('widget-total');
                        var label = document.getElementById('widget-cta-label');
                        var currency = '{{ $widgetCurrency }}';

                        // Shared helper from resources/js/booking.js — the old
                        // inline money() copy was removed (single source of truth).
                        function money(n) {
                            return window.EventlyBooking.money(n, currency);
                        }

                        function refresh() {
                            var subtotal = 0;
                            var any = false;
                            var totalQty = 0;
                            rows.forEach(function (row) {
                                var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                                var price = parseFloat(row.getAttribute('data-price'));
                                if (qty > 0) {
                                    subtotal += price * qty;
                                    any = true;
                                }
                                totalQty += qty;
                                row.style.borderColor = qty > 0 ? 'var(--primary)' : 'var(--border)';
                                row.style.background = qty > 0 ? 'var(--chip)' : 'transparent';
                            });
                            // Fees are reserved for future (spec: fees = 0), so total = subtotal.
                            subEl.textContent = money(subtotal);
                            feeEl.textContent = money(0);
                            totalEl.textContent = money(subtotal);
                            if (cta) {
                                // Carry the selected quantities over to the checkout page.
                                var params = [];
                                rows.forEach(function (row) {
                                    var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                                    if (qty > 0) {
                                        params.push('qty[' + row.getAttribute('data-tt-id') + ']=' + qty);
                                    }
                                });
                                cta.href = cta.getAttribute('data-base-href') + (params.length ? '&' + params.join('&') : '');
                                if (any) {
                                    cta.style.background = 'linear-gradient(135deg,var(--primary),var(--primary-dark))';
                                    cta.style.opacity = '1';
                                    cta.style.pointerEvents = 'auto';
                                    cta.style.cursor = 'pointer';
                                    label.textContent = 'Book ' + totalQty + ' ticket' + (totalQty > 1 ? 's' : '');
                                } else {
                                    cta.style.background = 'var(--disabled)';
                                    cta.style.opacity = '.9';
                                    cta.style.pointerEvents = 'none';
                                    cta.style.cursor = 'not-allowed';
                                    label.textContent = 'Select tickets to continue';
                                }
                            }
                        }

                        rows.forEach(function (row) {
                            row.setAttribute('data-qty', '0');
                            var max = parseInt(row.getAttribute('data-max'), 10) || 0;
                            var countEl = row.querySelector('[data-widget-count]');
                            var inc = row.querySelector('[data-widget-inc]');
                            var dec = row.querySelector('[data-widget-dec]');
                            if (max <= 0) {
                                inc.style.opacity = '.4';
                            }
                            inc.addEventListener('click', function () {
                                var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                                if (qty < max) {
                                    row.setAttribute('data-qty', String(qty + 1));
                                    countEl.textContent = qty + 1;
                                    refresh();
                                }
                            });
                            dec.addEventListener('click', function () {
                                var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                                if (qty > 0) {
                                    row.setAttribute('data-qty', String(qty - 1));
                                    countEl.textContent = qty - 1;
                                    refresh();
                                }
                            });
                        });
                        refresh();
                    });
                </script>
            @endif

            {{-- Tickets sold progress (design rDetail — second aside card).
                 Contract: $sold (int), $capacity (int|null). Card hidden when
                 $sold === 0; text-only (no bar) when $capacity is null. --}}
            @php
                $soldCount = (int) ($sold ?? 0);
                $capCount = isset($capacity) ? (int) $capacity : null;
            @endphp
            @if($soldCount > 0)
                @php
                    $soldLabel = $capCount !== null
                        ? number_format($soldCount).' of '.number_format($capCount).' sold'
                        : number_format($soldCount).' tickets sold';
                    if ($capCount !== null) {
                        $soldPct = min(100, (int) round($soldCount / max(1, $capCount) * 100));
                        $urgency = $soldPct >= 80
                            ? 'Nearly sold out — grab yours'
                            : 'Secure your spot before they\'re gone';
                    }
                @endphp
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;font-size:13px;font-weight:700;margin-bottom:9px">
                        <span>Tickets sold</span><span style="white-space:nowrap">{{ $soldLabel }}</span>
                    </div>
                    @if($capCount !== null)
                        <div style="height:9px;border-radius:99px;background:var(--chip);overflow:hidden">
                            <div style="height:100%;width:{{ $soldPct }}%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--cyan));transition:width .8s ease"></div>
                        </div>
                        <p style="margin:12px 0 0;font-size:12px;color:var(--muted);font-weight:600">{{ $urgency }}</p>
                    @endif
                </div>
            @endif

            {{-- Event details card (real dates/venue) --}}
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:12px">
                    <span>Event details</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:11px">
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Starts</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->starts_at?->format('D, M j, Y · g:i A') }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Ends</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->ends_at?->format('D, M j, Y · g:i A') }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Venue</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->location }}, {{ $event->city }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px">Format</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $event->format?->label() ?? 'In person' }}</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

{{-- Share button: copy the current URL + transient "Link copied" toast --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('share-btn');
        if (!btn) return;

        function showToast(msg) {
            var t = document.createElement('div');
            t.textContent = msg;
            t.setAttribute('role', 'status');
            t.setAttribute('aria-live', 'polite');
            t.style.cssText = 'position:fixed;left:50%;bottom:22px;transform:translateX(-50%);z-index:100;' +
                'background:var(--primary-dark);color:#fff;font-size:13px;font-weight:700;padding:10px 18px;' +
                'border-radius:10px;box-shadow:0 12px 34px rgba(11,37,69,.22);opacity:0;' +
                'transition:opacity .25s ease;pointer-events:none';
            document.body.appendChild(t);
            requestAnimationFrame(function () { t.style.opacity = '1'; });
            setTimeout(function () {
                t.style.opacity = '0';
                setTimeout(function () { t.remove(); }, 300);
            }, 1800);
        }

        function fallbackCopy(url) {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showToast('Link copied');
            } catch (e) {
                // Clipboard unavailable — still show feedback.
                showToast('Link copied');
            }
            ta.remove();
        }

        btn.addEventListener('click', function () {
            var url = window.location.href;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function () {
                    showToast('Link copied');
                }, function () {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        });
    });
</script>
</x-app-layout>
