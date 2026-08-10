<?php

namespace App\Http\Controllers\User;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class BookingController extends Controller
{
    /**
     * Show the checkout page with event summary + active ticket types + availability.
     */
    public function checkout(): View
    {
        $eventId = (int) request()->query('event');
        $event = Event::query()
            ->published()
            ->with(['organizer:id,name', 'category:id,name,slug'])
            ->findOrFail($eventId);

        // FIX 1: Single query for all ticket types with allocated quantity via withSum
        // instead of N+1 calls to availableQuantity() per row.
        $ticketTypes = $event->ticketTypes()
            ->where('is_active', true)
            ->withSum([
                'bookingItems as allocated_quantity' => fn ($q) => $q->whereIn('booking_id', Booking::query()
                    ->select('id')
                    ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])),
            ], 'quantity')
            ->get()
            ->map(fn ($tt) => [
                'id' => $tt->id,
                'name' => $tt->name,
                'description' => $tt->description,
                'price' => $tt->price,
                'currency' => $tt->currency,
                'min_per_booking' => $tt->min_per_booking,
                'max_per_booking' => $tt->max_per_booking,
                // availableQuantity() uses the pre-loaded aggregate (fast path).
                'available_quantity' => $tt->availableQuantity(),
                'is_sales_open' => $tt->isSalesOpen(),
            ]);

        // Quantities preselected on the event page (qty[ticket_type_id]=count).
        $initialQty = collect(request()->query('qty', []))
            ->mapWithKeys(fn ($qty, $ttId) => [(int) $ttId => max(0, (int) $qty)])
            ->all();

        return view('bookings.checkout', compact('event', 'ticketTypes', 'initialQty'));
    }

    /**
     * Store a new booking — with optional mock payment confirmation.
     *
     * Flow:
     * 1. FormRequest validates items + optional payment fields (required when total > 0).
     * 2. Mock card business rules validated in FormRequest (Visa test card, future expiry).
     * 3. BookingService::create() creates the booking (free → confirmed, paid → pending).
     * 4. If paid + valid mock card submitted → confirmPayment() immediately (confirmed + tickets).
     * 5. If paid + no card → stays pending (user can confirm later via bookings.show).
     */
    public function store(StoreBookingRequest $request, BookingService $service): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $validated = $request->validated();

            /** @var array{event_id: int, items: array<int, array{ticket_type_id: int, quantity: int}>, idempotency_key?: string, payment?: array{card_number?: string, expiry?: string, cvc?: string}} $validated */
            $booking = $service->create($user, $validated);

            // Mock payment: if paid event with valid card submitted, confirm immediately.
            // Only when the mock confirmation gate is enabled (config/payments.php).
            $total = (float) $booking->total;
            $cardNumber = $validated['payment']['card_number'] ?? null;
            $hasPayment = $cardNumber !== null && $cardNumber !== '';

            if ($total > 0 && $hasPayment) {
                if (! config('payments.mock_confirm')) {
                    // Real payment integration is not wired up: the booking
                    // stays pending rather than being falsely confirmed.
                    return redirect()->route('bookings.show', $booking)
                        ->with('info', 'Booking created. Payment confirmation is not available in this environment; your booking is pending.');
                }

                // Card format + business rules already validated by StoreBookingRequest.
                // Confirm payment: update status → Confirmed, payment → Succeeded, issue tickets.
                $service->confirmPayment($booking);

                return redirect()->route('bookings.show', $booking)
                    ->with('success', 'Payment confirmed! Your tickets are ready. Reference: '.$booking->reference);
            }

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Booking created successfully! Reference: '.$booking->reference);
        } catch (RuntimeException $e) {
            $status = $e->getCode() === 409 ? 409 : 422;

            return redirect()->back()
                ->withInput()
                ->withErrors(['items' => $e->getMessage()])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * List user's bookings.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $user->bookings()
            ->with(['event', 'items'])
            ->withCount('tickets');

        if (request()->filled('status')) {
            $query->where('status', request()->input('status'));
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        // FIX 2: Single groupBy query instead of 4 separate COUNT queries.
        $statusCounts = $user->bookings()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'all' => (int) $statusCounts->sum(),
            'confirmed' => (int) ($statusCounts[BookingStatus::Confirmed->value] ?? 0),
            'pending' => (int) ($statusCounts[BookingStatus::Pending->value] ?? 0),
            'cancelled' => (int) (($statusCounts[BookingStatus::Cancelled->value] ?? 0) + ($statusCounts[BookingStatus::Expired->value] ?? 0)),
        ];

        return view('bookings.index', compact('bookings', 'counts'));
    }

    /**
     * Show a single booking.
     */
    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load(['items', 'tickets.ticketType', 'payments', 'event']);

        /** @var User $user */
        $user = Auth::user();

        $canCancel = $user->can('cancel', $booking) && $booking->isCancellable();
        $canPay = $user->can('confirm', $booking) && $booking->status === BookingStatus::Pending;

        return view('bookings.show', compact('booking', 'canCancel', 'canPay'));
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Booking $booking, BookingService $service): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        if (! $booking->isCancellable()) {
            return redirect()->back()->with('error', 'This booking cannot be cancelled.');
        }

        try {
            $service->cancel($booking);

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Booking cancelled successfully.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Confirm payment for a pending booking (mock).
     *
     * Only reachable while the mock confirmation gate is enabled
     * (config/payments.php). Once a real provider is integrated, this
     * endpoint must be replaced by a provider callback.
     */
    public function confirmPayment(Booking $booking, BookingService $service): RedirectResponse
    {
        $this->authorize('confirm', $booking);

        if (! config('payments.mock_confirm')) {
            abort(403, 'Mock payment confirmation is disabled.');
        }

        try {
            $service->confirmPayment($booking);

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Payment confirmed! Your tickets are ready.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
