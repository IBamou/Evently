@php
    $aiRoutes = [
        'draft' => route('organizer.ai.event-drafts'),
        'status' => route('organizer.ai.generations.status', ['generationId' => '__GEN__']),
    ];
@endphp

<x-app-layout :activeNav="'oevents'">
<script>window.AIX_ROUTES = @json($aiRoutes);</script>

<style>
    @keyframes aixSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .aix-spin { animation: aixSpin 1s linear infinite; }
    .aix-toast { position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); z-index: 190; background: var(--primary-dark); color: #fff; font-size: 13px; font-weight: 700; padding: 10px 18px; border-radius: 10px; box-shadow: 0 10px 28px rgba(9,30,66,.3); }
    .aix-page { width: 100%; max-width: 1080px; margin: 0 auto; padding: 26px 24px 56px; }
    .aix-back { display: inline-flex; min-height: 40px; align-items: center; gap: 7px; margin-bottom: 12px; padding: 0 2px; color: var(--muted); font-size: 13px; font-weight: 800; text-decoration: none; }
    .aix-back:hover { color: var(--primary); }
    .aix-header { display: flex; align-items: center; gap: 13px; margin-bottom: 22px; }
    .aix-title-icon { display: grid; width: 40px; height: 40px; flex: 0 0 auto; place-items: center; border-radius: 13px; background: linear-gradient(135deg,var(--primary),var(--cyan)); color: #fff; box-shadow: 0 7px 18px rgba(21,101,216,.2); }
    .aix-kicker { margin-bottom: 3px; color: var(--primary); font-size: 10.5px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .aix-title { margin: 0; color: var(--text); font-size: 26px; font-weight: 800; letter-spacing: -.55px; }
    .aix-subtitle { margin: 5px 0 0; color: var(--muted); font-size: 13.5px; font-weight: 600; line-height: 1.5; }
    .aix-section { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: 0 1px 2px rgba(11,37,69,.04); }
    .aix-card-heading { display: flex; align-items: flex-start; gap: 11px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
    .aix-card-number { display: grid; width: 30px; height: 30px; flex: 0 0 auto; place-items: center; border-radius: 9px; background: var(--chip); color: var(--primary); font-size: 11px; font-weight: 800; }
    .aix-card-title { margin: 0; color: var(--text); font-size: 15px; font-weight: 800; }
    .aix-card-copy { margin: 3px 0 0; color: var(--muted); font-size: 11.5px; font-weight: 600; line-height: 1.45; }
    .aix-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); }
    .aix-input { width: 100%; min-height: 44px; padding: 11px 13px; border: 1px solid var(--border); background: var(--surface2); border-radius: 11px; font-size: 13.5px; outline: none; color: var(--text); box-sizing: border-box; transition: border-color .15s ease, box-shadow .15s ease; }
    .aix-input:focus-visible { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(21,101,216,.1); }
    textarea.aix-input { resize: vertical; line-height: 1.55; }
    .aix-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); font-size: 12.5px; font-weight: 700; padding: 9px 14px; border-radius: 10px; min-height: 38px; cursor: pointer; text-decoration: none; }
    .aix-btn:hover { border-color: var(--primary); color: var(--primary); }
    .aix-btn:disabled { opacity: .55; cursor: not-allowed; }
    .aix-btn-primary { border: 0; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
    .aix-btn-primary:hover { color: #fff; box-shadow: 0 6px 16px rgba(21,101,216,.28); }
    .aix-btn-sm { padding: 6px 10px; min-height: 30px; font-size: 12px; }
    .aix-prev { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; line-height: 1.6; color: var(--text); white-space: pre-wrap; word-break: break-word; max-height: 170px; overflow-y: auto; }
    .aix-errbox { margin-top: 2px; padding: 10px 12px; border-radius: 10px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: var(--err); font-size: 12.5px; font-weight: 600; line-height: 1.5; }
    .aix-missing { padding: 12px 14px; border-radius: 12px; background: rgba(217,119,6,.12); border: 1px solid rgba(217,119,6,.35); color: var(--warn); font-size: 12.5px; font-weight: 600; line-height: 1.55; }
    .aix-applied { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-radius: 12px; background: rgba(22,163,74,.12); border: 1px solid rgba(22,163,74,.35); color: var(--text); font-size: 13px; font-weight: 700; }
    .aix-hint { font-size: 11.5px; font-weight: 600; color: var(--muted); line-height: 1.45; }
    .aix-workspace-grid { display: grid; grid-template-columns: minmax(0, 410px) minmax(0, 1fr); gap: 20px; align-items: start; }
    .aix-form-column { position: sticky; top: 20px; }
    .aix-form-card { display: flex; flex-direction: column; gap: 16px; }
    .aix-result-column { min-width: 0; }
    .aix-loading, .aix-empty { min-height: 354px; border: 1px solid var(--border); border-radius: 18px; background: var(--surface); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 28px; text-align: center; }
    .aix-empty-icon { display: grid; width: 52px; height: 52px; place-items: center; margin-bottom: 14px; border-radius: 16px; background: var(--chip); color: var(--primary); }
    .aix-empty-title { margin: 0; color: var(--text); font-size: 16px; font-weight: 800; }
    .aix-empty-copy { max-width: 360px; margin: 7px 0 0; color: var(--muted); font-size: 12.5px; font-weight: 600; line-height: 1.55; }
    .aix-empty-list { display: flex; flex-wrap: wrap; justify-content: center; gap: 7px; margin-top: 18px; }
    .aix-empty-list span { padding: 7px 10px; border: 1px solid var(--border); border-radius: 9px; background: var(--surface2); color: var(--muted); font-size: 10.5px; font-weight: 800; }
    .aix-results-card { display: flex; flex-direction: column; gap: 16px; }
    @media (max-width: 860px) {
        .aix-page { padding: 24px 16px 48px; }
        .aix-workspace-grid { grid-template-columns: 1fr; }
        .aix-form-column { position: static; }
        .aix-loading, .aix-empty { min-height: 210px; }
    }
    @media (max-width: 520px) {
        .aix-page { padding-top: 20px; }
        .aix-header { align-items: flex-start; margin-bottom: 18px; }
        .aix-title { font-size: 23px; letter-spacing: -.35px; }
        .aix-section { padding: 16px; border-radius: 16px; }
        .aix-workspace-grid { gap: 14px; }
        .aix-empty { min-height: 190px; padding: 22px 18px; }
        .aix-empty-list { margin-top: 14px; }
    }
</style>

<div x-data="aixWorkspaceState()" x-init="init()" x-on:keydown.escape.window="backToForm()">

        <div class="aix-page">

            {{-- Back link --}}
            <a x-on:click.prevent="backToForm()" href="{{ route('organizer.events.create') }}" class="aix-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Event builder
            </a>

            {{-- Header --}}
            <div class="aix-header">
                <span class="aix-title-icon"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.7L19.6 10l-5.7 1.9L12 17.6l-1.9-5.7L4.4 10l5.7-1.9z"/><path d="M19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9z"/></svg></span>
                <div>
                    <div class="aix-kicker">Creative assistant</div>
                    <h1 class="aix-title">AI Event Copilot</h1>
                    <p class="aix-subtitle">Turn a short brief into an editable event title, description, and category.</p>
                </div>
            </div>

            {{-- Main two-column grid --}}
            <div class="aix-workspace-grid">

                {{-- ═══════ LEFT COLUMN: Brief form ═══════ --}}
                <div class="aix-form-column">
                    <div class="aix-section aix-form-card">
                        <div class="aix-card-heading">
                            <span class="aix-card-number">1</span>
                            <div>
                                <h2 class="aix-card-title">Draft setup</h2>
                                <p class="aix-card-copy">Describe the idea and choose how the copy should sound.</p>
                            </div>
                        </div>

                        <label style="display:flex;flex-direction:column;gap:6px">
                            <span class="aix-label">Brief</span>
                            <textarea class="aix-input" x-model="brief" rows="4" placeholder="e.g. A 2-day digital art and music festival in Casablanca with local electronic artists, AI visuals, food trucks, and a rooftop afterparty."></textarea>
                        </label>
                        <label style="display:flex;flex-direction:column;gap:6px">
                            <span class="aix-label">Audience <span style="opacity:.6">(optional)</span></span>
                            <input class="aix-input" x-model="audience" placeholder="e.g. Students, families, tech professionals">
                        </label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <label style="display:flex;flex-direction:column;gap:6px">
                                <span class="aix-label">Tone</span>
                                <select class="aix-input" x-model="tone">
                                    @foreach(config('ai.event_copilot.tones') as $t)
                                        <option value="{{ $t }}" @selected($t === 'friendly')>{{ Illuminate\Support\Str::headline($t) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label style="display:flex;flex-direction:column;gap:6px">
                                <span class="aix-label">Language</span>
                                <select class="aix-input" x-model="language">
                                    @foreach(config('ai.event_copilot.languages') as $lang)
                                        <option value="{{ $lang }}" @selected($lang === 'en')>{{ ['en' => 'English', 'fr' => 'Français', 'ar' => 'العربية'][$lang] ?? $lang }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <button type="button" class="aix-btn aix-btn-primary" style="width:100%;min-height:46px;font-size:13.5px" x-on:click="generateDraft()" x-bind:disabled="busy">
                            <svg x-cloak x-show="busy" class="aix-spin" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            <svg x-show="!busy" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.7L19.6 10l-5.7 1.9L12 17.6l-1.9-5.7L4.4 10l5.7-1.9z"/><path d="M19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9z"/></svg>
                            <span x-text="busy ? 'Generating draft…' : 'Generate draft'">Generate draft</span>
                        </button>

                        <div x-show="draftError" x-cloak class="aix-errbox" x-text="draftError" role="alert"></div>
                    </div>
                </div>

                {{-- ═══════ RIGHT COLUMN: Draft results ═══════ --}}
                <div class="aix-result-column">

                    {{-- Loading state --}}
                    <div x-show="busy" x-cloak class="aix-loading">
                        <svg class="aix-spin" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        <div style="margin-top:10px;font-size:14px;font-weight:800;color:var(--text)">Generating your draft…</div>
                        <div class="aix-hint">This usually takes a few seconds.</div>
                    </div>

                    {{-- Start state placeholder --}}
                    <div x-show="!busy && !draft" x-cloak class="aix-empty">
                        <span class="aix-empty-icon">
                            <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M9 7h6M9 11h5"/></svg>
                        </span>
                        <h2 class="aix-empty-title">Your draft will appear here</h2>
                        <p class="aix-empty-copy">Add a short event brief, then generate a structured draft you can review before applying it.</p>
                        <div class="aix-empty-list" aria-label="Generated fields">
                            <span>Event title</span>
                            <span>Description</span>
                            <span>Category</span>
                        </div>
                    </div>

                    {{-- Draft results --}}
                    <template x-if="draft">
                        <div class="aix-section aix-results-card">

                            {{-- Header row --}}
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="aix-label">Draft suggestions</span>
                                <div style="flex:1"></div>
                                <span style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:8px;background:var(--surface2);border:1px solid var(--border);color:var(--muted)" x-text="draft.language === 'ar' ? 'العربية' : (draft.language === 'fr' ? 'Français' : 'English')"></span>
                            </div>

                            {{-- Missing info --}}
                            <div x-show="missingList()" x-cloak class="aix-missing" role="status">
                                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">To improve accuracy, consider adding</div>
                                <div style="white-space:pre-line" x-text="missingList()"></div>
                            </div>

                            {{-- Title suggestion --}}
                            <div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                    <span class="aix-label">Suggested title</span>
                                    <div style="flex:1"></div>
                                    <button type="button" class="aix-btn aix-btn-sm" x-on:click="copyText(suggestion('title'), 'Title')">Copy</button>
                                    <button type="button" class="aix-btn aix-btn-primary aix-btn-sm" x-on:click="useSuggestion('title')">Use</button>
                                </div>
                                <div class="aix-prev" style="max-height:90px;font-weight:700" x-bind:dir="draftLangDir()" x-text="suggestion('title')"></div>
                            </div>

                            {{-- Description suggestion --}}
                            <div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                    <span class="aix-label">Suggested description</span>
                                    <div style="flex:1"></div>
                                    <button type="button" class="aix-btn aix-btn-sm" x-on:click="copyText(suggestion('description'), 'Description')">Copy</button>
                                    <button type="button" class="aix-btn aix-btn-primary aix-btn-sm" x-on:click="useSuggestion('description')">Use</button>
                                </div>
                                <div class="aix-prev" x-bind:dir="draftLangDir()" x-text="suggestion('description')"></div>
                            </div>

                            {{-- Category suggestion --}}
                            <div x-show="suggestionsCategory()">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                    <span class="aix-label">Suggested category</span>
                                    <div style="flex:1"></div>
                                    <button type="button" class="aix-btn aix-btn-primary aix-btn-sm" x-on:click="useSuggestion('category')">Use</button>
                                </div>
                                <div style="font-size:13.5px;font-weight:700;color:var(--text)" x-text="categoryLabel()"></div>
                            </div>

                            {{-- Action buttons --}}
                            <div style="position:sticky;bottom:0;background:var(--surface);padding-top:12px;margin-top:4px;display:flex;flex-direction:column;gap:10px">
                                <button type="button" class="aix-btn aix-btn-primary" style="width:100%;min-height:46px;font-size:13.5px" x-on:click="applyDraft()">Apply draft</button>
                                <button type="button" class="aix-btn" style="width:100%" x-on:click="newDraft()">New draft</button>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toastVisible" x-cloak class="aix-toast" x-text="toastMsg" role="status"></div>

</div>

<script>
function aixWorkspaceState() {
    return {
        screen: 'start',
        tab: 'draft',
        routes: window.AIX_ROUTES || {},
        _storageKey: 'aix_copilot_session_v1',
        _toastTimer: null,

        brief: '',
        audience: '',
        tone: 'friendly',
        language: 'en',
        busy: false,
        draftError: '',

        draft: null,
        applied: false,
        undoSnapshot: null,

        toastMsg: '',
        toastVisible: false,

        init() {
            this.restoreSession();
        },

        csrf() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.content : '';
        },

        suggestion(key) {
            return this.draft && this.draft.suggestions ? (this.draft.suggestions[key] || '') : '';
        },

        suggestionsCategory() {
            return this.draft && this.draft.suggestions && this.draft.suggestions.category ? this.draft.suggestions.category : null;
        },

        categoryLabel() {
            var c = this.suggestionsCategory();
            return c ? c.name : '';
        },

        missingList() {
            if (!this.draft || !this.draft.missing_information || !this.draft.missing_information.length) return '';
            return this.draft.missing_information.map(function (m, i) { return (i + 1) + '. ' + m; }).join('\n');
        },

        draftLangDir() {
            if (this.draft && this.draft.language) return this.draft.language === 'ar' ? 'rtl' : 'ltr';
            return this.language === 'ar' ? 'rtl' : 'ltr';
        },

        persistSession() {
            if (!window.sessionStorage) return;
            try {
                sessionStorage.setItem(this._storageKey, JSON.stringify({
                    screen: this.screen,
                    tab: this.tab,
                    brief: this.brief,
                    audience: this.audience,
                    tone: this.tone,
                    language: this.language,
                    draft: this.draft,
                    applied: this.applied,
                    undo: this.undoSnapshot,
                }));
            } catch (e) { /* storage full / unavailable */ }
        },

        restoreSession() {
            if (!window.sessionStorage) return;
            try {
                var raw = sessionStorage.getItem(this._storageKey);
                if (!raw) return;
                var s = JSON.parse(raw);
                if (!s || !s.draft) return;
                this.screen = s.screen === 'session' ? 'session' : 'start';
                this.tab = 'draft';
                this.brief = s.brief || '';
                this.audience = s.audience || '';
                this.tone = s.tone || 'friendly';
                this.language = s.language || 'en';
                this.draft = s.draft;
                this.applied = !!s.applied;
                this.undoSnapshot = s.undo || null;
            } catch (e) {
                /* corrupted storage — start fresh */
            }
        },

        clearSession() {
            try { if (window.sessionStorage) sessionStorage.removeItem(this._storageKey); } catch (e) { /* ignore */ }
        },

        async post(url, payload) {
            var res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload),
            });
            var body = null;
            try { body = await res.json(); } catch (e) { body = null; }
            return { res: res, body: body };
        },

        async get(url) {
            var res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
            });
            var body = null;
            try { body = await res.json(); } catch (e) { body = null; }
            return { res: res, body: body };
        },

        async pollGeneration(genId, onResult, onError) {
            var url = this.routes.status.replace('__GEN__', encodeURIComponent(genId));
            var max = 60;
            for (var attempt = 0; attempt < max; attempt++) {
                await new Promise(function (r) { setTimeout(r, 1500); });
                var out;
                try { out = await this.get(url); } catch (e) { continue; }
                if (!out || !out.body || !out.body.data) continue;
                var d = out.body.data;
                if (d.status === 'success') { onResult(d.result || {}, d.generation_id); return; }
                if (d.status === 'error') {
                    onError(this.mapError({ error_code: d.error_code, message: d.error_message }, 200));
                    return;
                }
            }
            onError(this.mapError({ error_code: 'ai_generation_timeout' }, 200));
        },

        mapError(body, status) {
            var table = {
                ai_feature_disabled: 'AI Event Copilot is disabled.',
                ai_generation_timeout: 'The AI assistant took too long to respond. Please try again.',
                ai_provider_refused: 'The AI assistant is temporarily unavailable. Your event form has not been changed.',
                ai_provider_unavailable: 'The AI assistant is temporarily unavailable. Your event form has not been changed.',
            };
            if (body && body.error_code && table[body.error_code]) return table[body.error_code];
            if (body && body.message && typeof body.message === 'string') return body.message;
            if (body && body.errors) {
                var first = Object.values(body.errors)[0];
                if (Array.isArray(first) && first.length) return String(first[0]);
                if (typeof first === 'string') return first;
            }
            if (!status) return 'Could not reach the server. Check your connection and try again.';
            if (status === 401) return 'Your session has expired. Please refresh the page and try again.';
            if (status === 403) return 'You do not have permission to use the AI copilot.';
            if (status === 429) return 'You are making requests too quickly. Wait a moment and try again.';
            return 'Something went wrong. Please try again.';
        },

        toast(msg) {
            this.toastMsg = msg;
            this.toastVisible = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toastVisible = false; }, 2000);
        },

        copyText(text, label) {
            if (!text) return;
            var self = this;
            var done = function () { self.toast(label + ' copied'); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done).catch(function () {
                    self.fallbackCopy(text, done);
                });
            } else {
                self.fallbackCopy(text, done);
            }
        },

        fallbackCopy(text, done) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); done(); } catch (e) { /* ignore */ }
            document.body.removeChild(ta);
        },

        async generateDraft() {
            var brief = (this.brief || '').trim();
            if (!brief) { this.draftError = 'Describe your event briefly to get started.'; return; }
            this.busy = true;
            this.draftError = '';
            try {
                var payload = {
                    brief: brief,
                    audience: (this.audience || '').trim(),
                    tone: this.tone,
                    language: this.language,
                    event_context: {},
                };
                if (!payload.audience) delete payload.audience;
                var out = await this.post(this.routes.draft, payload);
                if (!out.res.ok) {
                    this.draftError = this.mapError(out.body, out.res.status);
                    return;
                }
                var genId = out.body && out.body.data ? out.body.data.generation_id : null;
                if (!genId) { this.draftError = 'The AI assistant did not respond. Please try again.'; return; }
                var self = this;
                await this.pollGeneration(genId, function (result, id) {
                    self.draft = self.draftFromResult(result, id);
                    self.screen = 'session';
                    self.tab = 'draft';
                    self.applied = false;
                    self.undoSnapshot = null;
                    self.persistSession();
                }, function (err) {
                    self.draftError = err;
                });
            } catch (e) {
                this.draftError = this.mapError(null, 0);
            } finally {
                this.busy = false;
            }
        },

        draftFromResult(result, genId) {
            return {
                generation_id: genId,
                language: this.language,
                suggestions: {
                    title: result.title || '',
                    description: result.description || '',
                    category: result.category || null,
                },
                missing_information: result.missing_information || [],
            };
        },

        useSuggestion(key) {
            if (!this.draft) return;
            if (key === 'category') {
                var cat = this.suggestionsCategory();
                if (cat) this.toast('Category set to ' + cat.name);
                return;
            }
            var val = this.suggestion(key);
            if (!val) return;
            this.toast(key === 'title' ? 'Title updated' : 'Description updated');
        },

        applyDraft() {
            if (!this.draft) return;
            this.applied = true;
            if (window.sessionStorage) {
                try {
                    sessionStorage.setItem(this._storageKey, JSON.stringify({
                        screen: 'session',
                        tab: 'draft',
                        brief: this.brief,
                        audience: this.audience,
                        tone: this.tone,
                        language: this.language,
                        draft: {
                            generation_id: this.draft.generation_id,
                            language: this.draft.language,
                            suggestions: {
                                title: this.draft.suggestions.title,
                                description: this.draft.suggestions.description,
                                category: this.draft.suggestions.category,
                            },
                            missing_information: this.draft.missing_information || [],
                        },
                        applied: true,
                        undo: null,
                    }));
                } catch (e) { /* storage full / unavailable */ }
            }
            window.location.href = '{{ route("organizer.events.create") }}';
        },

        newDraft() {
            this.draft = null;
            this.applied = false;
            this.undoSnapshot = null;
            this.draftError = '';
            this.screen = 'start';
            this.persistSession();
            this.toast('Starting a new draft');
        },

        backToForm() {
            window.location.href = '{{ route("organizer.events.create") }}';
        },
    };
}
</script>
</x-app-layout>
