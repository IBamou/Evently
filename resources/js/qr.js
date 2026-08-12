// resources/js/qr.js — QR code rendering + camera scanning.
//
// Dependencies (both browser-side, dynamically imported by Vite):
//   - `qrcode`       → render ticket QR codes as <canvas> (tickets page, booking detail).
//   - `html5-qrcode` → camera-based decoding for the organizer check-in page.
//
// IMPORTANT (perf): neither library is statically imported here — together they
// shipped ~410 KB raw (~120 KB gzip) on EVERY page via app.js. Instead this
// module exposes cached lazy loaders (`loadQrRender` / `loadScanner`) and the
// exported wrappers fetch the library only when a QR feature actually runs.
// qr.js itself is only loaded on the 3 pages that use it:
//   - resources/views/tickets/index.blade.php        (@vite('resources/js/qr.js'))
//   - resources/views/bookings/show.blade.php        (@vite('resources/js/qr.js'))
//   - resources/views/organizer/check-in/index.blade.php
//
// Usage in Blade views (each view loads the module via @vite, which sets the
// global as a side effect before DOMContentLoaded):
//   <div id="ticket-qr"></div>
//   <script>
//       EventlyQr.renderQrCode('#ticket-qr', 'BK-4C19A7-1', 104);
//   </script>
//
//   <div id="scanner"></div>
//   <script>
//       EventlyQr.initCameraScanner({
//           elementId: 'scanner',
//           onSuccess: (code) => { ... },
//           onError: (err) => { /* camera denied → show manual-input fallback */ },
//       });
//   </script>
//
// Design conventions (design-evently-home.html):
//   - QR art on ticket cards: white canvas, #0B2545 modules, rounded corners.
//   - Scanner frame: 4/3 dark radial backdrop — the live camera replaces the static art.

let qrCodePromise = null;
let scannerPromise = null;

/**
 * Lazy-load the `qrcode` render library (once).
 *
 * The promise is cached so repeated calls never re-fetch the chunk. Resolves to
 * the module namespace (use `.default ?? mod` at the call site).
 *
 * @returns {Promise<object>}
 */
export function loadQrRender() {
    if (!qrCodePromise) {
        qrCodePromise = import('qrcode');
    }
    return qrCodePromise;
}

/**
 * Lazy-load the `html5-qrcode` scanner library (once).
 *
 * The promise is cached so repeated calls never re-fetch the chunk. Resolves to
 * the module namespace (named export `Html5Qrcode`).
 *
 * @returns {Promise<object>}
 */
export function loadScanner() {
    if (!scannerPromise) {
        scannerPromise = import('html5-qrcode');
    }
    return scannerPromise;
}

/**
 * Show a small muted "QR unavailable" message in the target container.
 * Used when the lazy-loaded library fails (e.g. chunk load error) so users
 * see a graceful message instead of a blank box.
 *
 * @param {HTMLElement} target
 */
function renderQrFallback(target) {
    if (!target || typeof target.replaceChildren !== 'function') {
        return;
    }
    const fallback = document.createElement('span');
    fallback.textContent = 'QR unavailable';
    fallback.style.cssText = [
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'height:100%',
        'font-size:11px',
        'color:var(--muted)',
        'text-align:center',
        'line-height:1.4',
        'padding:4px',
    ].join(';');
    target.replaceChildren(fallback);
}

/**
 * Public fallback helper for views' catch handlers.
 *
 * When a lazy chunk fails to load (network error, bad deploy, ...) the wrapper
 * functions reject; call this in the view's `.catch()` so the QR container
 * shows a small muted message instead of staying blank. It is idempotent —
 * safe to call even if the module already rendered its own fallback.
 *
 * @param {string|HTMLElement} container CSS selector or element to fill.
 */
export function showQrFallback(container) {
    const target = typeof container === 'string' ? document.querySelector(container) : container;
    renderQrFallback(target);
}

