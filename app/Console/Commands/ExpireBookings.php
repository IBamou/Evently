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
     *
     * @var string
     */
    protected $signature = 'bookings:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending bookings past their expiry window (REQ-CN-010)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = 0;

        DB::table('bookings')
            ->where('status', BookingStatus::Pending->value)
            ->where('expires_at', '<', now())
            ->chunkById(500, function ($bookings) use (&$count) {
                $expiredIds = $bookings->pluck('id');

                DB::transaction(function () use ($expiredIds) {
                    // Mark bookings as expired
                    DB::table('bookings')
                        ->whereIn('id', $expiredIds)
                        ->update([
                            'status' => BookingStatus::Expired->value,
                            'updated_at' => now(),
                        ]);

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

                $count += $expiredIds->count();
            });

        if ($count === 0) {
            $this->info('No bookings to expire.');

            return Command::SUCCESS;
        }

        $this->info("Expired {$count} booking(s).");

        return Command::SUCCESS;
    }
}
