<?php

namespace App\Actions\Events;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CancelEventAction
{
    /**
     * Cancel a published event and cascade to bookings/tickets (REQ-CN-011).
     *
     * - pending bookings → expired
     * - confirmed bookings → cancelled
     * - tickets → cancelled
     * - payments preserved for refund tracking
     * - no new bookings accepted (event status check blocks creation)
     */
    public function __invoke(Event $event): Event
    {
        if (! $event->status->isPublished()) {
            throw new RuntimeException('Only published events can be cancelled.');
        }

        DB::transaction(function () use ($event) {
            $event->forceFill(['status' => EventStatus::Cancelled])->save();

            // Pending bookings → expired, and cancel their pending payments
            // so nothing stays orphaned (REQ-CN-007 / REQ-CN-010). The update
            // is re-guarded on status: a booking confirmed after the snapshot
            // pluck is never overwritten to expired.
            $snapshotPendingIds = $event->bookings()
                ->where('status', BookingStatus::Pending->value)
                ->pluck('id');

            $event->bookings()
                ->whereIn('id', $snapshotPendingIds)
                ->where('status', BookingStatus::Pending->value)
                ->update(['status' => BookingStatus::Expired->value, 'updated_at' => now()]);

            // Confirmed bookings → cancelled
            $event->bookings()
                ->where('status', BookingStatus::Confirmed->value)
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            // Only cancel tickets belonging to bookings that are now
            // cancelled/expired — a booking confirmed mid-flight keeps its
            // valid tickets (the event cascade never touches them).
            $event->tickets()
                ->where('status', TicketStatus::Valid->value)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('bookings')
                        ->whereColumn('bookings.id', 'tickets.booking_id')
                        ->whereIn('bookings.status', [BookingStatus::Cancelled->value, BookingStatus::Expired->value]);
                })
                ->update([
                    'status' => TicketStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);

            // Cancel pending payments of bookings that are now
            // cancelled/expired (REQ-CN-007).
            DB::table('payments')
                ->where('status', PaymentStatus::Pending->value)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('bookings')
                        ->whereColumn('bookings.id', 'payments.booking_id')
                        ->whereIn('bookings.status', [BookingStatus::Cancelled->value, BookingStatus::Expired->value]);
                })
                ->update(['status' => PaymentStatus::Cancelled->value]);
        });

        return $event;
    }
}