/**
 * Render a QR code into a container as a <canvas>.
 *
 * @param {string|HTMLElement} container  CSS selector or element to render into.
 * @param {string} text                   The value to encode (ticket reference, e.g. "BK-4C19A7-1").
 * @param {object} [options]
 * @param {number} [options.size=104]     Canvas width/height in px. Design: 104 (tickets), 60 (booking detail).
 * @param {string} [options.dark='#0B2545'] QR module color (design ink).
 * @param {string} [options.light='#ffffff'] QR background (design white).
 * @param {number} [options.margin=1]     Quiet zone in modules (keeps the design's look at any size).
 * @param {boolean} [options.replace=true] Replace existing container content before appending.
 * @returns {Promise<HTMLCanvasElement>}  The rendered canvas.
 */
export async function renderQrCode(container, text, options = {}) {
    const {
        size = 104,
        dark = '#0B2545',
        light = '#ffffff',
        margin = 1,
        replace = true,
    } = options;

    const target = typeof container === 'string' ? document.querySelector(container) : container;
    if (!target) {
        throw new Error(`[EventlyQr] renderQrCode: container not found (${String(container)}).`);
    }

    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    canvas.style.width = size + 'px';
    canvas.style.height = size + 'px';
    canvas.style.display = 'block';
    canvas.style.background = light;
    canvas.style.borderRadius = '8px'; // design: QR sits on white with 6–8px radius

    let QRCode;
    try {
        const mod = await loadQrRender();
        QRCode = mod.default ?? mod;
    } catch (err) {
        renderQrFallback(target);
        throw err;
    }

    await QRCode.toCanvas(canvas, text, {
        width: size,
        margin,
        color: { dark, light },
        errorCorrectionLevel: 'M',
    });

    if (replace) {
        target.replaceChildren(canvas);
    } else {
        target.appendChild(canvas);
    }

    return canvas;
}

/**
 * Choose a back camera when several are available, else fall back to facingMode.
 *
 * @param {Html5Qrcode} scanner
 * @param {string} facingMode
 * @returns {Promise<string|{facingMode: string}>}
 */
async function pickCamera(scanner, facingMode) {
    try {
        const cameras = await scanner.getCameras();
        if (Array.isArray(cameras) && cameras.length > 0) {
            // Prefer a labelled back/environment camera (e.g. phones exposing "environment back").
            const back = cameras.find((cam) => /back|environment/i.test(cam.label || ''));
            if (back && back.id) {
                return back.id;
            }
            return cameras[0].id;
        }
    } catch (e) {
        // getCameras can reject on desktop without a camera — fall through to facingMode.
    }
    return { facingMode };
}

/**
 * Start a camera-based QR scanner with graceful failure handling.
 *
 * Auto-starts on init (best-effort). If the camera is unavailable/denied the
 * scanner never starts and `onError` is called once so the view can render its
 * fallback ("camera unavailable — use the manual input below").
 *
 * @param {object} config
 * @param {string} [config.elementId='qr-scanner']   Existing element id the video renders into.
 * @param {number} [config.fps=10]                   Scan frames per second.
 * @param {number} [config.qrbox=250]                Square QR scan box size in px.
 * @param {string} [config.facingMode='environment'] Fallback camera when no device ids are exposed.
 * @param {function(string, object):void} [config.onSuccess] Called with (decodedText, result).
 * @param {function(Error):void} [config.onError]     Called once when the camera can't start.
 * @param {function(string):void} [config.onScanError] Per-frame decode errors (no-QR-found noise) — optional.
 * @param {boolean} [config.autoStopOnSuccess=true]  Stop scanning after a successful decode so the
 *                                                   same code can't re-trigger; call `start()` again
 *                                                   to scan the next ticket.
 * @param {boolean} [config.pauseWhenHidden=true]    Stop the camera when the tab loses visibility and
 *                                                   restart it when the user returns (battery/perf &
 *                                                   privacy — avoids a live camera in a background tab).
 * @returns {Promise<{start: function, stop: function, dispose: function, isScanning: boolean, scanner: Html5Qrcode}>}
 */
