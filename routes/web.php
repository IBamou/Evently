<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Organizer\BookingController as OrganizerBookingController;
use App\Http\Controllers\Organizer\CheckInController;
use App\Http\Controllers\Organizer\EventAiController;
use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Organizer\TicketTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\User\BookingController as UserBookingController;
use App\Http\Controllers\User\TicketController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public routes ──
Route::get('/', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [PublicEventController::class, 'show'])->name('events.show');

Route::get('/dashboard', function () {
    $role = Auth::user()->role;

    // Send every authenticated role to its primary workspace.
    return match ($role) {
        UserRole::User => redirect()->route('events.index'),
        UserRole::Organizer => redirect()->route('organizer.dashboard'),
        UserRole::Admin => redirect()->route('admin.dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Booking routes (authenticated users) ──
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/bookings/checkout', [UserBookingController::class, 'checkout'])->name('bookings.checkout');
    Route::post('/bookings', [UserBookingController::class, 'store'])->name('bookings.store')->middleware('throttle:10,1');
    Route::get('/bookings', [UserBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [UserBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [UserBookingController::class, 'cancel'])->name('bookings.cancel')->middleware('throttle:10,1');
    Route::post('/bookings/{booking}/confirm-payment', [UserBookingController::class, 'confirmPayment'])->name('bookings.confirm-payment')->middleware('throttle:10,1');

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
});

// ── Organizer routes ──
Route::middleware(['auth', 'role:organizer'])->prefix('organizer')->name('organizer.')->group(function () {
    Route::get('dashboard', [OrganizerEventController::class, 'dashboard'])->name('dashboard');
    Route::get('events', [OrganizerEventController::class, 'index'])->name('events.index');
    Route::get('events/create', [OrganizerEventController::class, 'create'])->name('events.create');
    Route::post('events', [OrganizerEventController::class, 'store'])->name('events.store');
    Route::get('events/{event}/edit', [OrganizerEventController::class, 'edit'])->name('events.edit');
    Route::patch('events/{event}', [OrganizerEventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [OrganizerEventController::class, 'destroy'])->name('events.destroy');
    Route::post('events/{event}/cancel', [OrganizerEventController::class, 'cancel'])->name('events.cancel');
    Route::post('events/{event}/submit', [OrganizerEventController::class, 'submit'])->name('events.submit');

    // Ticket types (scopeBindings ensures ticketType must belong to the event in the URL)
    Route::get('events/{event}/ticket-types', [TicketTypeController::class, 'index'])->name('ticket-types.index');
    Route::get('events/{event}/ticket-types/create', [TicketTypeController::class, 'create'])->name('ticket-types.create');
    Route::post('events/{event}/ticket-types', [TicketTypeController::class, 'store'])->name('ticket-types.store');
    Route::get('events/{event}/ticket-types/{ticketType}/edit', [TicketTypeController::class, 'edit'])->name('ticket-types.edit')->scopeBindings();
    Route::put('events/{event}/ticket-types/{ticketType}', [TicketTypeController::class, 'update'])->name('ticket-types.update')->scopeBindings();
    Route::delete('events/{event}/ticket-types/{ticketType}', [TicketTypeController::class, 'destroy'])->name('ticket-types.destroy')->scopeBindings();
    Route::post('events/{event}/ticket-types/{ticketType}/activate', [TicketTypeController::class, 'activate'])->name('ticket-types.activate')->scopeBindings();
    Route::post('events/{event}/ticket-types/{ticketType}/deactivate', [TicketTypeController::class, 'deactivate'])->name('ticket-types.deactivate')->scopeBindings();

    // Organizer bookings
    Route::get('events/{event}/bookings', [OrganizerBookingController::class, 'index'])->name('bookings.index');

    // AI Event Copilot
    Route::prefix('ai')->name('ai.')->middleware('ai.enabled')->group(function () {
        Route::post('event-drafts', [EventAiController::class, 'generateDraft'])->name('event-drafts');
        Route::post('event-fields/transform', [EventAiController::class, 'transformField'])->name('event-fields.transform');
        Route::get('generations/{generation:public_id}', [EventAiController::class, 'status'])->name('generations.status');
        Route::post('generations/{generation:public_id}/feedback', [EventAiController::class, 'recordFeedback'])->name('generations.feedback');
    });
});

// ── Check-in (organizer + admin can scan doors; EventPolicy::update covers both) ──
Route::middleware(['auth', 'role:organizer,admin'])->prefix('organizer')->name('organizer.')->group(function () {
    Route::get('check-in', [CheckInController::class, 'picker'])->name('check-in.picker');
    Route::get('events/{event}/check-in', [CheckInController::class, 'index'])->name('check-in.index');
    Route::post('events/{event}/check-in', [CheckInController::class, 'scan'])->name('check-in.scan')->middleware('throttle:60,1');
});

// ── Admin routes ──
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Platform dashboard (design odash as admin: "Platform dashboard").
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('events', [AdminEventController::class, 'index'])->name('events.index');
    Route::post('events/{event}/publish', [AdminEventController::class, 'publish'])->name('events.publish');
    Route::post('events/{event}/reject', [AdminEventController::class, 'reject'])->name('events.reject');
    Route::post('events/{event}/cancel', [AdminEventController::class, 'cancel'])->name('events.cancel');
    Route::delete('events/{event}', [AdminEventController::class, 'destroy'])->name('events.destroy');
    Route::post('events/{event}/restore', [AdminEventController::class, 'restore'])->name('events.restore')->withTrashed();

    Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // Admin bookings
    Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');

    // Admin tickets
    Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');

    // Admin payments
    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
});

require __DIR__.'/auth.php';
