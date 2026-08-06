<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreTicketTypeRequest;
use App\Http\Requests\Booking\UpdateTicketTypeRequest;
use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TicketTypeController extends Controller
{
    /**
     * List all ticket types for an event (including inactive).
     */
    public function index(Event $event): View
    {
        $this->authorize('update', $event);

        // FIX 1: Eager-load allocated quantity via withSum instead of N+1 availableQuantity() calls.
        $ticketTypes = $event->ticketTypes()
            ->withTrashed()
            ->withSum([
                'bookingItems as allocated_quantity' => fn ($q) => $q->whereIn('booking_id', Booking::query()
                    ->select('id')
                    ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])),
            ], 'quantity')
            ->get();

        return view('organizer.ticket-types.index', compact('event', 'ticketTypes'));
    }

    /**
     * Show the create form.
     */
    public function create(Event $event): View
    {
        $this->authorize('update', $event);

        return view('organizer.ticket-types.create', compact('event'));
    }

    /**
     * Store a new ticket type.
     */
    public function store(StoreTicketTypeRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validated();
        $validated['max_per_booking'] = $request->input('max_per_booking', $request->input('quantity'));
        $validated['currency'] = config('app.currency', 'MAD');

        $event->ticketTypes()->create($validated);

        return redirect()->route('organizer.ticket-types.index', $event)
            ->with('success', 'Ticket type created.');
    }

    /**
     * Show the edit form.
     */
    public function edit(Event $event, TicketType $ticketType): View
    {
        $this->authorize('update', $event);
        $this->ensureOwnership($event, $ticketType);

        return view('organizer.ticket-types.edit', compact('event', 'ticketType'));
    }

    /**
     * Update the ticket type.
     */
    public function update(UpdateTicketTypeRequest $request, Event $event, TicketType $ticketType): RedirectResponse
    {
        $this->authorize('update', $event);
        $this->ensureOwnership($event, $ticketType);

        $ticketType->update($request->validated());

        return redirect()->route('organizer.ticket-types.index', $event)
            ->with('success', 'Ticket type updated.');
    }

    /**
     * Soft-delete a ticket type (only if no bookings).
     */
    public function destroy(Event $event, TicketType $ticketType): RedirectResponse
    {
        $this->authorize('update', $event);
        $this->ensureOwnership($event, $ticketType);

        if ($ticketType->bookingItems()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete ticket type with existing bookings. Deactivate it instead.');
        }

        $ticketType->delete();

        return redirect()->route('organizer.ticket-types.index', $event)
            ->with('success', 'Ticket type deleted.');
    }

    /**
     * Activate a ticket type.
     */
    public function activate(Event $event, TicketType $ticketType): RedirectResponse
    {
        $this->authorize('update', $event);
        $this->ensureOwnership($event, $ticketType);

        $ticketType->update(['is_active' => true]);

        return redirect()->route('organizer.ticket-types.index', $event)
            ->with('success', 'Ticket type activated.');
    }

    /**
     * Deactivate a ticket type.
     */
    public function deactivate(Event $event, TicketType $ticketType): RedirectResponse
    {
        $this->authorize('update', $event);
        $this->ensureOwnership($event, $ticketType);

        $ticketType->update(['is_active' => false]);

        return redirect()->route('organizer.ticket-types.index', $event)
            ->with('success', 'Ticket type deactivated.');
    }

    /**
     * Defense-in-depth: ensure the ticket type belongs to the given event.
     */
    private function ensureOwnership(Event $event, TicketType $ticketType): void
    {
        abort_unless($ticketType->event_id === $event->id, 404);
    }
}
