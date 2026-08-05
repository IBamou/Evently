{{-- ═══════════════════════════════════════════════════════════════════════
    Confirmation modal for DANGER actions (no native alert/confirm).

    Wire any form with:
        x-on:submit.prevent="$dispatch('confirm-ask', {
            form: $event.target,
            title: '...',
            message: '...',
            confirmLabel: '...'
        })"

    For actions that are NOT a form submit, pass an `action` callback instead
    of `form` (the callback wins when both are present):
        $dispatch('confirm-ask', { title, message, confirmLabel, action: () => {...} })

    The modal lives in the layout (sibling of <main>), so the event bubbles
    up to window where this component picks it up (x-on:confirm-ask.window).
    "Confirm" runs the `action` callback, or calls form.submit() when no
    callback is given — native submit bypasses the intercepted submit handler,
    so there is no loop. ESC / backdrop / Cancel close it.
    ═══════════════════════════════════════════════════════════════════════ --}}

<div x-cloak
     x-bind:style="open ? 'position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:20px' : 'position:fixed;inset:0;z-index:200;display:none'"
     x-on:confirm-ask.window="
         form = $event.detail.form;
         title = $event.detail.title;
         message = $event.detail.message;
         confirmLabel = $event.detail.confirmLabel || 'Confirm';
         action = $event.detail.action || null;
         open = true;
     "
     x-on:keydown.escape.window="close()"
     x-data="{
         open: false,
         title: '',
         message: '',
         confirmLabel: 'Confirm',
         form: null,
         action: null,
         confirm() {
             const f = this.form;
             const a = this.action;
             this.close();
             if (typeof a === 'function') { a(); return; }
             if (f) f.submit();
         },
         close() {
             this.open = false;
             this.form = null;
             this.action = null;
         }
     }"
     style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:20px">

    {{-- Backdrop --}}
    <div x-on:click="close()"
         style="position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px)"></div>

    {{-- Dialog card --}}
    <div x-on:click.outside="close()"
         role="dialog"
         aria-modal="true"
         :aria-label="title"
         style="position:relative;width:min(400px,100%);background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px;box-shadow:0 24px 60px rgba(0,0,0,.28)">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;flex:0 0 auto;border-radius:14px;background:linear-gradient(135deg,#EF4444,#DC2626);display:grid;place-items:center;color:#fff">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3 x-text="title" style="margin:0;font-size:19px;font-weight:800;letter-spacing:-.3px;color:var(--text)"></h3>
        </div>

        <p x-text="message" style="margin:14px 0 0;font-size:13.5px;font-weight:600;color:var(--muted);line-height:1.55"></p>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
            <button type="button"
                    x-on:click="close()"
                    style="border:1px solid var(--border);background:var(--surface2);color:var(--text);cursor:pointer;font-size:13.5px;font-weight:700;padding:12px 18px;border-radius:11px;min-height:44px">Cancel</button>
            <button type="button"
                    x-on:click="confirm()"
                    x-text="confirmLabel"
                    style="border:0;background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;cursor:pointer;font-size:13.5px;font-weight:700;padding:12px 18px;border-radius:11px;min-height:44px"></button>
        </div>
    </div>
</div>
