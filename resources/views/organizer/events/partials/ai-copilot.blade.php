{{-- ═══════════════════════════════════════════════════════════════════════
    AI Event Copilot — Start screen → AI workspace (tabs: Draft | Polish).

    Include inside `@if(config('ai-event-copilot.enabled'))` on the organizer
    event create/edit pages (create.blade.php / edit.blade.php). Self-contained:
    renders its own trigger button, the right-side drawer and all Alpine state.

    Backend contract (routes/organizer/ai/* — ASYNC, queue-backed):
      POST ai/event-drafts            → 202 { data: { generation_id, status: 'processing' } }
      POST ai/event-fields/transform  → 202 { data: { generation_id, status: 'processing' } }
      GET  ai/generations/{public_id} → 200 { data: { generation_id, status
           ('processing'|'success'|'error'|'blocked'), result|null, error_code,
           error_message, operation, provider_used, model_used, latency_ms } }
           success.result per operation:
             draft:      { title, description, category{id,name,slug}|null,
                           marketing{social_post,email_subject,email_intro}, missing_information[] }
             transform:  { content, language, warnings[] }
      POST ai/generations/{id}/feedback → { action: applied_field|applied_all|regenerated|dismissed, field? }
    Errors: { message, error_code } with 403/429/422 — mapped in mapError().
    ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $aiRoutes = [
        'draft' => route('organizer.ai.event-drafts'),
        'transform' => route('organizer.ai.event-fields.transform'),
        'status' => route('organizer.ai.generations.status', ['generation' => '__GEN__']),
        'feedback' => route('organizer.ai.generations.feedback', ['generation' => '__GEN__']),
    ];
@endphp

<script>window.AIX_ROUTES = @json($aiRoutes);</script>

<style>
    /* ── AI copilot panel primitives (CSS vars only, consistent with app tokens) ── */
    .aix-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.45); backdrop-filter: blur(3px); z-index: 170; }
    .aix-drawer   { position: fixed; top: 0; right: 0; bottom: 0; width: min(480px, 100%); background: var(--surface); border-left: 1px solid var(--border); box-shadow: -24px 0 60px rgba(9,30,66,.22); z-index: 180; display: flex; flex-direction: column; }
    .aix-toast    { position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); z-index: 190; background: var(--primary-dark); color: #fff; font-size: 13px; font-weight: 700; padding: 10px 18px; border-radius: 10px; box-shadow: 0 10px 28px rgba(9,30,66,.3); }

    .aix-hint   { font-size: 11.5px; font-weight: 600; color: var(--muted); line-height: 1.45; }
    .aix-section{ background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 14px; }
    .aix-label  { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); }
    .aix-input  { width: 100%; min-height: 44px; padding: 11px 13px; border: 1px solid var(--border); background: var(--surface2); border-radius: 11px; font-size: 13.5px; outline: none; color: var(--text); box-sizing: border-box; }
    .aix-input:focus-visible { border-color: var(--primary); outline: 2px solid var(--primary); outline-offset: 1px; }
    textarea.aix-input { resize: vertical; line-height: 1.55; }

    .aix-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); font-size: 12.5px; font-weight: 700; padding: 9px 14px; border-radius: 10px; min-height: 38px; cursor: pointer; text-decoration: none; }
    .aix-btn:hover { border-color: var(--primary); color: var(--primary); }
    .aix-btn:disabled { opacity: .55; cursor: not-allowed; }
    .aix-btn-primary { border: 0; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
    .aix-btn-primary:hover { color: #fff; box-shadow: 0 6px 16px rgba(21,101,216,.28); }
    .aix-btn-ghost { border: 0; background: none; color: var(--primary); }
    .aix-btn-sm { padding: 6px 10px; min-height: 30px; font-size: 12px; }

    /* ── Tabs ── */
    .aix-tabbar { display: flex; gap: 4px; padding: 6px; background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; }
    .aix-tab { flex: 1; min-height: 34px; font-size: 12.5px; font-weight: 700; color: var(--muted); background: transparent; border: 0; border-radius: 8px; cursor: pointer; }
    .aix-tab:hover { color: var(--primary); }
    .aix-tab-active { background: var(--surface); color: var(--primary); box-shadow: 0 1px 4px rgba(9,30,66,.14); }

    /* ── Segmented control ── */
    .aix-seg { display: flex; gap: 4px; padding: 3px; background: var(--surface2); border: 1px solid var(--border); border-radius: 9px; }
    .aix-seg-btn { flex: 1; min-height: 32px; font-size: 12.5px; font-weight: 700; color: var(--muted); background: transparent; border: 0; border-radius: 7px; cursor: pointer; }
    .aix-seg-btn:hover { color: var(--primary); }
    .aix-seg-active { background: var(--surface); color: var(--primary); box-shadow: 0 1px 4px rgba(9,30,66,.14); }

    /* ── Dropdown menu (tone / translate) ── */
    .aix-drop { position: relative; }
    .aix-menu { position: absolute; top: calc(100% + 6px); left: 0; z-index: 20; min-width: 170px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 14px 34px rgba(9,30,66,.22); padding: 6px; display: flex; flex-direction: column; gap: 2px; }
    .aix-menu button { text-align: left; background: transparent; border: 0; color: var(--text); font-size: 13px; font-weight: 600; padding: 9px 11px; border-radius: 8px; cursor: pointer; }
    .aix-menu button:hover { background: var(--surface2); color: var(--primary); }

    .aix-prev { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; line-height: 1.6; color: var(--text); white-space: pre-wrap; word-break: break-word; max-height: 170px; overflow-y: auto; }
    .aix-errbox   { margin-top: 2px; padding: 10px 12px; border-radius: 10px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: var(--err); font-size: 12.5px; font-weight: 600; line-height: 1.5; }
    .aix-missing  { padding: 12px 14px; border-radius: 12px; background: rgba(217,119,6,.12); border: 1px solid rgba(217,119,6,.35); color: var(--warn); font-size: 12.5px; font-weight: 600; line-height: 1.55; }
    .aix-applied  { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-radius: 12px; background: rgba(22,163,74,.12); border: 1px solid rgba(22,163,74,.35); color: var(--text); font-size: 13px; font-weight: 700; }

    /* Panel layout lives in classes so Alpine's display toggling (x-show /
       x-bind:style) never wipes padding/flex/overflow from inline styles. */
    .aix-panel   { flex: 1; overflow-y: auto; padding: 14px 16px 20px; display: flex; flex-direction: column; }
    .aix-flexcol { display: flex; flex-direction: column; }
</style>

<script>
window.aixCopilotState = function () {
    return {
    // ── boot / state ──
    "open": false,
    "screen": "start",          // 'start' | 'session'
    "tab": "draft",             // 'draft' | 'polish'
    "routes": window.AIX_ROUTES || {},
    "_storageKey": "aix_copilot_session_v1",
    "_toastTimer": null,

    // ── generate form ──
    "brief": "",
    "audience": "",
    "tone": "friendly",
    "language": "en",
    "busy": false,
    "draftError": "",

    // ── draft session ──
    "draft": null,              // { generation_id, language, suggestions{title,description,category,marketing}, missing_information[] }
    "applied": false,
    "undoSnapshot": null,       // previous form values right before "Apply draft"
    "formTick": 0,              // bumped whenever JS writes to the event form (drives reactive tab visibility)

    // ── polish ──
    "polishField": "title",
    "polishMenu": "Improve",    // just for a11y label
    "transforming": false,
    "compare": null,            // { field, action, original, content, language, generation_id, error }
    "toneMenuOpen": false,
    "translateMenuOpen": false,

    // ── toast ──
    "toastMsg": "",
    "toastVisible": false,
    "genState": "idle",         // 'idle' | 'generating' | 'transforming'

    // ── lifecycle ──
    init() {
        this.restoreSession();
    },

    // ── visibility helpers (x-bind:style REPLACES the style attr, so return full display) ──
    drawerVis() { return this.open ? '' : 'display:none'; },
    backdropVis() { return this.open ? '' : 'display:none'; },
    spinVis(cond) { return cond ? 'animation:spin 1s linear infinite' : 'display:none'; },
    errVis() { return this.draftError ? '' : 'display:none'; },
    toastVis() { return this.toastVisible ? '' : 'display:none'; },
    draftLangDir() {
        if (this.draft && this.draft.language) return this.draft.language === 'ar' ? 'rtl' : 'ltr';
        return this.language === 'ar' ? 'rtl' : 'ltr';
    },

    // ── readers ──
    csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; },
    eventForm() {
        var f = document.getElementById('event-form');
        if (f) return f;
        var titled = document.querySelector('form [name="title"]');
        if (titled && titled.form) return titled.form;
        return document.querySelector('form');
    },
    fieldInput(name) { var f = this.eventForm(); return f ? (f.elements[name] || null) : null; },
    formValue(name) { var el = this.fieldInput(name); return el ? el.value : ''; },
    titleContent() { return (this.formValue('title') || '').trim(); },
    descContent() { return (this.formValue('description') || '').trim(); },
    canPolish() { return this.formTick >= 0 && !!(this.titleContent() || this.descContent()); },
    tabVisible(t) {
        if (t === 'draft') return true;
        if (t === 'polish') return this.canPolish();
        return false;
    },
    eventContext(keys) {
        var ctx = {}, f = this.eventForm(), read = function (k, max) {
            if (!f || !f.elements[k]) return;
            var v = (f.elements[k].value || '').trim();
            if (v) ctx[k] = max ? v.slice(0, max) : v;
        };
        if (keys.indexOf('title') !== -1) read('title', 255);
        if (keys.indexOf('description') !== -1) read('description');
        if (keys.indexOf('city') !== -1) read('city', 255);
        if (keys.indexOf('location') !== -1) read('location', 255);
        if (keys.indexOf('starts_at') !== -1) read('starts_at');
        if (keys.indexOf('ends_at') !== -1) read('ends_at');
        return ctx;
    },
    suggestion(key) { return this.draft && this.draft.suggestions ? (this.draft.suggestions[key] || '') : ''; },
    suggestionsCategory() { return this.draft && this.draft.suggestions && this.draft.suggestions.category ? this.draft.suggestions.category : null; },
    categoryLabel() { var c = this.suggestionsCategory(); return c ? c.name : ''; },
    missingList() {
        if (!this.draft || !this.draft.missing_information || !this.draft.missing_information.length) return '';
        return this.draft.missing_information.map(function (m, i) { return (i + 1) + '. ' + m; }).join('\n');
    },

    // ── session persistence ──
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
        } catch (e) { /* storage full / unavailable — ignore */ }
    },
    restoreSession() {
        if (!window.sessionStorage) return;
        try {
            var raw = sessionStorage.getItem(this._storageKey);
            if (!raw) return;
            var s = JSON.parse(raw);
            if (!s || !s.draft) return;
            this.screen = s.screen === 'session' ? 'session' : 'start';
            this.tab = this.tabVisible(s.tab) ? s.tab : 'draft';
            this.brief = s.brief || '';
            this.audience = s.audience || '';
            this.tone = s.tone || 'friendly';
            this.language = s.language || 'en';
            this.draft = s.draft;
            this.applied = !!s.applied;
            this.undoSnapshot = s.undo || null;
            if (this.screen === 'session' && !this.tabVisible(this.tab)) this.tab = 'draft';
            // A page reload wipes the native form; re-apply an already-applied draft so the
            // workspace state matches the form again.
            if (this.applied && this.draft) {
                var titleInput = this.fieldInput('title');
                var descInput = this.fieldInput('description');
                if (titleInput && !titleInput.value && this.suggestion('title')) titleInput.value = this.suggestion('title');
                if (descInput && !descInput.value && this.suggestion('description')) descInput.value = this.suggestion('description');
                var cat = this.suggestionsCategory();
                var sel = document.querySelector('select[name="category_id"]');
                if (sel && cat && !sel.value && Array.prototype.some.call(sel.options, function (o) { return String(o.value) === String(cat.id); })) {
                    sel.value = String(cat.id);
                }
                this.formTick++;
            }
        } catch (e) {
            /* corrupted storage — start fresh */
        }
    },
    clearSession() {
        try { if (window.sessionStorage) sessionStorage.removeItem(this._storageKey); } catch (e) { /* ignore */ }
    },

    // ── HTTP ──
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
        var res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        var body = null;
        try { body = await res.json(); } catch (e) { body = null; }
        return { res: res, body: body };
    },
    // Poll a generation until it finishes (success/error/blocked) or times out.
    // Polls every 1.5s for up to 90s: covers the 30s provider timeout + fallback retry.
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
            if (d.status === 'error' || d.status === 'blocked') {
                onError(this.mapError({ error_code: d.error_code, message: d.error_message }, 200));
                return;
            }
        }
        onError(this.mapError({ error_code: 'ai_generation_timeout' }, 200));
    },
    mapError(body, status) {
        var table = {
            ai_feature_disabled: 'AI Event Copilot is disabled.',
            ai_rate_limited: 'You have made several AI requests. Try again shortly.',
            ai_daily_limit_reached: "You have reached today's AI-generation limit. You can still complete the event manually.",
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

    // ── toast ──
    toast(msg) {
        this.toastMsg = msg;
        this.toastVisible = true;
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => { this.toastVisible = false; }, 2000);
    },
    copyText(text, label) {
        if (!text) return;
        var done = () => this.toast(label + ' copied');
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(() => this.fallbackCopy(text, done));
        } else {
            this.fallbackCopy(text, done);
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

    // ── feedback (fire-and-forget) ──
    sendFeedback(generationId, action, field) {
        if (!generationId || !this.routes.feedback) return;
        var payload = { action: action };
        if (field) payload.field = field;
        fetch(this.routes.feedback.replace('__GEN__', encodeURIComponent(generationId)), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            body: JSON.stringify(payload),
        }).catch(() => {});
    },

    // ── navigation ──
    startGeneration() {
        this.open = true;
        if (this.draft) { this.screen = 'session'; this.tab = 'draft'; }
    },
    setTab(t) {
        if (!this.tabVisible(t)) return;
        this.tab = t;
        this.toneMenuOpen = false;
        this.translateMenuOpen = false;
        this.persistSession();
    },
    newDraft() {
        // Regenerate from scratch without leaving the session workspace.
        this.draft = null;
        this.applied = false;
        this.undoSnapshot = null;
        this.draftError = '';
        this.tab = 'draft';
        this.persistSession();
        this.toast('Creating a new draft');
    },
    closeCopilot() { this.open = false; },

    // ── draft generation ──
    async generateDraft() {
        var brief = (this.brief || '').trim();
        if (!brief) { this.draftError = 'Describe your event briefly to get started.'; return; }
        this.busy = true;
        this.genState = 'generating';
        this.draftError = '';
        try {
            var payload = {
                brief: brief,
                audience: (this.audience || '').trim(),
                tone: this.tone,
                language: this.language,
                event_context: this.eventContext(['title', 'description', 'city', 'location', 'starts_at', 'ends_at']),
            };
            if (!payload.audience) delete payload.audience;
            var out = await this.post(this.routes.draft, payload);
            if (!out.res.ok) {
                this.draftError = this.mapError(out.body, out.res.status);
                return;
            }
            var genId = out.body && out.body.data ? out.body.data.generation_id : null;
            if (!genId) { this.draftError = 'The AI assistant did not respond. Please try again.'; return; }
            await this.pollGeneration(genId, (result, id) => {
                this.draft = this.draftFromResult(result, id);
                this.screen = 'session';
                this.tab = 'draft';
                this.applied = false;
                this.undoSnapshot = null;
                this.persistSession();
            }, (err) => {
                this.draftError = err;
            });
        } catch (e) {
            this.draftError = this.mapError(null, 0);
        } finally {
            this.busy = false;
            this.genState = 'idle';
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
            var sel = document.querySelector('form [name="category_id"]');
            if (sel && cat && Array.prototype.some.call(sel.options, function (o) { return String(o.value) === String(cat.id); })) {
                sel.value = String(cat.id);
                this.formTick++;
                this.toast('Category set to ' + cat.name);
            }
            return;
        }
        var input = this.fieldInput(key);
        var val = this.suggestion(key);
        if (!input || !val) return;
        input.value = val;
        this.formTick++;
        this.sendFeedback(this.draft.generation_id, 'applied_field', key);
        this.toast(key === 'title' ? 'Title updated' : 'Description updated');
    },
    applyDraft() {
        if (!this.draft) return;
        var titleInput = this.fieldInput('title');
        var descInput = this.fieldInput('description');
        var sel = document.querySelector('select[name="category_id"]');
        this.undoSnapshot = {
            title: titleInput ? titleInput.value : '',
            description: descInput ? descInput.value : '',
            category: sel ? sel.value : '',
        };
        if (titleInput && this.suggestion('title')) titleInput.value = this.suggestion('title');
        if (descInput && this.suggestion('description')) descInput.value = this.suggestion('description');
        var cat = this.suggestionsCategory();
        if (sel && cat && Array.prototype.some.call(sel.options, function (o) { return String(o.value) === String(cat.id); })) {
            sel.value = String(cat.id);
        }
        this.formTick++;
        this.sendFeedback(this.draft.generation_id, 'applied_all');
        this.applied = true;
        this.persistSession();
        this.toast('AI draft applied to the form');
    },
    undoApply() {
        if (!this.undoSnapshot) return;
        var titleInput = this.fieldInput('title');
        var descInput = this.fieldInput('description');
        if (titleInput) titleInput.value = this.undoSnapshot.title;
        if (descInput) descInput.value = this.undoSnapshot.description;
        var prevCat = this.undoSnapshot.category;
        if (prevCat) {
            var sel = document.querySelector('select[name="category_id"]');
            if (sel && Array.prototype.some.call(sel.options, function (o) { return String(o.value) === String(prevCat); })) {
                sel.value = String(prevCat);
            }
        }
        this.undoSnapshot = null;
        this.applied = false;
        this.formTick++;
        this.persistSession();
        this.toast('Reverted to your previous values');
    },

    // ── polish (field-oriented, always on current form value) ──
    switchPolishField(field) {
        if (this.compare && this.compare.field !== field) {
            this.cancelCompare();
        }
        this.polishField = field;
    },
    async runPolish(op, toneVal, langVal) {
        var f = this.polishField;
        var input = this.fieldInput(f);
        var content = input ? (input.value || '').trim() : '';
        if (!content) {
            this.toast((f === 'title' ? 'Title' : 'Description') + ' is empty — type something first');
            return;
        }
        var action = { op: op, tone: toneVal || null, lang: langVal || null };
        await this.runTransform(f, action);
    },
    async runTransform(field, action) {
        var input = this.fieldInput(field);
        var content = input ? (input.value || '').trim() : '';
        this.transforming = true;
        this.genState = 'transforming';
        this.compare = { field: field, action: action, original: content, content: '', generation_id: null, language: 'en', error: '', warnings: [] };
        try {
            var payload = {
                field: field,
                operation: action.op,
                content: content,
                event_context: this.eventContext(['title', 'city']),
            };
            if (action.tone) payload.tone = action.tone;
            if (action.lang) payload.target_language = action.lang;
            var out = await this.post(this.routes.transform, payload);
            if (!out.res.ok) {
                this.compare.error = this.mapError(out.body, out.res.status);
                return;
            }
            var genId = out.body && out.body.data ? out.body.data.generation_id : null;
            if (!genId) { this.compare.error = 'The AI assistant did not respond. Please try again.'; return; }
            await this.pollGeneration(genId, (result) => {
                this.compare.content = result.content || '';
                this.compare.language = result.language || 'en';
                this.compare.generation_id = genId;
                this.compare.warnings = result.warnings || [];
            }, (err) => {
                this.compare.error = err;
            });
        } catch (e) {
            this.compare.error = this.mapError(null, 0);
        } finally {
            this.transforming = false;
            this.genState = 'idle';
        }
    },
    applyTransform() {
        var c = this.compare;
        if (!c || !c.content) return;
        var input = this.fieldInput(c.field);
        if (input) input.value = c.content;
        this.formTick++;
        this.sendFeedback(c.generation_id, 'applied_field', c.field);
        this.compare = null;
        this.translateMenuOpen = false;
        this.toneMenuOpen = false;
        this.persistSession();
        this.toast('Applied to ' + (c.field === 'title' ? 'title' : 'description'));
    },
    regenerateTransform() {
        var c = this.compare;
        if (!c) return;
        this.sendFeedback(c.generation_id, 'regenerated', c.field);
        this.runTransform(c.field, c.action);
    },
    cancel() {
        var c = this.compare;
        if (!c) return;
        this.sendFeedback(c.generation_id, 'dismissed', c.field);
        this.compare = null;
        this.translateMenuOpen = false;
        this.toneMenuOpen = false;
    }
    };
};
</script>

