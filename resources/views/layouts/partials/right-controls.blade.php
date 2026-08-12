{{-- Shared header right controls: theme toggle, tickets bell, account menu. --}}
<div style="display:flex;align-items:center;gap:8px">

    {{-- Theme toggle --}}
    <button type="button" @click="toggle()" aria-label="Toggle dark mode"
            style="width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface);border-radius:11px;cursor:pointer;color:var(--muted)">
        {{-- Sun icon (shown in dark mode) --}}
        <svg x-show="dark" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
            <circle cx="12" cy="12" r="5"></circle>
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
        </svg>
        {{-- Moon icon (shown in light mode) --}}
        <svg x-show="!dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>

    @if(auth()->user()?->role === \App\Enums\UserRole::User)
        <a href="{{ route('tickets.index') }}" aria-label="My tickets" style="position:relative;width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface);border-radius:11px;cursor:pointer;color:var(--muted);text-decoration:none">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-6z"></path>
            </svg>
            @if(session('cart_count', 0) > 0)
                <span style="position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 4px;border-radius:9px;background:var(--primary);color:#fff;font-size:10px;font-weight:800;display:grid;place-items:center">{{ session('cart_count') }}</span>
            @endif
        </a>
    @endif

    {{-- Avatar: authenticated users get an Alpine dropdown with sign-out.
         NOTE: state lives on the root x-data (accountMenuOpen) — nested x-data scopes don't react here. --}}
    @auth
        <div @click.outside="accountMenuOpen = false" style="position:relative">
            <button type="button"
                    @click="accountMenuOpen = !accountMenuOpen"
                    @keydown.escape.window="accountMenuOpen = false"
                    aria-haspopup="true"
                    :aria-expanded="accountMenuOpen ? 'true' : 'false'"
                    aria-label="Account menu"
                    style="width:40px;height:40px;border:0;border-radius:50%;background:{{ $avatar['grad'] }};color:#fff;display:grid;place-items:center;font-weight:700;font-size:14px;cursor:pointer;padding:0">{{ $avatarInitials }}</button>

            <div x-show="accountMenuOpen" x-cloak
                 style="position:absolute;top:calc(100% + 10px);right:0;width:260px;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 12px 32px rgba(9,30,66,.14);padding:8px;z-index:9999">
                <div style="padding:10px 12px;border-bottom:1px solid var(--border)">
                    <div style="font-size:14px;font-weight:800;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ auth()->user()->name }}</div>
                    <div style="font-size:12.5px;color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" style="width:100%;border:0;background:none;cursor:pointer;text-align:left;padding:10px 12px;border-radius:8px;font-size:13.5px;font-weight:700;color:var(--err)">Sign out</button>
                </form>
            </div>
        </div>
    @endauth
</div>
