<?php

namespace App\Services\Organizer;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Build all data for the organizer dashboard.
     *
     * Pass null for $user to get platform-wide data (admin mode).
     */
    public function buildDashboardData(?User $user = null): array
    {
        $events = $user
            ? $user->events()->get()
            : Event::query()->get();

        $eventIds = $events->pluck('id');

        $stats = [
            'total' => $events->count(),
            'published' => $events->where('status', EventStatus::Published)->count(),
            'underReview' => $events->where('status', EventStatus::UnderReview)->count(),
            'drafts' => $events->where('status', EventStatus::Draft)->count(),
            'cancelled' => $events->where('status', EventStatus::Cancelled)->count(),
        ];

        $revenueQuery = Payment::query()->where('status', PaymentStatus::Succeeded->value);
        $revenueQuery = $eventIds->isEmpty()
            ? $revenueQuery
            : $revenueQuery->whereHas('booking', fn ($q) => $q->whereIn('event_id', $eventIds));
        $revenue = (float) $revenueQuery->sum('amount');

        $ticketsQuery = Ticket::query();
        $ticketsQuery = $eventIds->isEmpty()
            ? $ticketsQuery
            : $ticketsQuery->whereIn('event_id', $eventIds);
        $ticketsIssued = (clone $ticketsQuery)
            ->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Used->value])
            ->count();
        $ticketsChecked = (clone $ticketsQuery)
            ->where('status', TicketStatus::Used->value)
            ->count();

        $checkInRate = $ticketsIssued > 0 ? round($ticketsChecked / $ticketsIssued * 100, 1) : null;

        $ordersQuery = Booking::query();
        $ordersQuery = $eventIds->isEmpty()
            ? $ordersQuery
            : $ordersQuery->whereIn('event_id', $eventIds);

        $orders = $ordersQuery
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
                    'total' => (float) $booking->total,
                    'status' => match ($booking->status) {
                        BookingStatus::Confirmed => 'Paid',
                        BookingStatus::Pending => 'Pending',
                        BookingStatus::Cancelled => 'Cancelled',
                        default => 'Expired',
                    },
                ];
            });

        $chart = $this->chartSeries($eventIds->isEmpty() ? null : $eventIds);
        $catBars = $this->categoryBars($eventIds->isEmpty() ? null : $eventIds);

        return compact('stats', 'revenue', 'ticketsIssued', 'ticketsChecked', 'checkInRate', 'orders', 'chart', 'catBars');
    }

    /**
     * Build the last-5-weeks revenue/tickets series for the dashboard chart.
     *
     * When $eventIds is null, returns platform-wide data (admin mode).
     */
    private function chartSeries(?Collection $eventIds = null): array
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
                ->when($eventIds, fn ($q) => $q->whereHas('booking', fn ($bq) => $bq->whereIn('event_id', $eventIds)))
                ->whereBetween('paid_at', [$week['start'], $week['end']])
                ->sum('amount');
            $tickets[] = Ticket::query()
                ->when($eventIds, fn ($q) => $q->whereIn('event_id', $eventIds))
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
                'tixLabel' => number_format($tickets[$i]).' tickets',
            ];
        })->all();
    }

    /**
     * Aggregate ticket quantities per event category for the dashboard.
     *
     * When $eventIds is null, returns platform-wide data (admin mode).
     */
    private function categoryBars(?Collection $eventIds = null): array
    {
        $colors = ['var(--primary)', 'var(--cyan)', 'var(--teal)', '#7C3AED', '#F59E0B'];

        $rows = BookingItem::query()
            ->whereHas('booking', function ($q) use ($eventIds): void {
                $q->when($eventIds, fn ($bq) => $bq->whereIn('event_id', $eventIds))
                    ->where('status', BookingStatus::Confirmed->value);
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
