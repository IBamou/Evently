<?php

namespace App\Services\Admin;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Build platform-level operations data for the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function buildDashboardData(): array
    {
        $eventPipeline = collect(EventStatus::cases())
            ->mapWithKeys(fn (EventStatus $status): array => [
                $status->value => Event::query()->where('status', $status->value)->count(),
            ]);

        $paymentHealth = collect(PaymentStatus::cases())
            ->mapWithKeys(fn (PaymentStatus $status): array => [
                $status->value => Payment::query()->where('status', $status->value)->count(),
            ]);

        $totalPayments = $paymentHealth->sum();
        $paymentHealth->put(
            'success_rate',
            $totalPayments > 0
                ? round($paymentHealth->get(PaymentStatus::Succeeded->value, 0) / $totalPayments * 100, 1)
                : null,
        );

        $platformStats = [
            'gross_revenue' => (float) Payment::query()
                ->where('status', PaymentStatus::Succeeded->value)
                ->sum('amount'),
            'live_events' => $eventPipeline->get(EventStatus::Published->value, 0),
            'organizers' => User::query()->where('role', UserRole::Organizer->value)->count(),
            'customers' => User::query()->where('role', UserRole::User->value)->count(),
            'confirmed_bookings' => Booking::query()
                ->where('status', BookingStatus::Confirmed->value)
                ->count(),
        ];

        $attentionItems = [
            'event_reviews' => $eventPipeline->get(EventStatus::UnderReview->value, 0),
            'pending_payments' => $paymentHealth->get(PaymentStatus::Pending->value, 0),
            'failed_payments' => $paymentHealth->get(PaymentStatus::Failed->value, 0),
        ];

        return [
            'platformStats' => $platformStats,
            'attentionItems' => $attentionItems,
            'eventPipeline' => $eventPipeline,
            'paymentHealth' => $paymentHealth,
            'underReviewEvents' => $this->underReviewEvents(),
            'recentBookings' => $this->recentBookings(),
        ];
    }

    private function underReviewEvents(): Collection
    {
        return Event::query()
            ->select(['id', 'organizer_id', 'category_id', 'title', 'city', 'starts_at', 'created_at'])
            ->where('status', EventStatus::UnderReview->value)
            ->with(['organizer:id,name', 'category:id,name'])
            ->latest()
            ->limit(4)
            ->get();
    }

    private function recentBookings(): Collection
    {
        return Booking::query()
            ->select(['id', 'reference', 'user_id', 'event_id', 'status', 'total', 'currency', 'created_at'])
            ->with(['user:id,name', 'event:id,title'])
            ->withCount('items')
            ->latest()
            ->limit(5)
            ->get();
    }
}
