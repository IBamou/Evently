<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingService
{
    /**
     * Create a new booking (REQ-BK-001..013).
     *
     * Handles: bookable event check, same-event/active/sales-window checks,
     * quantity min/max/available, server-side pricing + snapshots, reference
     * generation, free=confirmed+instant tickets, paid=pending+15min expiry
     * + payment record, idempotency key, transaction+lockForUpdate, 409
     * insufficient_capacity.
     *
     * @param  array{event_id: int, items: array<int, array{ticket_type_id: int, quantity: int}>, idempotency_key?: string}  $data
     */
    public function create(User $user, array $data): Booking
    {
        $event = Event::findOrFail($data['event_id']);

        if (! $event->isBookable()) {
            throw new RuntimeException('This event is not available for booking.');
        }

        // Idempotency key fast-path (REQ-BK-011): cheap check before locking.
        $existing = $this->findExistingBooking($user, $event, $data);

        if ($existing) {
            return $existing->loadMissing(['items', 'tickets', 'payments', 'event']);
        }

        $items = collect($data['items']);
        $ticketTypeIds = $items->pluck('ticket_type_id');

        $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)->get()->keyBy('id');

        // Validate all ticket types belong to the same event and are bookable
        foreach ($ticketTypes as $tt) {
            if ($tt->event_id !== $event->id) {
                throw new RuntimeException("Ticket type '{$tt->name}' does not belong to this event.");
            }

            if (! $tt->isSalesOpen()) {
                throw new RuntimeException("Sales for '{$tt->name}' are currently closed.");
            }
        }

        // Validate quantities
        foreach ($items as $index => $item) {
            $tt = $ticketTypes->get($item['ticket_type_id']);

            if (! $tt) {
                throw new RuntimeException('Ticket type not found.');
            }

            if ($item['quantity'] < $tt->min_per_booking) {
                throw new RuntimeException("Minimum quantity for '{$tt->name}' is {$tt->min_per_booking}.");
            }

            if ($item['quantity'] > $tt->max_per_booking) {
                throw new RuntimeException("Maximum quantity for '{$tt->name}' is {$tt->max_per_booking}.");
            }
        }

        try {
            $booking = DB::transaction(function () use ($user, $event, $items, $data) {
                // Lock ticket type rows FOR UPDATE (REQ-BK-009)
                $lockedTypes = TicketType::whereIn('id', $items->pluck('ticket_type_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Authoritative idempotency check inside the transaction:
                // concurrent submits carrying the same key serialize on the
                // ticket type locks above, so a duplicate can never slip in.
                $existing = $this->findExistingBooking($user, $event, $data);

                if ($existing) {
                    return $existing;
                }

                // Check availability using booking_items (capacity source of truth)
                foreach ($items as $item) {
                    $tt = $lockedTypes->get($item['ticket_type_id']);

                    if (! $tt) {
                        throw new RuntimeException('Ticket type not found.');
                    }

                    $allocated = $tt->bookingItems()
                        ->whereHas('booking', fn ($q) => $q->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value]))
                        ->sum('quantity');

                    $available = $tt->quantity - $allocated;

                    if ($item['quantity'] > $available) {
                        throw new RuntimeException("insufficient_capacity:{$tt->id}");
                    }
                }

                // Build booking items with server-side pricing
                $subtotal = 0;
                $bookingItems = [];

                foreach ($items as $item) {
                    $tt = $lockedTypes->get($item['ticket_type_id']);

                    if (! $tt) {
                        throw new RuntimeException('Ticket type not found.');
                    }

                    $lineTotal = (float) $tt->price * $item['quantity'];
                    $subtotal += $lineTotal;
                    $bookingItems[] = [
                        'ticket_type_id' => $tt->id,
                        'ticket_name' => $tt->name,
                        'unit_price' => $tt->price,
                        'quantity' => $item['quantity'],
                        'line_total' => $lineTotal,
                    ];
                }

                $isFree = bccomp((string) $subtotal, '0', 2) === 0;

                $booking = new Booking([
                    'reference' => Booking::generateReference(),
                    'idempotency_key' => $data['idempotency_key'] ?? null,
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'status' => $isFree ? BookingStatus::Confirmed : BookingStatus::Pending,
                    'subtotal' => $subtotal,
                    'fees' => 0,
                    'total' => $subtotal,
                    'currency' => config('app.currency', 'MAD'),
                    'expires_at' => $isFree ? null : now()->addMinutes(15),
                    'confirmed_at' => $isFree ? now() : null,
                ]);
                $booking->save();

                foreach ($bookingItems as $bi) {
                    $booking->items()->create($bi);
                }

                if (! $isFree) {
                    $booking->payments()->create([
                        'provider' => 'manual',
                        'status' => PaymentStatus::Pending,
                        'amount' => $subtotal,
                        'currency' => config('app.currency', 'MAD'),
                    ]);
                }

                // Free bookings: generate tickets immediately (REQ-BK-007)
                if ($isFree) {
                    $this->generateTickets($booking);
                }

                return $booking;
            });

            return $booking->load(['items', 'tickets', 'payments', 'event']);
        } catch (QueryException $e) {
            // Unique index backstop: a re-submitted key — even outside the
            // 15-minute window — must resolve to the existing booking instead
            // of 500ing on a duplicate key violation.
            if ($e->getCode() === 23000 && ! empty($data['idempotency_key'])) {
                $existing = $user->bookings()
                    ->where('event_id', $event->id)
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();

                if ($existing) {
                    return $existing->load(['items', 'tickets', 'payments', 'event']);
                }
            }

            throw $e;
        } catch (RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'insufficient_capacity:')) {
                $ttId = (int) explode(':', $e->getMessage())[1];
                $tt = $ticketTypes->get($ttId);

                if (! $tt) {
                    throw new RuntimeException('Ticket type not found.', 409);
                }

                throw new RuntimeException(
                    "Not enough tickets available for '{$tt->name}'.",
                    409
                );
            }

            throw $e;
        }
    }

    /**
     * Find a recent duplicate booking for the same idempotency key
     * (REQ-BK-011).
     *
     * Primary: exact persisted key. Fallback (bookings created before the key
     * was persisted): same event within 15 minutes with the exact same
     * selection (same ticket type count, same types, same total quantity).
     *
     * @param  array{event_id: int, items: array<int, array{ticket_type_id: int, quantity: int}>, idempotency_key?: string}  $data
     */
    private function findExistingBooking(User $user, Event $event, array $data): ?Booking
    {
        if (empty($data['idempotency_key'])) {
            return null;
        }

        $ids = collect($data['items'])->pluck('ticket_type_id');
        $totalQty = collect($data['items'])->sum('quantity');

        return $user->bookings()
            ->where('event_id', $event->id)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->where(function ($q) use ($data, $ids, $totalQty) {
                $q->where('idempotency_key', $data['idempotency_key'])
                    ->orWhere(function ($heuristic) use ($ids, $totalQty) {
                        $heuristic->whereNull('idempotency_key')
                            ->has('items', '=', $ids->count())
                            ->whereExists(function ($q) use ($ids, $totalQty) {
                                $q->selectRaw('1')
                                    ->from('booking_items')
                                    ->whereColumn('booking_items.booking_id', 'bookings.id')
                                    ->whereIn('ticket_type_id', $ids)
                                    ->groupBy('booking_items.booking_id')
                                    ->havingRaw('SUM(quantity) = ?', [$totalQty]);
                            });
                    });
            })
            ->first();
    }

    /**
     * Cancel a booking and release associated tickets and payments (REQ-CN-001..008).
     */
    public function cancel(Booking $booking): Booking
    {
        // Idempotent: already cancelled/expired → return as-is (REQ-CN-008)
        if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Expired])) {
            return $booking->load(['items', 'tickets', 'payments', 'event']);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $booking->tickets()
                ->where('status', TicketStatus::Valid)
                ->update([
                    'status' => TicketStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);

            // Cancel pending payments (REQ-CN-007)
            $booking->payments()
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Cancelled]);
        });

        return $booking->refresh()->load(['items', 'tickets', 'payments', 'event']);
    }

    /**
     * Confirm payment for a pending booking (REQ-PY-002..003).
     */
    public function confirmPayment(Booking $booking): Booking
    {
        if ($booking->status === BookingStatus::Confirmed) {
            return $booking->loadMissing(['items', 'tickets', 'payments', 'event']);
        }

        $confirmed = DB::transaction(function () use ($booking) {
            // Re-read the booking under a row lock so the status check is
            // authoritative — closes the race with ExpireBookings where a
            // stale in-memory status could confirm an already-expired booking.
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === BookingStatus::Confirmed) {
                return $locked;
            }

            if ($locked->status !== BookingStatus::Pending) {
                throw new RuntimeException('Only pending bookings can be confirmed.', 409);
            }

            // Never confirm a booking whose payment window has closed.
            if ($locked->expires_at !== null && $locked->expires_at->isPast()) {
                $locked->update(['status' => BookingStatus::Expired]);

                $locked->payments()
                    ->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Cancelled]);

                return $locked;
            }

            $locked->update([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => now(),
                'expires_at' => null,
            ]);

            $payment = $locked->payments()
                ->where('status', PaymentStatus::Pending)
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => PaymentStatus::Succeeded,
                    'paid_at' => now(),
                ]);
            } else {
                $locked->payments()->create([
                    'provider' => 'manual',
                    'status' => PaymentStatus::Succeeded,
                    'amount' => $locked->total,
                    'currency' => $locked->currency,
                    'paid_at' => now(),
                ]);
            }

            // Generate tickets for each booking item (REQ-TK-001)
            $this->generateTickets($locked);

            return $locked;
        });

        if ($confirmed->status === BookingStatus::Expired) {
            throw new RuntimeException('This booking has expired.', 409);
        }

        return $confirmed->refresh()->load(['items', 'tickets', 'payments', 'event']);
    }

    /**
     * Generate tickets for all items in a confirmed booking.
     */
    protected function generateTickets(Booking $booking): void
    {
        foreach ($booking->items as $item) {
            // Idempotent: skip if tickets already exist (REQ-TK-005)
            if ($item->tickets()->exists()) {
                continue;
            }

            for ($i = 0; $i < $item->quantity; $i++) {
                $booking->tickets()->create([
                    'booking_item_id' => $item->id,
                    'ticket_type_id' => $item->ticket_type_id,
                    'user_id' => $booking->user_id,
                    'event_id' => $booking->event_id,
                    'code' => Ticket::generateCode(),
                    'status' => TicketStatus::Valid,
                    'issued_at' => now(),
                ]);
            }
        }
    }
}
