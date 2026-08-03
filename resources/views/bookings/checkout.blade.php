{{-- Checkout — design checkout modal L1227-1264 + booking widget rows L565-582, adapted to a dedicated page.
     Real data: $event (published), $ticketTypes (active, map with availability). --}}
<x-app-layout :activeNav="'events'">
    <main style="max-width:1000px;margin:0 auto;padding:34px 26px 60px">
        <a href="{{ route('events.show', $event->slug) }}" style="font-size:13px;font-weight:700;color:var(--muted);text-decoration:none;display:inline-block;margin-bottom:12px">&larr; Back to event</a>

        <h1 style="font-size:28px;font-weight:800;letter-spacing:-.9px;margin:0 0 6px">Checkout</h1>
        <p style="font-size:14.5px;color:var(--muted);margin:0 0 24px">Complete your booking for <strong>{{ $event->title }}</strong></p>

        @if(session('error'))
            <div style="padding:14px 18px;border-radius:12px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--err);font-size:14px;margin-bottom:20px">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div style="padding:14px 18px;border-radius:12px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--err);font-size:14px;margin-bottom:20px">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('bookings.store') }}" method="POST" id="checkout-form">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            {{-- Selection-derived idempotency key: changes when the selection changes,
                 stays identical on double-submit → server dedupes (REQ-BK-011). --}}
            <input type="hidden" name="idempotency_key" id="idempotency-key" value="">

            <div style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:26px;align-items:start">
                {{-- Ticket selection --}}
                <div>
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px;margin-bottom:20px;box-shadow:var(--shadow)">
                        <h2 style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin:0 0 16px">Select tickets</h2>

                        @php $rowCount = 0; @endphp
                        @forelse($ticketTypes as $tt)
                            @if($tt['is_sales_open'] && $tt['available_quantity'] > 0)
                                @php
                                    $rowCount++;
                                    $maxQty = min($tt['max_per_booking'], $tt['available_quantity']);
                                    $priceLabel = $tt['price'] > 0
                                        ? number_format($tt['price'], 0).' '.$tt['currency']
                                        : 'Free';
                                    $stockLabel = $tt['available_quantity'].' left';
                                @endphp
                                <div data-checkout-row data-tt-id="{{ $tt['id'] }}" data-price="{{ $tt['price'] }}" data-max="{{ $maxQty }}" data-idx="{{ $rowCount - 1 }}"
                                     style="border:1px solid var(--border);border-radius:14px;padding:14px;margin-bottom:12px;transition:border-color .18s,background .18s">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px">
                                        <div style="flex:1;min-width:0">
                                            <div style="font-size:14px;font-weight:700">{{ $tt['name'] }}</div>
                                            @if($tt['description'])
                                                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:3px">{{ $tt['description'] }}</div>
                                            @endif
                                            <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:4px">{{ $stockLabel }}</div>
                                        </div>
                                        <div style="font-size:14px;font-weight:800;color:var(--primary);white-space:nowrap">{{ $priceLabel }}</div>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                                        <div style="font-size:11.5px;font-weight:700;color:var(--muted)">Max {{ $maxQty }} per booking</div>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            <button type="button" data-checkout-dec aria-label="Remove one"
                                                    style="width:36px;height:36px;border:1px solid var(--border);background:var(--surface);border-radius:9px;cursor:pointer;color:var(--text);font-size:16px;font-weight:800;line-height:1">−</button>
                                            <span data-checkout-count style="min-width:22px;text-align:center;font-size:15px;font-weight:800">0</span>
                                            <button type="button" data-checkout-inc aria-label="Add one"
                                                    style="width:36px;height:36px;border:0;background:var(--primary);border-radius:9px;cursor:pointer;color:#fff;font-size:16px;font-weight:800;line-height:1">+</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[{{ $rowCount - 1 }}][ticket_type_id]" value="{{ $tt['id'] }}">
                                    <input type="hidden" name="items[{{ $rowCount - 1 }}][quantity]" data-checkout-qty value="0">
                                </div>
                            @endif
                        @empty
                            <p style="color:var(--muted);font-size:14px">No ticket types available for this event.</p>
                        @endforelse
                    </div>

                    {{-- Payment block (design "Secure checkout" modal L1227-1264, adapted to the page) --}}
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px;box-shadow:var(--shadow)">
                        <h2 style="margin:0 0 4px;font-size:20px;font-weight:800;letter-spacing:-.5px">Secure checkout</h2>
                        <p style="margin:0 0 16px;font-size:13px;color:var(--muted)">Demo checkout &mdash; use card 4242 4242 4242 4242</p>

                        {{-- Event summary row --}}
                        <div style="background:var(--surface2);border:1px solid var(--border);border-radius:14px;padding:14px;display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px">
                            <span style="font-size:14px;font-weight:700;min-width:0">{{ $event->title }}</span>
                            <span data-payment-total style="font-size:14px;font-weight:800;color:var(--primary);white-space:nowrap">Free</span>
                        </div>

                        <div id="payment-fields" style="display:flex;flex-direction:column;gap:13px">
                            {{-- Card number --}}
                            <label style="display:flex;flex-direction:column;gap:7px">
                                <span style="font-size:12.5px;font-weight:700">Card number</span>
                                <input type="text" name="payment[card_number]" inputmode="numeric" autocomplete="cc-number"
                                       placeholder="4242 4242 4242 4242" value="{{ old('payment.card_number') }}"
                                       style="min-height:46px;padding:12px 14px;border:1px solid {{ $errors->has('payment.card_number') ? 'var(--err)' : 'var(--border)' }};background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                            </label>
                            @error('payment.card_number')
                                <div style="padding:9px 12px;border-radius:10px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--err);font-size:12.5px">{{ $message }}</div>
                            @enderror

                            {{-- Expiry + CVC --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                <label style="display:flex;flex-direction:column;gap:7px">
                                    <span style="font-size:12.5px;font-weight:700">Expiry</span>
                                    <input type="text" name="payment[expiry]" placeholder="12 / 28" autocomplete="cc-exp"
                                           value="{{ old('payment.expiry') }}"
                                           style="min-height:46px;padding:12px 14px;border:1px solid {{ $errors->has('payment.expiry') ? 'var(--err)' : 'var(--border)' }};background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                                </label>
                                <label style="display:flex;flex-direction:column;gap:7px">
                                    <span style="font-size:12.5px;font-weight:700">CVC</span>
                                    <input type="text" name="payment[cvc]" placeholder="123" autocomplete="cc-csc"
                                           value="{{ old('payment.cvc') }}"
                                           style="min-height:46px;padding:12px 14px;border:1px solid {{ $errors->has('payment.cvc') ? 'var(--err)' : 'var(--border)' }};background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                                </label>
                            </div>
                            @error('payment.expiry')
                                <div style="padding:9px 12px;border-radius:10px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--err);font-size:12.5px">{{ $message }}</div>
                            @enderror
                            @error('payment.cvc')
                                <div style="padding:9px 12px;border-radius:10px;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--err);font-size:12.5px">{{ $message }}</div>
                            @enderror

                            {{-- Client-side processing state (revealed on submit, hides the fields) --}}
                            <div id="payment-processing" style="display:none;padding:20px 0;flex-direction:column;align-items:center;gap:12px">
                                <div style="width:38px;height:38px;border-radius:50%;border:4px solid var(--chip);border-top-color:var(--primary);animation:spin .8s linear infinite"></div>
                                <div style="font-size:14.5px;font-weight:700">Confirming payment with Stripe&hellip;</div>
                            </div>
                        </div>

                        {{-- Free-event note (replaces the card fields when the total is 0) --}}
                        <div id="free-note" style="display:none;border:1px dashed var(--border);border-radius:14px;padding:16px;text-align:center;color:var(--muted);font-size:13px">Free event &mdash; no payment needed</div>
                    </div>
                </div>

                {{-- Order summary (design checkout summary) --}}
                <div style="position:sticky;top:82px">
                    <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:var(--shadow)">
                        <h3 style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin:0 0 14px">Order Summary</h3>
                        <div style="font-size:14px;font-weight:700;margin-bottom:6px">{{ $event->title }}</div>
                        <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:14px">{{ $event->starts_at->format('M d, Y · H:i') }}</div>
                        <div style="display:flex;flex-direction:column;gap:8px;border-top:1px solid var(--border);padding-top:14px">
                            <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:var(--muted)">
                                <span>Subtotal</span><span data-summary-subtotal>Free</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:var(--muted)">
                                <span>Service fee</span><span data-summary-fee>Free</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;letter-spacing:-.4px;padding-top:6px;border-top:1px solid var(--border)">
                                <span>Total</span><span data-summary-total>Free</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="checkout-submit" disabled
                            style="width:100%;min-height:52px;margin-top:14px;border:0;border-radius:13px;background:var(--disabled);color:#fff;font-size:15px;font-weight:800;padding:15px;cursor:not-allowed;opacity:.9">Select tickets</button>
                    <p style="font-size:11.5px;color:var(--muted);text-align:center;margin-top:10px">Secure payment via Stripe &middot; instant QR ticket</p>
                </div>
            </div>
        </form>
    </main>

    {{-- Shared booking helpers (money/selectionKey) — audit FIX-2, loaded only here --}}
    @vite('resources/js/booking.js')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var rows = document.querySelectorAll('[data-checkout-row]');
            var submit = document.getElementById('checkout-submit');
            var subEl = document.querySelector('[data-summary-subtotal]');
            var feeEl = document.querySelector('[data-summary-fee]');
            var totalEl = document.querySelector('[data-summary-total]');
            var form = document.getElementById('checkout-form');
            var keyInput = document.getElementById('idempotency-key');
            var eventId = {{ $event->id }};
            var initialQty = @json($initialQty ?? []);

            // Shared helpers live in resources/js/booking.js (single source of
            // truth) — the old inline copies of selectionKey()/money() are gone.
            function selectionKey(parts) {
                return window.EventlyBooking.selectionKey(eventId, parts);
            }

            function money(n) {
                return window.EventlyBooking.money(n);
            }

            function refresh() {
                var subtotal = 0;
                var any = false;
                var parts = [];
                rows.forEach(function (row) {
                    var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                    var price = parseFloat(row.getAttribute('data-price'));
                    if (qty > 0) {
                        subtotal += price * qty;
                        any = true;
                        parts.push(row.getAttribute('data-tt-id') + ':' + qty);
                    }
                    row.style.borderColor = qty > 0 ? 'var(--primary)' : 'var(--border)';
                    row.style.background = qty > 0 ? 'var(--chip)' : 'transparent';
                });
                // Recompute the idempotency key: same selection → same key,
                // changed selection → new key.
                keyInput.value = any ? selectionKey(parts) : '';
                // Fees are reserved for future (spec: fees = 0), so total = subtotal.
                subEl.textContent = money(subtotal);
                feeEl.textContent = money(0);
                totalEl.textContent = money(subtotal);
                // Payment block: show the card fields only for paid orders; a free
                // order hides (and disables) them so nothing card-related posts.
                var needsPayment = subtotal > 0;
                var payFields = document.getElementById('payment-fields');
                var freeNote = document.getElementById('free-note');
                var payTotal = document.querySelector('[data-payment-total]');
                if (payTotal) payTotal.textContent = money(subtotal);
                if (payFields) {
                    payFields.style.display = needsPayment ? 'flex' : 'none';
                    payFields.querySelectorAll('input').forEach(function (i) { i.disabled = !needsPayment; });
                }
                if (freeNote) freeNote.style.display = (any && !needsPayment) ? 'block' : 'none';
                if (any) {
                    submit.disabled = false;
                    submit.style.background = 'linear-gradient(135deg,var(--primary),var(--primary-dark))';
                    submit.style.opacity = '1';
                    submit.style.cursor = 'pointer';
                    submit.textContent = needsPayment ? 'Pay ' + money(subtotal) : 'Complete booking \u2014 Free';
                } else {
                    submit.disabled = true;
                    submit.style.background = 'var(--disabled)';
                    submit.style.opacity = '.9';
                    submit.style.cursor = 'not-allowed';
                    submit.textContent = 'Select tickets';
                }
            }

            rows.forEach(function (row) {
                row.setAttribute('data-qty', '0');
                var max = parseInt(row.getAttribute('data-max'), 10) || 0;
                var countEl = row.querySelector('[data-checkout-count]');
                var qtyInput = row.querySelector('[data-checkout-qty]');
                var inc = row.querySelector('[data-checkout-inc]');
                var dec = row.querySelector('[data-checkout-dec]');
                if (max <= 0) {
                    inc.style.opacity = '.4';
                }
                inc.addEventListener('click', function () {
                    var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                    if (qty < max) {
                        qty++;
                        row.setAttribute('data-qty', String(qty));
                        countEl.textContent = qty;
                        qtyInput.value = qty;
                        refresh();
                    }
                });
                dec.addEventListener('click', function () {
                    var qty = parseInt(row.getAttribute('data-qty'), 10) || 0;
                    if (qty > 0) {
                        qty--;
                        row.setAttribute('data-qty', String(qty));
                        countEl.textContent = qty;
                        qtyInput.value = qty;
                        refresh();
                    }
                });
                // Pre-fill quantities preselected on the event page (clamped to the max).
                var pre = parseInt(initialQty[row.getAttribute('data-tt-id')], 10) || 0;
                if (pre > 0) {
                    pre = Math.min(pre, max);
                    row.setAttribute('data-qty', String(pre));
                    countEl.textContent = pre;
                    qtyInput.value = pre;
                }
            });
            refresh();

            // While the POST is in flight: disable the CTA and swap the card
            // fields for the design's "Confirming payment with Stripe…" spinner.
            form.addEventListener('submit', function () {
                if (submit.disabled) return;
                submit.disabled = true;
                submit.style.opacity = '.85';
                submit.style.cursor = 'wait';
                var payFields = document.getElementById('payment-fields');
                var freeNote = document.getElementById('free-note');
                var processing = document.getElementById('payment-processing');
                if (payFields) payFields.style.display = 'none';
                if (freeNote) freeNote.style.display = 'none';
                if (processing) processing.style.display = 'flex';
            });
        });
    </script>
</x-app-layout>
