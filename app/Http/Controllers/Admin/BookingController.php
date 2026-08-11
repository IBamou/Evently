<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Traits\Queryable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    use Queryable;

    /**
     * List all bookings (admin).
     */
    public function index(Request $request): View
    {
        $query = Booking::query()
            ->with(['user', 'event', 'items.ticketType'])
            ->withCount('tickets');

        $this->applyFilters($query, $request, [
            'status' => 'status',
        ]);

        $this->applySearch($query, $request, [
            'reference',
            fn ($q, $search) => $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")),
            fn ($q, $search) => $q->orWhereHas('event', fn ($e) => $e->where('title', 'like', "%{$search}%")),
        ]);

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        $filters = $request->only(['status', 'search']);

        return view('admin.bookings', compact('bookings', 'filters'));
    }

    /**
     * Cancel any booking (admin, REQ-CN-009).
     */
    public function cancel(Booking $booking, BookingService $service): RedirectResponse
    {
        if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Expired])) {
            return redirect()->back()->with('info', 'Booking is already '.$booking->status->value.'.');
        }

        $service->cancel($booking);

        return redirect()->back()->with('success', 'Booking cancelled.');
    }
}
