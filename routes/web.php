<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// ── UI preview routes (static views only — temporary, per user directive) ──
Route::prefix('preview')->group(function () {
    Route::view('/events', 'home');
    Route::view('/events/atlantis-live', 'events.show');
    Route::view('/ubookings', 'bookings.index');
    Route::view('/booking', 'bookings.show');
    Route::view('/tickets', 'tickets.index');
    Route::view('/profile', 'profile.edit');
    Route::view('/odash', 'organizer.dashboard');
    Route::view('/oevents', 'organizer.events');
    Route::view('/create', 'organizer.events.create');
    Route::view('/scan', 'organizer.scan');
    Route::view('/admin', 'admin.index');
    Route::view('/login', 'auth.login');
    Route::view('/register', 'auth.register');
    Route::view('/forgot', 'auth.forgot-password');
});
