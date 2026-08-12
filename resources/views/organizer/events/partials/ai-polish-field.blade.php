{{-- ═══════════════════════════════════════════════════════════════════════
    Inline AI polish — "⋮" trigger beside a form field → dropdown of AI ops →
    inline suggestion with Accept / Deny.

    Include once per polishable field inside a `position:relative` wrapper,
    right after the field's <label>:
        <div style="position:relative">
            <label>...field...</label>
            @include('organizer.events.partials.ai-polish-field', ['field' => 'title'])
        </div>

    Self-contained: own styles, own Alpine state, own HTTP/polling. Uses the
    same transform endpoint as the drawer:
      POST ai/event-fields/transform → 202 { generation_id }
      GET  ai/generations/{id}       → { status, result: { content, language } }
    ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $aiPolishRoutes = [
        'transform' => route('organizer.ai.event-fields.transform'),
        'status' => route('organizer.ai.generations.status', ['generationId' => '__GEN__']),
    ];
@endphp

@once
<style>
    [x-cloak] { display: none !important; }
    .aixp { position: absolute; top: 4px; right: 6px; z-index: 40; }
    .aixp-dots { width: 30px; height: 28px; border: 1px solid var(--border); background: var(--surface2); color: var(--muted); border-radius: 8px; cursor: pointer; display: grid; place-items: center; font-size: 15px; font-weight: 800; line-height: 1; }
    .aixp-dots:hover, .aixp-dots[aria-expanded="true"] { color: var(--primary); border-color: var(--primary); }
    .aixp-pop { position: absolute; top: calc(100% + 6px); right: 0; min-width: 210px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 14px 34px rgba(9,30,66,.2); padding: 6px; z-index: 9999; }
    .aixp-item { display: flex; align-items: center; gap: 8px; width: 100%; text-align: left; border: 0; background: none; padding: 9px 11px; border-radius: 8px; font-size: 13px; font-weight: 700; color: var(--text); cursor: pointer; }
    .aixp-item:hover { background: var(--surface2); color: var(--primary); }
    .aixp-sub { margin: 3px 0; border-top: 1px solid var(--border); }
    .aixp-panel { position: absolute; top: calc(100% + 6px); right: 0; width: min(540px, calc(100vw - 60px)); background: var(--surface); border: 1px solid var(--primary); border-radius: 14px; box-shadow: 0 18px 44px rgba(9,30,66,.24); padding: 14px; z-index: 9999; box-sizing: border-box; }
    .aixp-spin { animation: aixp-spin 1s linear infinite; }
    @keyframes aixp-spin { to { transform: rotate(360deg); } }
    .aixp-prev { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; line-height: 1.6; color: var(--text); white-space: pre-wrap; word-break: break-word; max-height: 170px; overflow-y: auto; }
    .aixp-err { margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: var(--err); font-size: 12.5px; font-weight: 600; line-height: 1.5; }
    .aixp-toast { position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); z-index: 200; background: var(--primary-dark); color: #fff; font-size: 13px; font-weight: 700; padding: 10px 18px; border-radius: 10px; box-shadow: 0 10px 28px rgba(9,30,66,.3); }
</style>
@endonce

<div class="aixp" x-data="aiPolishField({ field: '{{ $field }}' })" x-on:click.outside="menuOpen = false; toneOpen = false; translateOpen = false">

    <button type="button" class="aixp-dots" aria-haspopup="menu" x-bind:aria-expanded="menuOpen ? 'true' : 'false'" x-bind:aria-label="'AI polish — ' + fieldLabel()" x-on:click="menuOpen = !menuOpen; toneOpen = false; translateOpen = false">⋮</button>

    {{-- Dropdown menu --}}
    <div x-cloak x-show="menuOpen" class="aixp-pop" role="menu">
        <button type="button" class="aixp-item" role="menuitem" x-on:click="run('rewrite')">Improve</button>
        <button type="button" class="aixp-item" role="menuitem" x-on:click="run('shorten')">Shorten</button>
        <div class="aixp-sub"></div>
        <button type="button" class="aixp-item" role="menuitem" x-on:click="toneOpen = !toneOpen">Change tone ▾</button>
        <template x-if="toneOpen">
            <div>
                <button type="button" class="aixp-item" x-on:click="run('rewrite', 'professional')">Professional</button>
                <button type="button" class="aixp-item" x-on:click="run('rewrite', 'friendly')">Friendly</button>
                <button type="button" class="aixp-item" x-on:click="run('rewrite', 'energetic')">Energetic</button>
            </div>
        </template>
        <button type="button" class="aixp-item" role="menuitem" x-on:click="translateOpen = !translateOpen">Translate ▾</button>
        <template x-if="translateOpen">
            <div>
                <button type="button" class="aixp-item" x-on:click="run('translate', null, 'en')">English</button>
                <button type="button" class="aixp-item" x-on:click="run('translate', null, 'fr')">Français</button>
                <button type="button" class="aixp-item" x-on:click="run('translate', null, 'ar')">العربية</button>
            </div>
        </template>
    </div>

    {{-- Generating state --}}
    <div x-cloak x-show="state === 'generating'" class="aixp-panel" style="display:flex;align-items:center;gap:10px;justify-content:center;padding:18px">
        <svg class="aixp-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        <span style="font-size:13px;font-weight:700;color:var(--text)">Generating a suggestion for your <span x-text="fieldLabel().toLowerCase()"></span>…</span>
    </div>

    {{-- Result with Accept / Deny --}}
    <div x-cloak x-show="state === 'done' || state === 'error'" class="aixp-panel">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
            <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--primary)">AI suggestion — <span x-text="fieldLabel()"></span></span>
            <div style="flex:1"></div>
            <button type="button" x-on:click="reset()" aria-label="Close suggestion" style="border:0;background:none;cursor:pointer;color:var(--muted);font-size:14px;font-weight:800;padding:2px">✕</button>
        </div>
        <div class="aixp-prev" x-bind:dir="language === 'ar' ? 'rtl' : 'ltr'" x-text="content"></div>
        <div x-cloak x-show="error" class="aixp-err" x-text="error" role="alert"></div>
        <div style="display:flex;gap:8px;margin-top:12px">
            <button type="button" x-on:click="reset()" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-weight:700;font-size:12.5px;padding:8px 16px;border-radius:9px;color:var(--text);min-height:36px">Deny</button>
            <div style="flex:1"></div>
            <button type="button" x-on:click="accept()" x-bind:disabled="!content" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:12.5px;padding:8px 18px;border-radius:9px;min-height:36px">Accept</button>
        </div>
    </div>

    <div x-cloak x-show="toastVisible" class="aixp-toast" x-text="toastMsg" role="status"></div>
</div>

@once
<script>
window.aiPolishField = function (opts) {
    return {
        field: opts.field,
        menuOpen: false,
        toneOpen: false,
        translateOpen: false,
        state: 'idle',          // 'idle' | 'generating' | 'done' | 'error'
        content: '',
        language: 'en',
        error: '',
        toastMsg: '',
        toastVisible: false,
        _toastTimer: null,
        _routes: { transform: '{{ $aiPolishRoutes['transform'] }}', status: '{{ $aiPolishRoutes['status'] }}' },

        csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; },
        input() { return document.querySelector('form [name="' + this.field + '"]'); },
        fieldLabel() { return this.field === 'title' ? 'Title' : 'Description'; },

        async post(url, payload) {
            var res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            });
            var body = null;
            try { body = await res.json(); } catch (e) { body = null; }
            return { res: res, body: body };
        },
        async get(url) {
            var res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            var body = null;
            try { body = await res.json(); } catch (e) { body = null; }
            return { res: res, body: body };
        },
        mapError(body, status) {
            var table = {
                ai_feature_disabled: 'AI Event Copilot is disabled.',
                ai_generation_timeout: 'The AI assistant took too long to respond. Please try again.',
                ai_provider_refused: 'The AI assistant is temporarily unavailable.',
                ai_provider_unavailable: 'The AI assistant is temporarily unavailable.',
            };
            if (body && body.error_code && table[body.error_code]) return table[body.error_code];
            if (body && body.message && typeof body.message === 'string') return body.message;
            if (body && body.errors) {
                var first = Object.values(body.errors)[0];
                if (Array.isArray(first) && first.length) return String(first[0]);
                if (typeof first === 'string') return first;
            }
            if (status === 401) return 'Your session has expired. Please refresh the page and try again.';
            if (status === 403) return 'You do not have permission to use the AI copilot.';
            if (status === 429) return 'You are making requests too quickly. Wait a moment and try again.';
            return 'Something went wrong. Please try again.';
        },
        eventContext() {
            var ctx = {}, f = this.input() ? this.input().form : null;
            var read = function (k, max) {
                if (!f || !f.elements[k]) return;
                var v = (f.elements[k].value || '').trim();
                if (v) ctx[k] = max ? v.slice(0, max) : v;
            };
            read('title', 255);
            read('city', 255);
            return ctx;
        },
        async poll(genId) {
            var url = this._routes.status.replace('__GEN__', encodeURIComponent(genId));
            for (var i = 0; i < 60; i++) {
                await new Promise(function (r) { setTimeout(r, 1500); });
                var out;
                try { out = await this.get(url); } catch (e) { continue; }
                if (!out || !out.body || !out.body.data) continue;
                var d = out.body.data;
                if (d.status === 'success') return d.result || {};
                if (d.status === 'error') throw new Error(this.mapError({ error_code: d.error_code, message: d.error_message }, 200));
            }
            throw new Error('The AI assistant took too long to respond. Please try again.');
        },
        async run(op, tone, lang) {
            var input = this.input();
            var content = input ? (input.value || '').trim() : '';
            if (!content) { this.toast('Type a ' + this.fieldLabel().toLowerCase() + ' first'); return; }
            this.menuOpen = false;
            this.toneOpen = false;
            this.translateOpen = false;
            this.state = 'generating';
            this.error = '';
            this.content = '';
            try {
                var payload = { field: this.field, operation: op, content: content, event_context: this.eventContext() };
                if (tone) payload.tone = tone;
                if (lang) payload.target_language = lang;
                var out = await this.post(this._routes.transform, payload);
                if (!out.res.ok) { this.state = 'error'; this.error = this.mapError(out.body, out.res.status); return; }
                var genId = out.body && out.body.data ? out.body.data.generation_id : null;
                if (!genId) { this.state = 'error'; this.error = 'The AI assistant did not respond. Please try again.'; return; }
                var result = await this.poll(genId);
                this.content = result.content || '';
                this.language = result.language || 'en';
                this.state = this.content ? 'done' : 'error';
                if (!this.content) this.error = 'The AI assistant returned an empty result. Please try again.';
            } catch (e) {
                this.state = 'error';
                this.error = e && e.message ? e.message : 'Something went wrong. Please try again.';
            }
        },
        accept() {
            var input = this.input();
            if (input && this.content) {
                input.value = this.content;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            var label = this.fieldLabel();
            this.reset();
            this.toast(label + ' updated by AI');
        },
        reset() {
            this.state = 'idle';
            this.menuOpen = false;
            this.toneOpen = false;
            this.translateOpen = false;
            this.content = '';
            this.error = '';
        },
        toast(msg) {
            this.toastMsg = msg;
            this.toastVisible = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toastVisible = false; }, 2200);
        },
    };
};
</script>
@endonce
