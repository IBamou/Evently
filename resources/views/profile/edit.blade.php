{{-- Profile — pixel-port of design rProfile (lines 786–817).
     Accepts $pageTitle prop (default 'My profile') for role reuse by big-pickle. --}}

<x-app-layout :activeRole="'user'" :navRole="'user'" :avatarRole="'user'">
@props(['pageTitle' => 'My profile'])

@php
    $userName = 'Yassine Benali';
    $userEmail = 'yassine@example.com';
    $initial = 'YB';
    $avatarBg = 'linear-gradient(135deg,#0EA5E9,#1565D8)';
    $roleLabel = 'User';
    $roleBadgeBg = 'var(--chip)';
    $roleBadgeFg = 'var(--primary)';
@endphp

<div style="max-width:820px;margin:0 auto;padding:34px 26px 60px">
    <h1 style="margin:0 0 22px;font-size:28px;font-weight:800;letter-spacing:-.9px">{{ $pageTitle }}</h1>

    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Card 1: Profile information --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px">
                <div style="width:64px;height:64px;border-radius:50%;background:{{ $avatarBg }};color:#fff;display:grid;place-items:center;font-weight:800;font-size:24px">{{ $initial }}</div>
                <div>
                    <div style="font-size:18px;font-weight:800">{{ $userName }}</div>
                    <div style="font-size:13px;color:var(--muted);font-weight:600">{{ $userEmail }}</div>
                </div>
                <div style="flex:1"></div>
                <span style="padding:7px 13px;border-radius:9px;background:{{ $roleBadgeBg }};color:{{ $roleBadgeFg }};font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.6px">{{ $roleLabel }}</span>
            </div>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px">
                        <span style="font-size:12.5px;font-weight:700">Full name</span>
                        <input type="text" name="name" value="{{ old('name', $userName) }}"
                               class="needs-focus"
                               style="min-height:46px;padding:12px 14px;border:1px solid var(--border);background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:7px">
                        <span style="font-size:12.5px;font-weight:700">Email</span>
                        <input type="email" name="email" value="{{ old('email', $userEmail) }}"
                               class="needs-focus"
                               style="min-height:46px;padding:12px 14px;border:1px solid var(--border);background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                    </label>
                </div>
                <button type="submit" style="margin-top:18px;border:0;cursor:pointer;background:var(--primary);color:#fff;font-weight:700;font-size:14px;padding:13px 22px;border-radius:12px;min-height:46px">Save changes</button>
            </form>
        </div>

        {{-- Card 2: Change password --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px">
            <h2 style="margin:0 0 16px;font-size:16px;font-weight:800">Change password</h2>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px">
                    <label style="display:flex;flex-direction:column;gap:7px">
                        <span style="font-size:12.5px;font-weight:700">Current password</span>
                        <input type="password" name="current_password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="needs-focus"
                               style="min-height:46px;padding:12px 14px;border:1px solid var(--border);background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:7px">
                        <span style="font-size:12.5px;font-weight:700">New password</span>
                        <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="needs-focus"
                               style="min-height:46px;padding:12px 14px;border:1px solid var(--border);background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:7px">
                        <span style="font-size:12.5px;font-weight:700">Confirm new password</span>
                        <input type="password" name="password_confirmation" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="needs-focus"
                               style="min-height:46px;padding:12px 14px;border:1px solid var(--border);background:var(--surface2);border-radius:11px;font-size:14px;outline:none">
                    </label>
                </div>
                <button type="submit" style="margin-top:18px;border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-weight:700;font-size:14px;padding:13px 22px;border-radius:12px;min-height:46px">Update password</button>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