<div x-data="aixCopilotState()" x-init="init()" x-on:keydown.escape.window="closeCopilot()">

    {{-- ══════════════ Trigger button (renders in the page header row) ══════════════ --}}
    <button type="button" x-on:click="open = true" class="aix-btn" style="min-height:40px" aria-haspopup="dialog" aria-controls="ai-copilot-drawer">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.7L19.6 10l-5.7 1.9L12 17.6l-1.9-5.7L4.4 10l5.7-1.9z"/><path d="M19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9z"/></svg>
        <span>AI copilot</span>
    </button>

    {{-- ══════════════ Backdrop ══════════════ --}}
    <div x-cloak x-bind:style="backdropVis()" class="aix-backdrop" x-on:click="open = false" aria-hidden="true"></div>

    {{-- ══════════════ Drawer ══════════════ --}}
    <div x-cloak x-bind:style="drawerVis()" class="aix-drawer" id="ai-copilot-drawer" role="dialog" aria-modal="true" aria-label="AI Event Copilot">

        {{-- Header --}}
        <div style="flex:0 0 auto;background:linear-gradient(120deg,var(--primary-dark),var(--primary));padding:16px 18px;color:#fff;display:flex;align-items:center;gap:12px">
            <div style="width:38px;height:38px;flex:0 0 auto;border-radius:12px;background:rgba(255,255,255,.16);display:grid;place-items:center">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l2.4 7.2L22 12l-7.6 2.8L12 22l-2.4-7.2L2 12l7.6-2.8z"/></svg>
            </div>
            <div style="min-width:0;flex:1">
                <div style="font-size:16.5px;font-weight:800;letter-spacing:-.3px">AI Event Copilot</div>
                <div style="font-size:12px;font-weight:600;opacity:.85" x-text="screen === 'session' ? 'Your event creation workspace' : 'Create a stronger event in seconds'">Create a stronger event in seconds</div>
            </div>
            <button type="button" x-on:click="open = false" aria-label="Close AI copilot" style="width:34px;height:34px;flex:0 0 auto;border:0;border-radius:10px;background:rgba(255,255,255,.16);color:#fff;cursor:pointer;display:grid;place-items:center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- ══════════════ START SCREEN: the only job is getting started ══════════════ --}}
        <div x-show="screen === 'start'" class="aix-panel" style="padding:18px 18px 24px;gap:14px">
            <div>
                <div style="font-size:16px;font-weight:800;color:var(--text)">What are you planning?</div>
                <div class="aix-hint" style="margin-top:4px">Describe your event in a few sentences — secrets to a great draft: theme, format, vibe, what attendees should expect.</div>
            </div>

            <div class="aix-section" style="display:flex;flex-direction:column;gap:12px">
                <label style="display:flex;flex-direction:column;gap:6px">
                    <span class="aix-label">Brief</span>
                    <textarea class="aix-input" name="aix-brief" x-model="brief" rows="3" x-ref="aixBrief" placeholder="e.g. A 2-day digital art & music festival in Casablanca mixing local electronic artists with AI-generated visuals, food trucks and a rooftop afterparty."></textarea>
                </label>
                <label style="display:flex;flex-direction:column;gap:6px">
                    <span class="aix-label">Audience <span style="opacity:.6">(optional)</span></span>
                    <input class="aix-input" name="aix-audience" x-model="audience" x-ref="aixAudience" placeholder="e.g. Students, families, tech professionals">
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <label style="display:flex;flex-direction:column;gap:6px">
                        <span class="aix-label">Tone</span>
                        <select class="aix-input" x-model="tone" x-ref="aixTone">
                            @foreach(config('ai-event-copilot.tones') as $tone)
                                <option value="{{ $tone }}" @selected($tone === 'friendly')>{{ \Illuminate\Support\Str::headline($tone) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:6px">
                        <span class="aix-label">Language</span>
                        <select class="aix-input" x-model="language" x-ref="aixLang">
                            @foreach(config('ai-event-copilot.languages') as $lang)
                                <option value="{{ $lang }}" @selected($lang === 'en')>{{ ['en' => 'English', 'fr' => 'Français', 'ar' => 'العربية'][$lang] ?? $lang }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <button type="button" class="aix-btn aix-btn-primary" style="min-height:48px;font-size:14px" x-on:click="generateDraft()" x-bind:disabled="busy">
                <svg x-bind:style="spinVis(busy)" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <span x-text="busy ? 'Generating draft…' : '✨ Generate draft'">✨ Generate draft</span>
            </button>
            <div x-cloak x-bind:style="errVis()" class="aix-errbox" x-text="draftError" role="alert"></div>
        </div>

        {{-- ══════════════ SESSION SCREEN: tabs ══════════════ --}}
        <template x-if="screen === 'session'">
        <div style="flex:1;display:flex;flex-direction:column;min-height:0">

            {{-- Tab bar --}}
            <div style="flex:0 0 auto;padding:12px 16px 0;display:flex;flex-direction:column;gap:10px">
                <div class="aix-tabbar" role="tablist" aria-label="Copilot tools">
                    <button type="button" role="tab" class="aix-tab" x-bind:class="tab === 'draft' ? 'aix-tab-active' : ''" x-on:click="setTab('draft')">Draft</button>
                    <button type="button" role="tab" class="aix-tab" x-show="canPolish()" x-bind:class="tab === 'polish' ? 'aix-tab-active' : ''" x-on:click="setTab('polish')">Polish</button>
                </div>
                {{-- Applied confirmation (contextual, replaces permanent Undo All) --}}
                <div x-cloak x-show="applied" class="aix-applied">
                    <span style="color:#16a34a;font-size:14px" aria-hidden="true">✓</span>
                    <span style="flex:1">Draft applied to the form</span>
                    <button type="button" class="aix-btn aix-btn-sm" x-on:click="undoApply()">Undo</button>
                </div>
            </div>

            {{-- ────────── TAB: DRAFT ────────── --}}
            <div x-show="tab === 'draft'" class="aix-panel" style="gap:12px">

                {{-- no draft yet → inline regenerate form --}}
                <div x-show="!draft">
                    <div style="font-size:15px;font-weight:800;color:var(--text);margin-bottom:12px">Create with AI</div>
                    <div class="aix-section" style="display:flex;flex-direction:column;gap:12px">
                        <label style="display:flex;flex-direction:column;gap:6px">
                            <span class="aix-label">Brief</span>
                            <textarea class="aix-input" name="aix-brief-inline" x-model="brief" rows="3" placeholder="Describe your event in a few sentences."></textarea>
                        </label>
                        <label style="display:flex;flex-direction:column;gap:6px">
                            <span class="aix-label">Audience <span style="opacity:.6">(optional)</span></span>
                            <input class="aix-input" name="aix-audience-inline" x-model="audience" placeholder="e.g. Students, families, tech professionals">
                        </label>
                        <button type="button" class="aix-btn aix-btn-primary" style="min-height:44px" x-on:click="generateDraft()" x-bind:disabled="busy">
                            <svg x-bind:style="spinVis(busy)" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            <span x-text="busy ? 'Generating draft…' : '✨ Generate draft'">✨ Generate draft</span>
                        </button>
                    </div>
                </div>

                <!-- generating state -->
                <div x-show="busy" style="border:1px solid var(--border);border-radius:14px;padding:18px 16px;display:flex;flex-direction:column;gap:6px;align-items:center;text-align:center">
                    <svg x-bind:style="spinVis(true)" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    <div style="font-size:13.5px;font-weight:700;color:var(--text)">Generating your draft…</div>
                    <div class="aix-hint">This usually takes a few seconds.</div>
                </div>

                <!-- draft result -->
                <template x-if="draft">
                    <div style="display:flex;flex-direction:column;gap:14px">

                        <div x-cloak x-show="missingList()" class="aix-missing" role="status">
                            <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px">To improve accuracy, consider adding</div>
                            <div style="white-space:pre-line" x-text="missingList()"></div>
                        </div>

                        <div style="display:flex;align-items:center;gap:8px">
                            <span class="aix-label">Draft suggestions</span>
                            <div style="flex:1"></div>
                            <span class="aix-hint" x-text="draft ? (draft.language === 'ar' ? 'العربية' : (draft.language === 'fr' ? 'Français' : 'English')) : ''"></span>
                        </div>

                        {{-- Title --}}
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <span class="aix-label">Suggested title</span>
                                <div style="flex:1"></div>
                                <button type="button" class="aix-btn aix-btn-sm" x-on:click="copyText(suggestion('title'), 'Title')">Copy</button>
                                <button type="button" class="aix-btn aix-btn-primary aix-btn-sm" x-on:click="useSuggestion('title')">Use</button>
                            </div>
                            <div class="aix-prev" style="max-height:90px;font-weight:700" x-bind:dir="draftLangDir()" x-text="suggestion('title')"></div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <span class="aix-label">Suggested description</span>
                                <div style="flex:1"></div>
                                <button type="button" class="aix-btn aix-btn-sm" x-on:click="copyText(suggestion('description'), 'Description')">Copy</button>
                                <button type="button" class="aix-btn aix-btn-primary aix-btn-sm" x-on:click="useSuggestion('description')">Use</button>
                            </div>
                            <div class="aix-prev" x-bind:dir="draftLangDir()" x-text="suggestion('description')"></div>
                        </div>

                        {{-- Category --}}
                        <div x-show="suggestionsCategory()">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <span class="aix-label">Suggested category</span>
                                <div style="flex:1"></div>
                                <button type="button" class="aix-btn aix-btn-primary aix-btn-sm" x-on:click="useSuggestion('category')">Use</button>
                            </div>
                            <div style="font-size:13.5px;font-weight:700;color:var(--text)" x-text="categoryLabel()"></div>
                        </div>

                        {{-- Sticky-primary CTA row --}}
                        <div style="position:sticky;bottom:0;background:var(--surface);padding-top:10px;margin-top:-4px">
                            <button type="button" class="aix-btn aix-btn-primary" style="width:100%;min-height:46px;font-size:14px" x-on:click="applyDraft()">✨ Apply draft</button>
                        </div>
                        <button type="button" class="aix-btn" style="width:100%" x-on:click="newDraft()">↻ New draft</button>
                    </div>
                </template>

                <div x-cloak x-bind:style="errVis()" class="aix-errbox" x-text="draftError" role="alert"></div>
            </div>

            {{-- ────────── TAB: POLISH ────────── --}}
            <div x-show="tab === 'polish'" class="aix-panel" style="gap:14px">

                <div>
                    <div style="font-size:15px;font-weight:800;color:var(--text)">Polish your event</div>
                    <div class="aix-hint" style="margin-top:2px">Runs on the current value in the form — polish anything, anytime.</div>
                </div>

                {{-- Field selector --}}
                <div class="aix-seg" role="tablist" aria-label="Field to polish">
                    <button type="button" class="aix-seg-btn" x-bind:class="polishField === 'title' ? 'aix-seg-active' : ''" x-on:click="switchPolishField('title')">Title</button>
                    <button type="button" class="aix-seg-btn" x-bind:class="polishField === 'description' ? 'aix-seg-active' : ''" x-on:click="switchPolishField('description')">Description</button>
                </div>

                {{-- Current value --}}
                <div>
                    <div class="aix-label" style="margin-bottom:6px">Current <span x-text="polishField === 'title' ? 'title' : 'description'"></span></div>
                    <div class="aix-prev" style="max-height:none" x-text="polishField === 'title' ? (titleContent() || 'Nothing here yet — type a title first.') : (descContent() || 'Nothing here yet — type a description first.')"></div>
                </div>

                {{-- Actions (4, with tone/translate submenus) --}}
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    <button type="button" class="aix-chip" x-on:click="runPolish('rewrite')" x-bind:disabled="transforming" style="display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:12px;font-weight:700;padding:8px 11px;border-radius:9px;cursor:pointer;min-height:34px">Improve</button>
                    <button type="button" class="aix-chip" x-on:click="runPolish('shorten')" x-bind:disabled="transforming" style="display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:12px;font-weight:700;padding:8px 11px;border-radius:9px;cursor:pointer;min-height:34px">Shorten</button>

                    <div class="aix-drop">
                        <button type="button" class="aix-chip" x-on:click="toneMenuOpen = !toneMenuOpen; translateMenuOpen = false" x-bind:disabled="transforming" style="display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:12px;font-weight:700;padding:8px 11px;border-radius:9px;cursor:pointer;min-height:34px">Change tone ▾</button>
                        <div x-show="toneMenuOpen" x-on:click.outside="toneMenuOpen = false" class="aix-menu">
                            <button type="button" x-on:click="toneMenuOpen = false; runPolish('rewrite', 'professional')">Professional</button>
                            <button type="button" x-on:click="toneMenuOpen = false; runPolish('rewrite', 'friendly')">Friendly</button>
                            <button type="button" x-on:click="toneMenuOpen = false; runPolish('rewrite', 'energetic')">Energetic</button>
                        </div>
                    </div>

                    <div class="aix-drop">
                        <button type="button" class="aix-chip" x-on:click="translateMenuOpen = !translateMenuOpen; toneMenuOpen = false" x-bind:disabled="transforming" style="display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:12px;font-weight:700;padding:8px 11px;border-radius:9px;cursor:pointer;min-height:34px">Translate ▾</button>
                        <div x-show="translateMenuOpen" x-on:click.outside="translateMenuOpen = false" class="aix-menu">
                            <button type="button" x-on:click="translateMenuOpen = false; runPolish('translate', null, 'en')">English</button>
                            <button type="button" x-on:click="translateMenuOpen = false; runPolish('translate', null, 'fr')">Français</button>
                            <button type="button" x-on:click="translateMenuOpen = false; runPolish('translate', null, 'ar')">العربية</button>
                        </div>
                    </div>
                </div>

                {{-- Comparison box --}}
                <div x-cloak x-show="compare" class="aix-section" style="border-color:var(--primary);background:var(--surface2)">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                        <span class="aix-label" style="color:var(--primary)">AI suggestion —</span>
                        <div style="flex:1"></div>
                        <button type="button" class="aix-btn aix-btn-ghost" x-on:click="cancel()" aria-label="Close comparison" style="min-height:30px;padding:5px 9px">✕</button>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div>
                            <div class="aix-label" style="margin-bottom:6px">Current</div>
                            <div class="aix-prev" style="max-height:110px;color:var(--muted)" x-text="compare ? compare.original : ''"></div>
                        </div>
                        <div>
                            <div class="aix-label" style="margin-bottom:6px">AI version</div>
                            <div class="aix-prev" x-bind:dir="compare && compare.language === 'ar' ? 'rtl' : 'ltr'" x-text="compare ? compare.content : ''"></div>
                        </div>
                    </div>

                    <div x-cloak x-show="compare && compare.error" class="aix-errbox" style="margin-top:10px" x-text="compare ? compare.error : ''" role="alert"></div>

                    <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
                        <button type="button" class="aix-btn" x-on:click="cancel()">Cancel</button>
                        <button type="button" class="aix-btn" x-on:click="regenerateTransform()" x-bind:disabled="transforming">
                            <svg x-bind:style="spinVis(transforming)" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            <span x-text="transforming ? 'Generating…' : '↻ Try again'">↻ Try again</span>
                        </button>
                        <div style="flex:1"></div>
                        <button type="button" class="aix-btn aix-btn-primary" x-on:click="applyTransform()" x-bind:disabled="!compare || !compare.content || transforming">Apply</button>
                    </div>
                </div>
            </div>

            </div>
        </template>

        {{-- Toast --}}
        <div x-cloak x-bind:style="toastVis()" class="aix-toast" x-text="toastMsg" role="status"></div>
    </div>
</div>