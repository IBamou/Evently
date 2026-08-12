<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoBookingsSeeder extends Seeder
{
    /**
     * Seed demo bookings, booking items, tickets and payments for the
     * regular demo user (demo-user@example.com).
     *
     * Events, users and ticket types are looked up by unique key so this
     * seeder is independent from the in-memory references used while
     * creating them.
     */
    public function run(): void
    {
        $user = User::where('email', 'demo-user@example.com')->firstOrFail();
        $organizer = User::where('email', 'demo-organizer@evently.test')->firstOrFail();

        $festival = Event::where('slug', 'casablanca-summer-music-festival')->firstOrFail();
        $summit = Event::where('slug', 'rabat-tech-summit-2026')->firstOrFail();
        $foodTour = Event::where('slug', 'marrakech-street-food-tour')->firstOrFail();
        $past = Event::where('slug', 'rabat-business-networking-night')->firstOrFail();
        $remote = Event::where('slug', 'remote-careers-summit')->firstOrFail();
        $elJadida = Event::where('slug', 'el-jadida-moroccan-street-food-festival')->firstOrFail();

        $ga = TicketType::where('event_id', $festival->id)->where('name', 'General Admission')->firstOrFail();
        $vip = TicketType::where('event_id', $festival->id)->where('name', 'VIP Pass')->firstOrFail();
        $summitGeneral = TicketType::where('event_id', $summit->id)->where('name', 'General Ticket')->firstOrFail();
        $elJadidaTasting = TicketType::where('event_id', $elJadida->id)->where('name', 'Tasting Pass')->firstOrFail();

        // 1) Festival — confirmed 3 tickets, one already checked in at the door
        $booking = Booking::create([
            'reference' => 'BKEV-2410010',
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 135.00,
            'fees' => 6.75,
            'total' => 141.75,
            'currency' => 'MAD',
            'expires_at' => $festival->starts_at->subDays(1)->addMinutes(10),
            'confirmed_at' => now()->subDays(6),
        ]);

        $gaItem = BookingItem::create([
            'booking_id' => $booking->id,
            'ticket_type_id' => $ga->id,
            'ticket_name' => 'General Admission',
            'unit_price' => 25.00,
            'quantity' => 1,
            'line_total' => 25.00,
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'booking_item_id' => $gaItem->id,
            'ticket_type_id' => $ga->id,
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'code' => 'T-GA00000001',
            'status' => TicketStatus::Used,
            'issued_at' => now()->subDays(1),
            'checked_in_at' => now()->subHours(20),
        ]);

        $vipItem = BookingItem::create([
            'booking_id' => $booking->id,
            'ticket_type_id' => $vip->id,
            'ticket_name' => 'VIP Pass',
            'unit_price' => 55.00,
            'quantity' => 2,
            'line_total' => 110.00,
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'booking_item_id' => $vipItem->id,
            'ticket_type_id' => $vip->id,
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'code' => 'T-VIP00000001',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(1),
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'booking_item_id' => $vipItem->id,
            'ticket_type_id' => $vip->id,
            'user_id' => $user->id,
            'event_id' => $festival->id,
            'code' => 'T-VIP00000002',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(1),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'manual',
            'provider_reference' => 'PAY-BKE-2410010',
            'status' => PaymentStatus::Succeeded,
            'amount' => 141.75,
            'currency' => 'MAD',
            'paid_at' => now()->subDays(1),
        ]);

        // 2) Tech Summit — confirmed 1 ticket
        $booking2 = Booking::create([
            'reference' => 'BKE-2410011',
            'user_id' => $user->id,
            'event_id' => $summit->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 40.00,
            'fees' => 2.00,
            'total' => 42.00,
            'currency' => 'MAD',
            'expires_at' => $summit->starts_at->addMinutes(10),
            'confirmed_at' => now()->subDays(3),
        ]);

        $summitItem = BookingItem::create([
            'booking_id' => $booking2->id,
            'ticket_type_id' => $summitGeneral->id,
            'ticket_name' => 'General Ticket',
            'unit_price' => 40.00,
            'quantity' => 1,
            'line_total' => 40.00,
        ]);

        Ticket::create([
            'booking_id' => $booking2->id,
            'booking_item_id' => $summitItem->id,
            'ticket_type_id' => $summitGeneral->id,
            'user_id' => $user->id,
            'event_id' => $summit->id,
            'code' => 'T-SU00000001',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(3),
        ]);

        Payment::create([
            'booking_id' => $booking2->id,
            'provider' => 'manual',
            'provider_reference' => 'PAY-BKE-2410011',
            'status' => PaymentStatus::Succeeded,
            'amount' => 42.00,
            'currency' => 'MAD',
            'paid_at' => now()->subDays(3),
        ]);

        // 3) Food tour — pending booking (awaiting payment, expires in 8 minutes)
        Booking::create([
            'user_id' => $user->id,
            'event_id' => $foodTour->id,
            'reference' => 'BKE-2410013',
            'status' => BookingStatus::Pending,
            'subtotal' => 15.00,
            'fees' => 0.75,
            'total' => 15.75,
            'currency' => 'MAD',
            'expires_at' => now()->addMinutes(8),
        ]);

        // 4) Past networking event — attended + checked in (free booking)
        $booking4 = Booking::create([
            'reference' => 'BKE-2410001',
            'user_id' => $user->id,
            'event_id' => $past->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 0.00,
            'fees' => 0.00,
            'total' => 0.00,
            'currency' => 'MAD',
            'expires_at' => $past->starts_at->addMinutes(10),
            'confirmed_at' => now()->subDays(12),
        ]);

        $netItem = BookingItem::create([
            'booking_id' => $booking4->id,
            'ticket_type_id' => null,
            'ticket_name' => 'Free Entry',
            'unit_price' => 0.00,
            'quantity' => 1,
            'line_total' => 0.00,
        ]);

        Ticket::create([
            'booking_id' => $booking4->id,
            'booking_item_id' => $netItem->id,
            'ticket_type_id' => null,
            'user_id' => $user->id,
            'event_id' => $past->id,
            'code' => 'T-NET00000001',
            'status' => TicketStatus::Used,
            'issued_at' => now()->subDays(12),
            'checked_in_at' => now()->subDays(10),
        ]);

        // 5) Remote summit — cancelled booking (user changed mind, refunded)
        $booking5 = Booking::create([
            'reference' => 'BKE-2410012',
            'user_id' => $user->id,
            'event_id' => $remote->id,
            'status' => BookingStatus::Cancelled,
            'subtotal' => 9.00,
            'fees' => 0.45,
            'total' => 9.45,
            'currency' => 'MAD',
            'expires_at' => $remote->starts_at->addMinutes(10),
            'cancelled_at' => now()->subHours(5),
        ]);

        Payment::create([
            'booking_id' => $booking5->id,
            'provider' => 'manual',
            'provider_reference' => 'PAY-BKE-2410012',
            'status' => PaymentStatus::Refunded,
            'amount' => 9.45,
            'currency' => 'MAD',
            'paid_at' => now()->subDays(2),
        ]);

        // 6) El Jadida Moroccan Street Food Festival — confirmed 2× Tasting Pass
        $booking6 = Booking::create([
            'reference' => 'BKEV-2410014',
            'user_id' => $user->id,
            'event_id' => $elJadida->id,
            'status' => BookingStatus::Confirmed,
            'subtotal' => 300.00,
            'fees' => 15.00,
            'total' => 315.00,
            'currency' => 'MAD',
            'expires_at' => $elJadida->starts_at->addMinutes(10),
            'confirmed_at' => now()->subDays(2),
        ]);

        $elJadidaItem = BookingItem::create([
            'booking_id' => $booking6->id,
            'ticket_type_id' => $elJadidaTasting->id,
            'ticket_name' => 'Tasting Pass',
            'unit_price' => 150.00,
            'quantity' => 2,
            'line_total' => 300.00,
        ]);

        Ticket::create([
            'booking_id' => $booking6->id,
            'booking_item_id' => $elJadidaItem->id,
            'ticket_type_id' => $elJadidaTasting->id,
            'user_id' => $user->id,
            'event_id' => $elJadida->id,
            'code' => 'T-ELJ00000001',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(2),
        ]);

        Ticket::create([
            'booking_id' => $booking6->id,
            'booking_item_id' => $elJadidaItem->id,
            'ticket_type_id' => $elJadidaTasting->id,
            'user_id' => $user->id,
            'event_id' => $elJadida->id,
            'code' => 'T-ELJ00000002',
            'status' => TicketStatus::Valid,
            'issued_at' => now()->subDays(2),
        ]);

        Payment::create([
            'booking_id' => $booking6->id,
            'provider' => 'manual',
            'provider_reference' => 'PAY-BKE-2410014',
            'status' => PaymentStatus::Succeeded,
            'amount' => 315.00,
            'currency' => 'MAD',
            'paid_at' => now()->subDays(2),
        ]);
    }
}
