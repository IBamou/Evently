<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public routes ──
Route::get('/', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [PublicEventController::class, 'show'])->name('events.show');

Route::get('/dashboard', function () {
    $role = Auth::user()->role;

    // Home page per role: users land on their profile, organizers on their
    // dashboard and admins on the admin console.
    return match ($role) {
        UserRole::Organizer => redirect()->route('organizer.dashboard'),
        UserRole::Admin => redirect()->route('admin.events.index'),
        default => redirect()->route('profile.edit'),
    };
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

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
});

// ── Admin routes ──
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
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
});

// ── UI preview routes (static views only — temporary, per user directive) ──
Route::prefix('preview')->group(function () {
    Route::view('/ubookings', 'bookings.index');
    Route::view('/booking', 'bookings.show');
    Route::view('/tickets', 'tickets.index');
    Route::view('/scan', 'organizer.scan');
});