export async function initCameraScanner(config = {}) {
    const {
        elementId = 'qr-scanner',
        fps = 15,
        qrbox = 200,
        facingMode = 'environment',
        onSuccess = () => {},
        onError = () => {},
        onScanError = () => {},
        autoStopOnSuccess = true,
        pauseWhenHidden = true,
    } = config;

    // Fetch the scanner library only when the scanner is actually started.
    let Html5Qrcode;
    try {
        const scannerModule = await loadScanner();
        Html5Qrcode = scannerModule.Html5Qrcode;
    } catch (e) {
        const err = new Error(
            '[EventlyQr] initCameraScanner: QR scanner library could not be loaded.',
            { cause: e },
        );
        onError(err);
        throw err;
    }

    let scanner;
    try {
        scanner = new Html5Qrcode(elementId, /* verbose */ false);
    } catch (e) {
        const err = new Error(
            `[EventlyQr] initCameraScanner: container #${elementId} not found.`,
        );
        onError(err);
        throw err;
    }

    let processing = false;

    // ── Visibility lifecycle state (audit fix: stop the camera when the tab is
    //    hidden, restart it when the user comes back, so we never keep a live
    //    camera running in a background tab). ───────────────────────────────
    let disposed = false;
    let starting = false;
    let stoppedForTabHide = false; // camera was on when the tab was hidden → resume on return
    let stoppedOnSuccess = false;  // last stop was the success auto-stop → don't resume

    const stop = async () => {
        if (!scanner.isScanning) {
            return;
        }
        try {
            await scanner.stop();
        } catch (e) {
            // stop() rejects when the scanner is already stopped or mid-transition — safe to ignore.
        }
        try {
            scanner.clear();
        } catch (e) {
            // clear() can also throw if the video element was removed — safe to ignore.
        }
    };

    const start = async () => {
        if (starting || scanner.isScanning) {
            return;
        }
        starting = true;
        try {
            const cameraIdOrConfig = await pickCamera(scanner, facingMode);
            await scanner.start(
                cameraIdOrConfig,
                {
                    fps,
                    qrbox: { width: qrbox, height: qrbox },
                    aspectRatio: 4 / 3, // design: camera area is aspect-ratio 4/3
                    disableFlip: false,
                    experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                },
                (decodedText, result) => {
                    if (processing) {
                        return;
                    }
                    processing = true;
                    onSuccess(decodedText, result);
                    if (autoStopOnSuccess) {
                        stoppedOnSuccess = true;
                        stop().finally(() => {
                            processing = false;
                        });
                    } else {
                        processing = false;
                    }
                },
                (errorMessage) => onScanError(errorMessage),
            );
            // Camera is live now — clear the lifecycle flags. The last-start state
            // is authoritative, so a hide/return cycle that ended in a successful
            // start must not count as "stopped for tab hide" anymore.
            stoppedForTabHide = false;
            stoppedOnSuccess = false;
            // Race guard: the tab may have been hidden while the async start was
            // in flight (visibilitychange fired before isScanning flipped true).
            if (document.hidden && pauseWhenHidden) {
                stoppedForTabHide = true;
                await stop();
            }
        } catch (e) {
            onError(e);
        } finally {
            starting = false;
        }
    };

    const handleVisibilityChange = () => {
        if (disposed || !pauseWhenHidden) {
            return;
        }
        if (document.hidden) {
            // Remember that the camera was live so we can resume on return,
            // then stop it to save battery/perf and avoid filming in the background.
            stoppedForTabHide = scanner.isScanning || starting;
            if (scanner.isScanning) {
                stop();
            }
        } else if (stoppedForTabHide && !stoppedOnSuccess) {
            // Back in the tab and the scanner wasn't deliberately stopped after
            // a successful decode → pick up where we left off.
            start();
        }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Auto-start attempt (best-effort — page load, camera request prompt shows here).
    await start();

    const dispose = async () => {
        disposed = true;
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        await stop();
    };

    return { start, stop, dispose, isScanning: () => scanner.isScanning, scanner };
}

// Global for Blade inline <script> usage. The 3 QR views load this module via
// @vite (not app.js), so this side effect runs only on pages that need it.
window.EventlyQr = { renderQrCode, initCameraScanner, showQrFallback };
