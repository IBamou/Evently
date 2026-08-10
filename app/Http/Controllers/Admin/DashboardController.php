<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the platform dashboard (design odash as admin).
     *
     * Real platform-wide stats (events, revenue, tickets, check-ins) plus the
     * six most recent bookings. Charts and category bars computed from real
     * platform-wide data, mirroring the organizer dashboard.
     */
    public function dashboard(): View
    {
        $stats = [
            'total' => Event::query()->count(),
            'published' => Event::query()->where('status', EventStatus::Published)->count(),
            'underReview' => Event::query()->where('status', EventStatus::UnderReview)->count(),
        ];

        $revenue = (float) Payment::query()->where('status', PaymentStatus::Succeeded->value)->sum('amount');
        $ticketsIssued = Ticket::query()->count();
        $ticketsChecked = Ticket::query()->whereNotNull('checked_in_at')->count();

        $orders = Booking::query()
            ->with(['user:id,name', 'event:id,title'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Booking $booking): array {
                $name = $booking->user->name ?? 'Unknown';

                return [
                    'buyer' => $name,
                    'initial' => collect(explode(' ', $name))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
                        ->join(''),
                    'event' => $booking->event->title ?? '—',
                    'qty' => $booking->items()->sum('quantity'),
                    'total' => $booking->total,
                    'status' => match ($booking->status) {
                        BookingStatus::Confirmed => 'Paid',
                        BookingStatus::Pending => 'Pending',
                        BookingStatus::Cancelled => 'Cancelled',
                        default => 'Expired',
                    },
                ];
            });

        // Platform-wide chart and category bars (mirror organizer pattern).
        $chart = $this->chartSeries();
        $catBars = $this->categoryBars();

        $checkInRate = $ticketsIssued > 0
            ? round($ticketsChecked / $ticketsIssued * 100, 1)
            : null;

        $hasEvents = Event::query()->count() > 0;

        return view('admin.dashboard', compact(
            'stats',
            'revenue',
            'ticketsIssued',
            'ticketsChecked',
            'orders',
            'chart',
            'catBars',
            'checkInRate',
            'hasEvents'
        ));
    }

    /**
     * Build the last-5-weeks revenue/tickets series for the dashboard chart.
     *
     * Platform-wide (no event scope), mirroring organizer's chartSeries().
     */
    private function chartSeries(): array
    {
        $weeks = collect(range(4, 0))->map(
            fn (int $offset): array => [
                'start' => now()->startOfWeek()->subWeeks($offset),
                'end' => now()->startOfWeek()->subWeeks($offset - 1),
            ]
        );

        $revenue = [];
        $tickets = [];
        foreach ($weeks as $week) {
            $revenue[] = (float) Payment::query()
                ->where('status', PaymentStatus::Succeeded->value)
                ->whereBetween('paid_at', [$week['start'], $week['end']])
                ->sum('amount');
            $tickets[] = Ticket::query()
                ->whereBetween('created_at', [$week['start'], $week['end']])
                ->count();
        }

        $maxRevenue = collect($revenue)->max() ?: 1.0;
        $maxTickets = collect($tickets)->max() ?: 1;

        return $weeks->values()->map(function (array $week, int $i) use ($revenue, $tickets, $maxRevenue, $maxTickets): array {
            $label = 'W'.($i + 1);

            return [
                'label' => $label,
                'revH' => max(2, (int) round($revenue[$i] / $maxRevenue * 100)).'%',
                'tixH' => max(2, (int) round($tickets[$i] / $maxTickets * 100)).'%',
                'revLabel' => number_format($revenue[$i]).' MAD',
                'tixLabel' => number_format($tickets[$i]),
            ];
        })->all();
    }

    /**
     * Aggregate ticket quantities per event category for the dashboard.
     *
     * Platform-wide (no event scope), mirroring organizer's categoryBars().
     */
    private function categoryBars(): array
    {
        $colors = ['var(--primary)', 'var(--cyan)', 'var(--teal)', '#7C3AED', '#F59E0B'];

        $rows = BookingItem::query()
            ->whereHas('booking', function ($q): void {
                $q->where('status', BookingStatus::Confirmed->value);
            })
            ->with('booking.event.category:id,name')
            ->get()
            ->groupBy(fn (BookingItem $item): string => $item->booking->event->category->name ?? 'Uncategorized')
            ->map(fn ($items): int => $items->sum('quantity'))
            ->sortDesc()
            ->take(5);

        if ($rows->isEmpty()) {
            return [];
        }

        $total = $rows->sum();

        $labels = $rows->keys()->all();

        return $rows->values()->map(function (int $qty, int $i) use ($labels, $total, $colors): array {
            return [
                'label' => $labels[$i],
                'value' => number_format($qty).' tix',
                'pct' => round($qty / $total * 100).'%',
                'color' => $colors[$i % count($colors)],
            ];
        })->all();
    }
}
