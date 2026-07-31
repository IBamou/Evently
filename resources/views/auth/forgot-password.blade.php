{{-- Forgot password — pixel-port of design rAuth (lines 601–658), forgot variant.
     Rendered inside <x-app-layout> (design shows the full shell header on auth routes). --}}

<x-app-layout :activeRole="'guest'" :navRole="'guest'" :avatarRole="'guest'" :activeNav="'forgot'">

<div style="min-height:calc(100vh - 66px);display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr)">

    {{-- Left column: form --}}
    <div style="display:grid;place-items:center;padding:48px 26px">
        <div style="width:100%;max-width:400px">
            {{-- Logo --}}
            <a href="/preview/events" style="display:flex;align-items:center;gap:9px;background:none;text-decoration:none;margin-bottom:24px">
                <div style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--cyan));display:grid;place-items:center;color:#fff;font-weight:800;font-size:15px">E</div>
                <span style="font-weight:800;font-size:20px;letter-spacing:-.5px;color:var(--primary)">Evently</span>
            </a>

            <h1 style="margin:0 0 8px;font-size:30px;font-weight:800;letter-spacing:-1px">Reset your password</h1>
            <p style="margin:0 0 26px;color:var(--muted);font-size:14.5px">We'll email you a secure reset link.</p>

            @if (session('status'))
                <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:rgba(22,163,74,.12);color:var(--ok);font-size:13px;font-weight:600">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px">
                        <span style="font-size:12.5px;font-weight:700">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus
                               class="needs-focus"
                               style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface);border-radius:12px;font-size:14.5px;outline:none">
                        @error('email')<span style="font-size:12px;color:var(--err)">{{ $message }}</span>@enderror
                    </label>

                    <button type="submit" style="margin-top:4px;border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:15px;padding:15px;border-radius:13px;min-height:52px">Send reset link</button>

                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:4px">
                        <a href="{{ route('login') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--primary);padding:6px 0;text-decoration:none">&larr; Back to sign in</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Right panel: decorative gradient --}}
    <div style="position:relative;overflow:hidden;background:linear-gradient(160deg,var(--primary-dark),var(--primary) 50%,var(--cyan))">
        <div style="position:absolute;top:14%;left:12%;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.28),transparent 70%);animation:glow 8s ease-in-out infinite"></div>
        <div style="position:relative;padding:70px 52px;color:#fff">
            <div style="font-size:11px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;opacity:.8;margin-bottom:16px">Evently platform</div>
            <h2 style="margin:0 0 18px;font-size:34px;font-weight:800;letter-spacing:-1.2px;line-height:1.15;max-width:20ch">Every ticket, every attendee, one calm dashboard.</h2>
            <p style="margin:0 0 32px;font-size:15px;line-height:1.7;opacity:.85;max-width:36ch">Publish events, sell tickets in MAD, scan QR codes at the door and watch revenue in real time.</p>
            <div style="display:flex;flex-direction:column;gap:12px">
                @foreach (['Approval workflow for every event', 'QR check-in at the door', 'Stripe payouts in MAD', 'AI copilot for event copy'] as $perk)
                    <div style="display:flex;align-items:center;gap:11px;font-size:14px;font-weight:600">
                        <span style="width:24px;height:24px;flex:0 0 auto;border-radius:50%;background:rgba(255,255,255,.2);display:grid;place-items:center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                        </span>
                        {{ $perk }}
                    </div>
                @endforeach
            </div>
        </div>
        <div style="position:absolute;bottom:0;left:0;width:200%;height:110px;opacity:.32;animation:wave 17s linear infinite">
            <svg width="100%" height="100%" viewBox="0 0 2400 110" preserveAspectRatio="none"><path d="M0 46c200 34 400-34 600 0s400 34 600 0 400-34 600 0 400 34 600 0v64H0z" fill="#fff"></path></svg>
        </div>
    </div>
</div>

</x-app-layout>
