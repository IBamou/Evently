<x-app-layout :activeNav="'scan'">
    <main style="max-width:1100px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('organizer.check-in.picker') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Change event</a>
        <h1 style="font-size:28px;font-weight:800;letter-spacing:-.9px;margin:0 0 6px">Door check-in</h1>
        <p style="font-size:14.5px;color:var(--muted);margin:0 0 24px">Scan attendee QR codes or type a ticket code manually. — {{ $event->title }}</p>

        <div style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start">
            {{-- Scanner + manual input --}}
            <div>
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px;margin-bottom:20px">
                    {{-- Design viewfinder (rScan L1077-1089): live camera replaces the static QR art --}}
                    <div style="position:relative;background:radial-gradient(circle at 50% 40%,#123B66,#071426);border-radius:15px;aspect-ratio:4/3;overflow:hidden;margin-bottom:16px">
                        <div id="qr-scanner" style="position:absolute;inset:0"></div>
                        <div id="scanner-art" style="position:absolute;inset:0;display:grid;place-items:center;z-index:0;pointer-events:none">
                            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="rgba(155,211,242,.28)" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="7" y1="7" x2="17" y2="7"/><line x1="7" y1="11" x2="17" y2="11"/><line x1="7" y1="15" x2="13" y2="15"/></svg>
                        </div>
                        <div style="position:absolute;inset:14%;border:2px solid rgba(255,255,255,.35);border-radius:12px;pointer-events:none;z-index:1"></div>
                        <div style="position:absolute;left:14%;right:14%;top:16%;height:2px;background:linear-gradient(90deg,transparent,var(--cyan),transparent);box-shadow:0 0 14px var(--cyan);animation:scanline 2.4s ease-in-out infinite;pointer-events:none;z-index:1"></div>
                        <div style="position:absolute;left:0;right:0;bottom:14px;text-align:center;color:rgba(255,255,255,.8);font-size:12.5px;font-weight:700;pointer-events:none;z-index:1">Camera active · hold the QR steady</div>
                    </div>

                    <div id="cam-fallback" style="display:none;padding:10px 14px;border-radius:10px;background:rgba(217,119,6,.1);border:1px solid rgba(217,119,6,.25);color:var(--warn);font-size:12.5px;margin-bottom:12px">Camera unavailable — type the reference below.</div>

                    {{-- Manual entry --}}
                    <form action="{{ route('organizer.check-in.scan', $event) }}" method="POST" id="manual-form" style="display:flex;gap:10px">
                        @csrf
                        <input type="text" name="code" id="manual-code" placeholder="T-XXXXXXXXXX" required autofocus
                               style="flex:1;min-height:48px;padding:0 15px;border:1px solid var(--border);border-radius:12px;font-size:14.5px;background:var(--surface2)">
                        <button type="submit" style="padding:0 24px;height:48px;border:0;border-radius:12px;background:var(--primary);color:#fff;font-size:13px;font-weight:800;cursor:pointer;white-space:nowrap">Check in</button>
                    </form>
                </div>

                {{-- Inline scan result (fetch flow) — hidden until the first scan --}}
                <div id="scan-result" style="display:none"></div>

                {{-- Result flash (no-JS fallback: normal POST â†’ redirect with flash) --}}
                @if(session('checkin_success'))
                    @php $res = session('checkin_success'); @endphp
                    <div style="border:1px solid var(--ok);background:rgba(22,163,74,.06);border-radius:14px;padding:16px;display:flex;gap:14px;align-items:center;margin-bottom:20px">
                        <div style="width:38px;height:38px;border-radius:50%;background:rgba(22,163,74,.12);display:grid;place-items:center;color:var(--ok);font-size:18px">&#10003;</div>
                        <div>
                            <div style="font-size:15px;font-weight:800">{{ $res['message'] }}</div>
                            <div style="font-size:12.5px;color:var(--muted)">{{ $res['ticket']['type'] ?? '' }} &middot; {{ $res['ticket']['holder_name'] ?? '' }}</div>
                        </div>
                    </div>
                @endif

                @if($errors->has('code'))
                    <div style="border:1px solid var(--err);background:rgba(220,38,38,.06);border-radius:14px;padding:16px;display:flex;gap:14px;align-items:center;margin-bottom:20px">
                        <div style="width:38px;height:38px;border-radius:50%;background:rgba(220,38,38,.12);display:grid;place-items:center;color:var(--err);font-size:18px">&#10007;</div>
                        <div>
                            <div style="font-size:15px;font-weight:800">{{ $errors->first('code') }}</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Stats aside --}}
            <div style="display:flex;flex-direction:column;gap:16px">
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                    <h3 style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin:0 0 14px">Tonight at the door</h3>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <div style="display:flex;align-items:baseline;gap:10px">
                            <span id="stat-checked-in" style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $stats['checked_in'] }}</span>
                            <span style="font-size:12.5px;font-weight:700;color:var(--muted)">Checked in</span>
                        </div>
                        <div style="display:flex;align-items:baseline;gap:10px">
                            <span id="stat-issued" style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $stats['issued'] }}</span>
                            <span style="font-size:12.5px;font-weight:700;color:var(--muted)">Issued</span>
                        </div>
                        <div style="display:flex;align-items:baseline;gap:10px">
                            <span id="stat-remaining" style="font-size:22px;font-weight:800;letter-spacing:-.7px;min-width:56px">{{ $stats['remaining'] }}</span>
                            <span style="font-size:12.5px;font-weight:700;color:var(--muted)">Remaining</span>
                        </div>
                    </div>
                    @if($stats['issued'] > 0)
                        <div style="margin-top:16px;height:8px;border-radius:99px;background:var(--chip);overflow:hidden">
                            <div id="stat-progress" style="height:100%;width:{{ ($stats['checked_in'] / $stats['issued']) * 100 }}%;background:linear-gradient(135deg,var(--primary),var(--cyan));border-radius:99px"></div>
                        </div>
                    @endif
                </div>

                <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
                    <h3 style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin:0 0 14px">Recent scans</h3>
                    <div id="recent-scans" style="display:flex;flex-direction:column;gap:10px">
                        @forelse($recentScans as $scan)
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:8px;height:8px;border-radius:50%;background:var(--ok);flex-shrink:0"></div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $scan->ticketType?->name ?? 'Ticket' }}</div>
                                </div>
                                <div style="font-size:11.5px;color:var(--muted);white-space:nowrap">{{ $scan->checked_in_at?->diffForHumans() ?? '' }}</div>
                            </div>
                        @empty
                            <div id="recent-scans-empty" style="font-size:13px;color:var(--muted)">No scans yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Load the QR module only on this page (html5-qrcode itself is lazy-loaded by qr.js) --}}
    @vite('resources/js/qr.js')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('manual-form');
            var input = document.getElementById('manual-code');
            var submitBtn = form ? form.querySelector('button[type="submit"]') : null;
            var resultEl = document.getElementById('scan-result');
            var csrfToken = form ? form.querySelector('input[name="_token"]') : null;
            var url = '{{ route('organizer.check-in.scan', $event) }}';
            var scannerHandle = null;

            function escapeHtml(str) {
                return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            // Result type â†’ banner tone: checked_in is green, already_used is a
            // soft warning (ticket simply scanned before), the rest are errors.
            function bannerStyles(kind) {
                if (kind === 'success') {
                    return {
                        border: '1px solid var(--ok)', bg: 'rgba(22,163,74,.06)',
                        iconBg: 'rgba(22,163,74,.12)', color: 'var(--ok)', icon: '&#10003;',
                    };
                }
                var warn = kind === 'warn';
                return {
                    border: warn ? '1px solid var(--warn)' : '1px solid var(--err)',
                    bg: warn ? 'rgba(217,119,6,.08)' : 'rgba(220,38,38,.06)',
                    iconBg: warn ? 'rgba(217,119,6,.14)' : 'rgba(220,38,38,.12)',
                    color: warn ? 'var(--warn)' : 'var(--err)',
                    icon: '&#10007;',
                };
            }

            function renderBanner(data, kind) {
                if (!resultEl) return;
                var s = bannerStyles(kind);
                var detail = '';
                if (kind === 'success' && data.ticket) {
                    var t = data.ticket;
                    var time = t.checked_in_at
                        ? new Date(t.checked_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                        : '';
                    detail = '<div style="font-size:12.5px;color:var(--muted);margin-top:2px">'
                        + escapeHtml(t.type || 'Ticket') + ' &middot; ' + escapeHtml(t.holder_name || 'Guest')
                        + (time ? ' &middot; ' + time : '') + '</div>';
                }
                resultEl.innerHTML =
                    '<div style="border:' + s.border + ';background:' + s.bg + ';border-radius:14px;padding:16px;display:flex;gap:14px;align-items:center;margin-bottom:20px">'
                    + '<div style="width:38px;height:38px;border-radius:50%;background:' + s.iconBg + ';display:grid;place-items:center;color:' + s.color + ';font-size:18px;flex-shrink:0">' + s.icon + '</div>'
                    + '<div style="flex:1;min-width:0">'
                    + '<div style="font-size:15px;font-weight:800;color:' + s.color + '">' + escapeHtml(data.message || '') + '</div>'
                    + detail + '</div>'
                    + '<button type="button" data-dismiss-scan aria-label="Dismiss" style="border:0;background:none;color:var(--muted);font-size:20px;cursor:pointer;padding:4px;flex-shrink:0">&times;</button>'
                    + '</div>';
                resultEl.style.display = 'block';
                var dismiss = resultEl.querySelector('[data-dismiss-scan]');
                if (dismiss) {
                    dismiss.addEventListener('click', function () { resultEl.style.display = 'none'; });
                }
            }

            function applyStats(stats) {
                if (!stats) return;
                var ci = document.getElementById('stat-checked-in');
                var is = document.getElementById('stat-issued');
                var rm = document.getElementById('stat-remaining');
                var bar = document.getElementById('stat-progress');
                if (ci) ci.textContent = stats.checked_in;
                if (is) is.textContent = stats.issued;
                if (rm) rm.textContent = stats.remaining;
                if (bar && stats.issued > 0) {
                    bar.style.width = ((stats.checked_in / stats.issued) * 100) + '%';
                }
            }

            function prependScan(ticket) {
                var list = document.getElementById('recent-scans');
                if (!list || !ticket) return;
                var empty = document.getElementById('recent-scans-empty');
                if (empty) empty.remove();
                var time = ticket.checked_in_at
                    ? new Date(ticket.checked_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : '';
                var row = document.createElement('div');
                row.style.cssText = 'display:flex;align-items:center;gap:10px';
                row.innerHTML =
                    '<div style="width:8px;height:8px;border-radius:50%;background:var(--ok);flex-shrink:0"></div>'
                    + '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(ticket.type || 'Ticket') + '</div></div>'
                    + '<div style="font-size:11.5px;color:var(--muted);white-space:nowrap">' + escapeHtml(time) + '</div>';
                list.prepend(row);
            }

            function checkIn(code) {
                var body = new FormData();
                body.append('code', code);
                if (csrfToken) body.append('_token', csrfToken.value);
                if (submitBtn) submitBtn.disabled = true;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.value : '',
                    },
                    body: body,
                })
                    .then(function (res) {
                        return res.json().catch(function () { return null; }).then(function (data) {
                            return { ok: res.ok, status: res.status, data: data };
                        });
                    })
                    .then(function (r) {
                        if (r.data && r.data.result === 'checked_in') {
                            renderBanner(r.data, 'success');
                            applyStats(r.data.stats);
                            prependScan(r.data.ticket);
                        } else if (r.data) {
                            var kind = r.data.result === 'already_used' ? 'warn' : 'error';
                            renderBanner(r.data, kind);
                        } else {
                            renderBanner({ result: 'error', message: 'Unexpected server response (' + r.status + ').' }, 'error');
                        }
                    })
                    .catch(function () {
                        renderBanner({ result: 'error', message: 'Network error — check your connection and try again.' }, 'error');
                    })
                    .finally(function () {
                        if (submitBtn) submitBtn.disabled = false;
                        if (input) { input.value = ''; input.focus(); }
                    });
            }

            // Manual entry â†’ fetch (progressive enhancement: without JS the form
            // still POSTs normally and redirects back with a flash).
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var code = input ? input.value.trim() : '';
                    if (code) checkIn(code);
                });
            }

            // Camera scan â†’ same fetch flow (no more form.submit()).
            if (window.EventlyQr) {
                window.EventlyQr.initCameraScanner({
                    elementId: 'qr-scanner',
                    fps: 15,
                    qrbox: 200,
                    onSuccess: function (code) {
                        checkIn(String(code).trim());
                    },
                    onError: function (err) {
                        var fb = document.getElementById('cam-fallback');
                        if (fb) fb.style.display = 'block';
                    },
                    onScanError: function () {
                        // Per-frame decode error — scanner is active but no QR found yet.
                        // This is expected noise; no action needed.
                    },
                }).then(function (handle) {
                    scannerHandle = handle;
                    var art = document.getElementById('scanner-art');
                    if (art) art.style.display = 'none';
                }).catch(function () {
                    // Lazy chunk failed to load — the camera never started. Show the
                    // muted fallback in the viewfinder too (cam-fallback is shown via onError).
                    window.EventlyQr.showQrFallback(document.getElementById('qr-scanner'));
                });

                // Stop the camera cleanly if the page is ever torn down while the
                // scanner is running (e.g. SPA-ish navigation).
                window.addEventListener('pagehide', function () {
                    if (scannerHandle && typeof scannerHandle.dispose === 'function') {
                        scannerHandle.dispose();
                    }
                });
            }
        });
    </script>
</x-app-layout>
