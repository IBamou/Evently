@php
    $platformStats ??= [];
    $attentionItems ??= [];
    $eventPipeline ??= collect();
    $paymentHealth ??= collect();
    $underReviewEvents ??= collect();
    $recentBookings ??= collect();

    $summaryCards = [
        ['label' => 'Gross volume', 'value' => number_format((float) ($platformStats['gross_revenue'] ?? 0)) . ' MAD', 'detail' => number_format($platformStats['confirmed_bookings'] ?? 0) . ' confirmed bookings', 'tone' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'],
        ['label' => 'Live events', 'value' => number_format($platformStats['live_events'] ?? 0), 'detail' => number_format($eventPipeline->get('under_review', 0)) . ' awaiting review', 'tone' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
        ['label' => 'Organizers', 'value' => number_format($platformStats['organizers'] ?? 0), 'detail' => 'Creating events on Evently', 'tone' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300'],
        ['label' => 'Customers', 'value' => number_format($platformStats['customers'] ?? 0), 'detail' => 'Registered attendee accounts', 'tone' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300'],
    ];

    $attentionTotal = array_sum($attentionItems);
    $pipelineTotal = max(1, $eventPipeline->only(['draft', 'under_review', 'published', 'cancelled'])->sum());
    $bookingStatusClasses = [
        'confirmed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        'expired' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300',
    ];
@endphp

<x-app-layout :activeNav="'odash'">
    <div class="mx-auto w-full max-w-[1380px] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="mb-7 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-xs font-extrabold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-300">
                    <span class="h-2 w-2 rounded-full bg-blue-600 dark:bg-blue-300"></span>
                    Platform operations
                </div>
                <h1 class="m-0 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Admin command center</h1>
                <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">Moderation, payments and platform health in one place.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.events.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-sm hover:bg-blue-700 hover:text-white">Review events</a>
                <a href="{{ route('admin.payments.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">Payments</a>
            </div>
        </div>

        <section aria-labelledby="attention-heading" class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/5">
            <div class="grid gap-4 p-5 lg:grid-cols-[1.1fr_2fr] lg:items-center lg:p-6">
                <div>
                    <p class="mb-1 text-xs font-extrabold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">Needs attention</p>
                    <h2 id="attention-heading" class="m-0 text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($attentionTotal) }} open {{ Str::plural('item', $attentionTotal) }}</h2>
                    <p class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-400">Resolve the platform’s operational queue before reviewing analytics.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('admin.events.index') }}" class="rounded-xl border border-amber-200 bg-white p-4 hover:border-amber-300 dark:border-amber-500/20 dark:bg-slate-900">
                        <span class="block text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($attentionItems['event_reviews'] ?? 0) }}</span>
                        <span class="mt-1 block text-xs font-bold text-slate-500 dark:text-slate-400">Event reviews</span>
                    </a>
                    <a href="{{ route('admin.payments.index') }}" class="rounded-xl border border-amber-200 bg-white p-4 hover:border-amber-300 dark:border-amber-500/20 dark:bg-slate-900">
                        <span class="block text-2xl font-extrabold text-slate-950 dark:text-white">{{ number_format($attentionItems['pending_payments'] ?? 0) }}</span>
                        <span class="mt-1 block text-xs font-bold text-slate-500 dark:text-slate-400">Pending payments</span>
                    </a>
                    <a href="{{ route('admin.payments.index') }}" class="rounded-xl border border-red-200 bg-white p-4 hover:border-red-300 dark:border-red-500/20 dark:bg-slate-900">
                        <span class="block text-2xl font-extrabold text-red-600 dark:text-red-300">{{ number_format($attentionItems['failed_payments'] ?? 0) }}</span>
                        <span class="mt-1 block text-xs font-bold text-slate-500 dark:text-slate-400">Failed payments</span>
                    </a>
                </div>
            </div>
        </section>

        <section aria-label="Platform overview" class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <span class="text-xs font-extrabold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">{{ $card['label'] }}</span>
                        <span class="h-8 w-8 rounded-lg {{ $card['tone'] }}"></span>
                    </div>
                    <div class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</div>
                    <p class="mb-0 mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <div class="mb-6 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="m-0 text-lg font-extrabold text-slate-950 dark:text-white">Approval queue</h2>
                        <p class="mb-0 mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Newest organizer submissions</p>
                    </div>
                    <a href="{{ route('admin.events.index') }}" class="text-xs font-extrabold text-blue-600 dark:text-blue-300">View all</a>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse ($underReviewEvents as $event)
                        <article class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/60 sm:flex-row sm:items-center">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-sm font-extrabold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ Str::upper(Str::substr($event->title, 0, 1)) }}</div>
                            <div class="min-w-0 flex-1">
                                <h3 class="m-0 truncate text-sm font-extrabold text-slate-900 dark:text-white">{{ $event->title }}</h3>
                                <p class="mb-0 mt-1 truncate text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $event->organizer?->name }} · {{ $event->category?->name }} · {{ $event->city }}</p>
                            </div>
                            <span class="w-fit rounded-lg bg-amber-100 px-2.5 py-1.5 text-[11px] font-extrabold uppercase tracking-wide text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Review</span>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 px-5 py-10 text-center dark:border-slate-700">
                            <p class="m-0 text-sm font-extrabold text-slate-800 dark:text-slate-200">Approval queue is clear</p>
                            <p class="mb-0 mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">New submissions will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-slate-950 p-5 text-white shadow-sm dark:border-slate-700 sm:p-6">
                <div class="mb-6">
                    <p class="mb-1 text-xs font-extrabold uppercase tracking-[0.14em] text-blue-300">Platform health</p>
                    <h2 class="m-0 text-lg font-extrabold">Payment reliability</h2>
                </div>
                <div class="mb-6 flex items-end gap-2">
                    <span class="text-4xl font-extrabold tracking-tight">{{ $paymentHealth->get('success_rate') !== null ? number_format($paymentHealth->get('success_rate'), 1) . '%' : '—' }}</span>
                    <span class="pb-1 text-xs font-bold text-slate-400">successful payments</span>
                </div>
                <dl class="grid grid-cols-2 gap-3">
                    @foreach ([['label' => 'Succeeded', 'key' => 'succeeded'], ['label' => 'Pending', 'key' => 'pending'], ['label' => 'Failed', 'key' => 'failed'], ['label' => 'Refunded', 'key' => 'refunded']] as $payment)
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                            <dt class="text-[11px] font-bold text-slate-400">{{ $payment['label'] }}</dt>
                            <dd class="mb-0 mt-1 text-xl font-extrabold">{{ number_format($paymentHealth->get($payment['key'], 0)) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[.75fr_1.25fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                <h2 class="m-0 text-lg font-extrabold text-slate-950 dark:text-white">Event pipeline</h2>
                <p class="mb-5 mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Distribution across every lifecycle stage</p>
                <div class="flex flex-col gap-4">
                    @foreach ([['label' => 'Published', 'key' => 'published', 'color' => 'bg-emerald-500'], ['label' => 'Under review', 'key' => 'under_review', 'color' => 'bg-amber-500'], ['label' => 'Draft', 'key' => 'draft', 'color' => 'bg-blue-500'], ['label' => 'Cancelled', 'key' => 'cancelled', 'color' => 'bg-red-500']] as $stage)
                        @php($stageCount = $eventPipeline->get($stage['key'], 0))
                        <div>
                            <div class="mb-2 flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-700 dark:text-slate-300">{{ $stage['label'] }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ number_format($stageCount) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $stage['color'] }}" style="width: {{ round($stageCount / $pipelineTotal * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 dark:border-slate-700 sm:px-6">
                    <div>
                        <h2 class="m-0 text-lg font-extrabold text-slate-950 dark:text-white">Recent bookings</h2>
                        <p class="mb-0 mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Latest marketplace activity</p>
                    </div>
                    <a href="{{ route('admin.bookings.index') }}" class="text-xs font-extrabold text-blue-600 dark:text-blue-300">All bookings</a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($recentBookings as $booking)
                        <article class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_1fr_auto] sm:items-center sm:px-6">
                            <div class="min-w-0">
                                <p class="m-0 truncate text-sm font-extrabold text-slate-900 dark:text-white">{{ $booking->user?->name ?? 'Unknown customer' }}</p>
                                <p class="mb-0 mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $booking->reference }} · {{ $booking->items_count }} {{ Str::plural('line', $booking->items_count) }}</p>
                            </div>
                            <p class="m-0 truncate text-xs font-bold text-slate-600 dark:text-slate-300">{{ $booking->event?->title ?? 'Deleted event' }}</p>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <span class="text-sm font-extrabold text-slate-900 dark:text-white">{{ number_format((float) $booking->total) }} {{ $booking->currency }}</span>
                                <span class="rounded-lg px-2.5 py-1.5 text-[10px] font-extrabold uppercase tracking-wide {{ $bookingStatusClasses[$booking->status->value] ?? $bookingStatusClasses['expired'] }}">{{ $booking->status->label() }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-12 text-center sm:px-6">
                            <p class="m-0 text-sm font-extrabold text-slate-800 dark:text-slate-200">No bookings yet</p>
                            <p class="mb-0 mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Marketplace activity will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
