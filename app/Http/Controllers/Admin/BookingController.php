<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * List all bookings (admin).
     */
    public function index(Request $request): View
    {
        $query = Booking::query()
            ->with(['user', 'event', 'items.ticketType'])
            ->withCount('tickets');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('event', function ($e) use ($search) {
                        $e->where('title', 'like', '%'.$search.'%');
                    });
            });
        }

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
