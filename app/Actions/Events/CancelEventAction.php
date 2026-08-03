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
            // so nothing stays orphaned (REQ-CN-007 / REQ-CN-010).
            $pendingIds = $event->bookings()
                ->where('status', BookingStatus::Pending->value)
                ->pluck('id');

            $event->bookings()
                ->whereIn('id', $pendingIds)
                ->update(['status' => BookingStatus::Expired->value, 'updated_at' => now()]);

            if ($pendingIds->isNotEmpty()) {
                DB::table('payments')
                    ->whereIn('booking_id', $pendingIds)
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Cancelled->value]);
            }

            // Confirmed bookings → cancelled
            $event->bookings()
                ->where('status', BookingStatus::Confirmed->value)
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            // All valid tickets → cancelled
            $event->tickets()
                ->where('status', TicketStatus::Valid->value)
                ->update([
                    'status' => TicketStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);
        });

        return $event;
    }
}
