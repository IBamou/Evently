<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:expire';

    /**
     * The console command description.
     */
    protected $description = 'Expire pending bookings past their expiry window (REQ-CN-010)';

    /**
     * Execute the console command.
     *
     * Serialization with BookingService::confirmPayment(): both sides lock the
     * same authoritative booking rows (FOR UPDATE) before deciding state, so a
     * booking can never be both expired and confirmed. If confirmation gets
     * the lock first, the booking is confirmed (expires_at = NULL) and drops
     * out of the guarded re-check below; if expiration gets the lock first,
     * the booking becomes expired and confirmPayment() rejects it with 409.
     *
     * The reported count is the number of rows that truly transitioned to
     * expired — the UPDATE's affected-row count — never the candidate count.
     */
    public function handle(): int
    {
        $count = 0;

        DB::table('bookings')
            ->where('status', BookingStatus::Pending->value)
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->chunkById(500, function ($candidates) use (&$count) {
                DB::transaction(function () use ($candidates, &$count) {
                    // Lock the candidate rows FOR UPDATE, then re-check both
                    // guards (still pending, window still closed) under the
                    // lock. A booking confirmed between the chunk read and
                    // this transaction holds status=confirmed + expires_at=NULL,
                    // so it drops out here and is never overwritten.
                    $locked = DB::table('bookings')
                        ->whereIn('id', $candidates->pluck('id'))
                        ->lockForUpdate()
                        ->where('status', BookingStatus::Pending->value)
                        ->where('expires_at', '<', now())
                        ->get();

                    if ($locked->isEmpty()) {
                        return;
                    }

                    $candidateIds = $locked->pluck('id');

                    // The UPDATE re-applies both guards as a backstop, so it can
                    // never overwrite a booking that flipped to confirmed after
                    // the lock read. Affected rows = true transitions (the
                    // reported count must never include candidates that were
                    // confirmed in the meantime).
                    $transitioned = DB::table('bookings')
                        ->whereIn('id', $candidateIds)
                        ->where('status', BookingStatus::Pending->value)
                        ->where('expires_at', '<', now())
                        ->update([
                            'status' => BookingStatus::Expired->value,
                            'updated_at' => now(),
                        ]);

                    $count += $transitioned;

                    if ($transitioned === 0) {
                        return;
                    }

                    // Cascade only against the rows that truly transitioned.
                    $expiredIds = DB::table('bookings')
                        ->whereIn('id', $candidateIds)
                        ->where('status', BookingStatus::Expired->value)
                        ->pluck('id');

                    // Cancel valid tickets for expired bookings
                    DB::table('tickets')
                        ->whereIn('booking_id', $expiredIds)
                        ->where('status', TicketStatus::Valid->value)
                        ->update([
                            'status' => TicketStatus::Cancelled->value,
                            'cancelled_at' => now(),
                        ]);

                    // Cancel pending payments for expired bookings
                    DB::table('payments')
                        ->whereIn('booking_id', $expiredIds)
                        ->where('status', PaymentStatus::Pending->value)
                        ->update(['status' => PaymentStatus::Cancelled->value]);
                });
            });

        if ($count === 0) {
            $this->info('No bookings to expire.');

            return Command::SUCCESS;
        }

        $this->info("Expired {$count} booking(s).");

        return Command::SUCCESS;
    }
}
