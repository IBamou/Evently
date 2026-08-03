import './bootstrap';

// NOTE: Alpine is NOT imported/started here — Livewire 4 bundles its own Alpine
// instance (loaded via @livewireScripts). Starting a second one causes the
// "Detected multiple instances of Alpine running" console warning.
//
// QR rendering/scanner code is NOT imported here anymore. qr.js + its two
// libraries (qrcode, html5-qrcode) used to ship ~410 KB raw to EVERY page.
// Only 3 views need them — those views load `resources/js/qr.js` on demand via
// @vite, and qr.js itself dynamically imports each library only when a QR
// feature actually runs (see resources/js/qr.js for the wiring).
