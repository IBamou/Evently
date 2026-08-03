// resources/js/booking.js — shared booking-widget helpers (single source of truth).
//
// Previously these helpers were duplicated inline in two Blade views:
//   - resources/views/events/show.blade.php     (booking widget JS)
//   - resources/views/bookings/checkout.blade.php (checkout JS)
//
// This module is loaded via @vite ONLY on those two pages (same pattern as
// resources/js/qr.js) and exposes `window.EventlyBooking` for the inline
// scripts to call. It has no dependencies, so it ships as a tiny chunk.
//
// Design conventions (design-evently-home.html):
//   - money(): 0 → "Free", otherwise "Math.round(n) <currency>" (MAD default).
//   - selectionKey(): djb2-style hash of the "ttid:qty" parts → deterministic,
//     selection-derived idempotency key "e{eventId}:{hash36}" (REQ-BK-011).

/**
 * Format a price the design way: 0 → "Free", else "rounded <currency>".
 *
 * @param {number} n The (possibly fractional) amount in the event currency.
 * @param {string} [currency='MAD'] Currency label appended to positive values.
 * @returns {string}
 */
export function money(n, currency) {
    return n === 0 ? 'Free' : Math.round(n).toLocaleString('en-US') + ' ' + (currency || 'MAD');
}

/**
 * Deterministic idempotency key derived from a ticket selection.
 *
 * The same selection (same ticket-type/quantity parts) always yields the same
 * key, so a double-submit of an identical order is a no-op server-side.
 * Changing the selection produces a new key, letting the user place a
 * different order. Scoped per event via `eventId` (REQ-BK-011).
 *
 * @param {number|string} eventId The event id — scopes the key per event.
 * @param {string[]} parts Array of "ticketTypeId:qty" strings (sorted internally).
 * @returns {string} Key like "e3:k1x9z".
 */
export function selectionKey(eventId, parts) {
    var h = 5381;
    var s = parts.slice().sort().join(',');
    for (var i = 0; i < s.length; i++) {
        h = ((h << 5) + h + s.charCodeAt(i)) >>> 0;
    }
    return 'e' + eventId + ':' + h.toString(36);
}

// Global for Blade inline <script> usage — set as a side effect so the two
// booking views can call the helpers without an explicit import.
window.EventlyBooking = { money, selectionKey };
