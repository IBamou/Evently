<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     *
     * Owner, organizer of the event, or admin can view.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($booking->user_id === $user->id) {
            return true;
        }

        // Organizer of the event
        return $booking->event && $booking->event->organizer_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the booking.
     *
     * Owner or admin can cancel.  The isCancellable() check is done
     * in the controller/service layer.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $booking->user_id === $user->id;
    }
}
