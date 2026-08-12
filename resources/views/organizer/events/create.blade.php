@php
    $cities = ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Chefchaouen', 'Salé'];
    $detailsHaveErrors = $errors->has('category_id')
        || $errors->has('format')
        || $errors->has('location')
        || $errors->has('city')
        || $errors->has('starts_at')
        || $errors->has('ends_at')
        || $errors->has('banner_url');
    $initialStep = $detailsHaveErrors ? 1 : 0;
@endphp

<x-app-layout :activeNav="'oevents'">
    <div
        class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-8"
        x-data="createEventWizard({
            initialStep: {{ $initialStep }},
            title: @js((string) old('title', '')),
            description: @js((string) old('description', '')),
            format: @js((string) old('format', 'in_person')),
            bannerUrl: @js((string) old('banner_url', '')),
        })"
        x-init="init()"
    >
        <header class="mb-6 flex flex-col gap-4 sm:mb-7 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <a
                    href="{{ route('organizer.events.index') }}"
                    class="mb-4 inline-flex min-h-10 items-center gap-2 rounded-lg px-1 text-sm font-bold text-[var(--muted)] transition hover:text-[var(--primary)] focus-visible:outline-offset-4"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    My events
                </a>

                <div class="mb-2 flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--chip)] px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--primary)]">
                        <span class="h-2 w-2 rounded-full bg-[var(--primary)]"></span>
                        Event builder
                    </span>
                </div>
                <h1 class="m-0 text-2xl font-extrabold tracking-[-.02em] text-[var(--text)] sm:text-3xl">Create your event</h1>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[var(--muted)]">
                    Start with the essentials. You can add ticket types and submit for approval after your draft is created.
                </p>
            </div>

            @if (config('ai.event_copilot.enabled'))
                <a
                    href="{{ route('organizer.ai.workspace') }}"
                    class="inline-flex min-h-10 w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-4 py-2.5 text-sm font-extrabold text-[var(--text)] shadow-sm transition hover:border-[var(--primary)] hover:text-[var(--primary)] sm:w-auto"
                >
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-[var(--primary)] to-[var(--cyan)] text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.9 5.7L19.6 10l-5.7 1.9L12 17.6l-1.9-5.7L4.4 10l5.7-1.9z" />
                            <path d="M19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9z" />
                        </svg>
                    </span>
                    Create with AI
                </a>
            @endif
        </header>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-300/50 bg-red-500/10 px-4 py-3 text-sm font-semibold text-[var(--err)]" role="alert">
                <p class="m-0 font-extrabold">Please check the highlighted event details.</p>
                <ul class="mb-0 mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-6">
            <aside class="hidden lg:block">
                <nav class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-2 shadow-sm" aria-label="Event creation progress">
                    <template x-for="(item, index) in steps" :key="item.title">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl px-2.5 py-3 text-left transition"
                            x-bind:class="step === index ? 'bg-[var(--chip)]' : ''"
                            x-on:click="goToStep(index)"
                            x-bind:disabled="index > highestStep"
                            x-bind:aria-current="step === index ? 'step' : null"
                        >
                            <span
                                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border text-xs font-extrabold"
                                x-bind:class="step === index || highestStep > index ? 'border-[var(--primary)] bg-[var(--primary)] text-white' : 'border-[var(--border)] bg-[var(--surface2)] text-[var(--muted)]'"
                                x-text="highestStep > index ? '✓' : index + 1"
                            ></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-extrabold" x-bind:class="step === index ? 'text-[var(--primary)]' : 'text-[var(--muted)]'" x-text="item.title"></span>
                                <span class="mt-1 block text-xs font-semibold leading-4 text-[var(--muted)]" x-text="item.subtitle"></span>
                            </span>
                        </button>
                    </template>
                </nav>

                <div class="mt-4 flex gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface2)] p-4">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[var(--chip)] text-[var(--primary)]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                    </span>
                    <div>
                        <p class="m-0 text-sm font-extrabold text-[var(--text)]">Saved as a draft</p>
                        <p class="mb-0 mt-1 text-xs font-semibold leading-5 text-[var(--muted)]">Nothing goes live until you add tickets and submit it for review.</p>
                    </div>
                </div>
            </aside>

            <div class="min-w-0">
                <nav class="mb-5 flex items-center rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-2 shadow-sm lg:hidden" aria-label="Event creation progress">
                    <template x-for="(item, index) in steps" :key="item.title">
                        <div class="flex min-w-0 flex-1 items-center">
                            <button
                                type="button"
                                class="grid min-h-12 flex-1 place-items-center rounded-xl transition"
                                x-bind:class="step === index ? 'bg-[var(--chip)]' : ''"
                                x-on:click="goToStep(index)"
                                x-bind:disabled="index > highestStep"
                                x-bind:aria-label="item.title"
                                x-bind:aria-current="step === index ? 'step' : null"
                            >
                                <span
                                    class="grid h-8 w-8 place-items-center rounded-lg border text-xs font-extrabold"
                                    x-bind:class="step === index || highestStep > index ? 'border-[var(--primary)] bg-[var(--primary)] text-white' : 'border-[var(--border)] bg-[var(--surface2)] text-[var(--muted)]'"
                                    x-text="highestStep > index ? '✓' : index + 1"
                                ></span>
                            </button>
                            <span x-show="index < 2" class="mx-1 h-px w-3 shrink-0 bg-[var(--border)]"></span>
                        </div>
                    </template>
                </nav>

                <form id="event-form" method="POST" action="{{ route('organizer.events.store') }}" class="overflow-visible rounded-2xl border border-[var(--border)] bg-[var(--surface)] shadow-sm">
                    @csrf

                    <section x-show="step === 0" x-cloak aria-labelledby="basics-heading">
                        <div class="flex items-start gap-3 px-5 pb-4 pt-5 sm:px-6 sm:pt-6">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--chip)] text-[var(--primary)]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z" />
                                </svg>
                            </span>
                            <div>
                                <p class="m-0 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--primary)]">Step 1 of 3</p>
                                <h2 id="basics-heading" class="mb-0 mt-1 text-lg font-extrabold tracking-[-.01em] text-[var(--text)] sm:text-xl">Tell people what to expect</h2>
                                <p class="mb-0 mt-2 text-sm font-semibold leading-5 text-[var(--muted)]">A clear name and useful description make your event easier to discover.</p>
                            </div>
                        </div>

                        <div class="space-y-5 px-5 pb-6 sm:px-6">
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label for="event-title" class="text-sm font-extrabold text-[var(--text)]">Event title <span class="text-[var(--err)]">*</span></label>
                                    <span class="text-xs font-bold text-[var(--muted)]" x-text="values.title.length + '/255'"></span>
                                </div>
                                <div class="relative">
                                    <input
                                        id="event-title"
                                        type="text"
                                        name="title"
                                        maxlength="255"
                                        required
                                        placeholder="e.g. Casablanca Jazz Night"
                                        class="min-h-12 w-full rounded-xl border bg-[var(--surface2)] px-4 py-3 pr-12 text-sm font-semibold text-[var(--text)] placeholder:text-[var(--muted)] focus:border-[var(--primary)]"
                                        x-bind:class="fieldErrors.title ? 'border-[var(--err)]' : 'border-[var(--border)]'"
                                        x-model="values.title"
                                        x-on:input="fieldErrors.title = ''"
                                    >
                                    @if (config('ai.event_copilot.enabled'))
                                        @include('organizer.events.partials.ai-polish-field', ['field' => 'title'])
                                    @endif
                                </div>
                                <p x-show="fieldErrors.title" x-cloak class="mb-0 mt-2 text-xs font-bold text-[var(--err)]" x-text="fieldErrors.title"></p>
                                @error('title') <p class="mb-0 mt-2 text-xs font-bold text-[var(--err)]">{{ $message }}</p> @enderror
                                <p class="mb-0 mt-2 text-xs font-semibold text-[var(--muted)]">Keep it specific, memorable, and easy to scan.</p>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label for="event-description" class="text-sm font-extrabold text-[var(--text)]">Description <span class="text-[var(--err)]">*</span></label>
                                    <span class="text-xs font-bold text-[var(--muted)]" x-text="values.description.length + ' characters'"></span>
                                </div>
                                <div class="relative">
                                    <textarea
                                        id="event-description"
                                        name="description"
                                        rows="6"
                                        required
                                        placeholder="Describe the experience, who it is for, and what attendees will take away..."
                                        class="min-h-40 w-full resize-y rounded-xl border bg-[var(--surface2)] px-4 py-3 pr-12 text-sm font-semibold leading-6 text-[var(--text)] placeholder:text-[var(--muted)] focus:border-[var(--primary)] sm:min-h-48"
                                        x-bind:class="fieldErrors.description ? 'border-[var(--err)]' : 'border-[var(--border)]'"
                                        x-model="values.description"
                                        x-on:input="fieldErrors.description = ''"
                                    ></textarea>
                                    @if (config('ai.event_copilot.enabled'))
                                        @include('organizer.events.partials.ai-polish-field', ['field' => 'description'])
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-col gap-1 text-xs font-semibold text-[var(--muted)] sm:flex-row sm:justify-between">
                                    <p class="m-0">Include the highlights, audience, and schedule.</p>
                                    <p class="m-0">Minimum 20 characters</p>
                                </div>
                                <p x-show="fieldErrors.description" x-cloak class="mb-0 mt-2 text-xs font-bold text-[var(--err)]" x-text="fieldErrors.description"></p>
                                @error('description') <p class="mb-0 mt-2 text-xs font-bold text-[var(--err)]">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section x-show="step === 1" x-cloak aria-labelledby="details-heading">
                        <div class="flex items-start gap-3 px-5 pb-4 pt-5 sm:px-6 sm:pt-6">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--chip)] text-[var(--primary)]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />
                                </svg>
                            </span>
                            <div>
                                <p class="m-0 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--primary)]">Step 2 of 3</p>
                                <h2 id="details-heading" class="mb-0 mt-1 text-lg font-extrabold tracking-[-.01em] text-[var(--text)] sm:text-xl">Set the time and place</h2>
                                <p class="mb-0 mt-2 text-sm font-semibold leading-5 text-[var(--muted)]">Add the practical details attendees need to make a plan.</p>
                            </div>
                        </div>

                        <div class="space-y-6 px-5 pb-6 sm:px-6">
                            <fieldset class="space-y-4">
                                <legend class="flex w-full items-center gap-3 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--muted)]">
                                    Event setup <span class="h-px flex-1 bg-[var(--border)]"></span>
                                </legend>

                                <label class="block">
                                    <span class="mb-2 block text-sm font-extrabold text-[var(--text)]">Category <span class="text-[var(--err)]">*</span></span>
                                    <select name="category_id" required x-model="values.categoryId" class="min-h-12 w-full rounded-xl border border-[var(--border)] bg-[var(--surface2)] px-4 text-sm font-semibold text-[var(--text)] focus:border-[var(--primary)]">
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                </label>

                                <fieldset>
                                    <legend class="mb-2 text-sm font-extrabold text-[var(--text)]">Format <span class="text-[var(--err)]">*</span></legend>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach (\App\Enums\EventFormat::cases() as $format)
                                            <label
                                                class="flex min-h-12 cursor-pointer items-center gap-2 rounded-xl border bg-[var(--surface2)] px-3 text-xs font-extrabold transition sm:text-sm"
                                                x-bind:class="values.format === '{{ $format->value }}' ? 'border-[var(--primary)] text-[var(--primary)]' : 'border-[var(--border)] text-[var(--text)]'"
                                            >
                                                <input type="radio" name="format" value="{{ $format->value }}" class="h-4 w-4 accent-[var(--primary)]" x-model="values.format">
                                                {{ $format->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('format') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                </fieldset>
                            </fieldset>

                            <fieldset class="space-y-4">
                                <legend class="flex w-full items-center gap-3 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--muted)]">
                                    Schedule <span class="h-px flex-1 bg-[var(--border)]"></span>
                                </legend>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label>
                                        <span class="mb-2 block text-sm font-extrabold text-[var(--text)]">Starts at <span class="text-[var(--err)]">*</span></span>
                                        <input type="datetime-local" name="starts_at" required class="min-h-12 w-full rounded-xl border border-[var(--border)] bg-[var(--surface2)] px-3 text-sm font-semibold focus:border-[var(--primary)]" x-model="values.startsAt" x-on:input="fieldErrors.dates = ''">
                                        @error('starts_at') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                    </label>
                                    <label>
                                        <span class="mb-2 block text-sm font-extrabold text-[var(--text)]">Ends at <span class="text-[var(--err)]">*</span></span>
                                        <input type="datetime-local" name="ends_at" required class="min-h-12 w-full rounded-xl border border-[var(--border)] bg-[var(--surface2)] px-3 text-sm font-semibold focus:border-[var(--primary)]" x-model="values.endsAt" x-on:input="fieldErrors.dates = ''">
                                        @error('ends_at') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                                <p x-show="fieldErrors.dates" x-cloak class="m-0 text-xs font-bold text-[var(--err)]" x-text="fieldErrors.dates"></p>
                            </fieldset>

                            <fieldset class="space-y-4">
                                <legend class="flex w-full items-center gap-3 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--muted)]">
                                    Location <span class="h-px flex-1 bg-[var(--border)]"></span>
                                </legend>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label>
                                        <span class="mb-2 block text-sm font-extrabold text-[var(--text)]">Venue / location <span class="text-[var(--err)]">*</span></span>
                                        <input type="text" name="location" required placeholder="e.g. Complexe Mohammed V" class="min-h-12 w-full rounded-xl border border-[var(--border)] bg-[var(--surface2)] px-4 text-sm font-semibold placeholder:text-[var(--muted)] focus:border-[var(--primary)]" x-model="values.location">
                                        @error('location') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                    </label>
                                    <label>
                                        <span class="mb-2 block text-sm font-extrabold text-[var(--text)]">City <span class="text-[var(--err)]">*</span></span>
                                        <select name="city" required x-model="values.city" class="min-h-12 w-full rounded-xl border border-[var(--border)] bg-[var(--surface2)] px-4 text-sm font-semibold focus:border-[var(--primary)]">
                                            @foreach ($cities as $city)
                                                <option value="{{ $city }}" @selected(old('city') === $city)>{{ $city }}</option>
                                            @endforeach
                                        </select>
                                        @error('city') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            </fieldset>

                            <fieldset class="space-y-3">
                                <legend class="flex w-full items-center gap-3 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--muted)]">
                                    Cover <span class="h-px flex-1 bg-[var(--border)]"></span>
                                </legend>
                                <label>
                                    <span class="mb-2 block text-sm font-extrabold text-[var(--text)]">Cover image URL <span class="font-semibold text-[var(--muted)]">(optional)</span></span>
                                    <input type="url" name="banner_url" placeholder="https://example.com/cover.jpg" class="min-h-12 w-full rounded-xl border border-[var(--border)] bg-[var(--surface2)] px-4 text-sm font-semibold placeholder:text-[var(--muted)] focus:border-[var(--primary)]" x-model="values.bannerUrl" x-on:input="bannerFailed = false">
                                    @error('banner_url') <span class="mt-2 block text-xs font-bold text-[var(--err)]">{{ $message }}</span> @enderror
                                </label>
                                <div x-show="values.bannerUrl && !bannerFailed" x-cloak class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--surface2)]">
                                    <img x-bind:src="values.bannerUrl" x-on:error="bannerFailed = true" alt="Cover preview" class="h-32 w-full object-cover">
                                </div>
                            </fieldset>
                        </div>
                    </section>

                    <section x-show="step === 2" x-cloak aria-labelledby="review-heading">
                        <div class="flex items-start gap-3 px-5 pb-4 pt-5 sm:px-6 sm:pt-6">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--chip)] text-[var(--primary)]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                                </svg>
                            </span>
                            <div>
                                <p class="m-0 text-[11px] font-extrabold uppercase tracking-[.12em] text-[var(--primary)]">Step 3 of 3</p>
                                <h2 id="review-heading" class="mb-0 mt-1 text-lg font-extrabold tracking-[-.01em] text-[var(--text)] sm:text-xl">Review your draft</h2>
                                <p class="mb-0 mt-2 text-sm font-semibold leading-5 text-[var(--muted)]">Check how the most important details will read to attendees.</p>
                            </div>
                        </div>

                        <div class="px-5 pb-6 sm:px-6">
                            <article class="overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface2)]">
                                <div class="relative grid h-36 place-items-center overflow-hidden bg-gradient-to-br from-[var(--hero1)] via-[var(--hero2)] to-[var(--hero3)] sm:h-44">
                                    <img x-show="values.bannerUrl && !bannerFailed" x-cloak x-bind:src="values.bannerUrl" x-on:error="bannerFailed = true" alt="" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!values.bannerUrl || bannerFailed" class="grid h-16 w-16 place-items-center rounded-2xl border border-white/60 bg-white/50 text-[var(--muted)] backdrop-blur">
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" />
                                        </svg>
                                    </span>
                                    <span class="absolute left-4 top-4 rounded-full border border-white/70 bg-white/90 px-3 py-1.5 text-xs font-extrabold text-[#0B2545]" x-text="selectLabel('category_id')"></span>
                                </div>
                                <div class="space-y-4 p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="m-0 break-words text-xl font-extrabold tracking-[-.02em] text-[var(--text)]" x-text="values.title || 'Untitled event'"></h3>
                                            <span class="mt-3 block h-0.5 w-3 bg-[var(--primary)]"></span>
                                        </div>
                                        <span class="shrink-0 rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1.5 text-xs font-extrabold text-[var(--muted)]" x-text="values.format === 'online' ? 'Online' : 'In person'"></span>
                                    </div>
                                    <p class="m-0 whitespace-pre-wrap text-sm font-semibold leading-6 text-[var(--muted)]" x-text="values.description || 'Your event description will appear here.'"></p>
                                    <dl class="grid gap-3 border-t border-[var(--border)] pt-4 sm:grid-cols-2">
                                        <div>
                                            <dt class="text-[10px] font-extrabold uppercase tracking-[.1em] text-[var(--muted)]">Starts</dt>
                                            <dd class="mb-0 mt-1 text-sm font-bold text-[var(--text)]" x-text="formatDate(values.startsAt)"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] font-extrabold uppercase tracking-[.1em] text-[var(--muted)]">Ends</dt>
                                            <dd class="mb-0 mt-1 text-sm font-bold text-[var(--text)]" x-text="formatDate(values.endsAt)"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] font-extrabold uppercase tracking-[.1em] text-[var(--muted)]">Where</dt>
                                            <dd class="mb-0 mt-1 text-sm font-bold text-[var(--text)]" x-text="locationSummary()"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] font-extrabold uppercase tracking-[.1em] text-[var(--muted)]">Format</dt>
                                            <dd class="mb-0 mt-1 text-sm font-bold text-[var(--text)]" x-text="values.format === 'online' ? 'Online' : 'In person'"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </article>

                            <div class="mt-4 rounded-xl border border-[var(--border)] bg-[var(--chip)] px-4 py-3 text-xs font-semibold leading-5 text-[var(--muted)]">
                                Creating this event saves it as a draft. You can add tickets and submit it for approval afterward.
                            </div>
                        </div>
                    </section>

                    <footer class="flex items-center gap-3 border-t border-[var(--border)] bg-[var(--surface2)] px-5 py-4 sm:px-6">
                        <span class="hidden text-xs font-bold text-[var(--muted)] sm:block" x-text="'Step ' + (step + 1) + ' of 3'"></span>
                        <a x-show="step === 0" href="{{ route('organizer.events.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-extrabold text-[var(--muted)] hover:text-[var(--primary)]">Cancel</a>
                        <button x-show="step > 0" x-cloak type="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-extrabold text-[var(--muted)] hover:text-[var(--primary)]" x-on:click="previousStep()">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                            Back
                        </button>
                        <span class="flex-1"></span>
                        <button x-show="step < 2" type="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-dark)] px-5 text-sm font-extrabold text-white shadow-md shadow-blue-900/10 transition hover:-translate-y-px" x-on:click="nextStep()">
                            Continue
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        </button>
                        <button x-show="step === 2" x-cloak type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-dark)] px-5 text-sm font-extrabold text-white shadow-md shadow-blue-900/10 transition hover:-translate-y-px">
                            Create draft
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6" /></svg>
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </div>

    <script>
        function createEventWizard(options) {
            return {
                steps: [
                    { title: 'Basics', subtitle: 'Title and description' },
                    { title: 'Details', subtitle: 'Time, place and format' },
                    { title: 'Review', subtitle: 'Check your draft' },
                ],
                step: Number(options.initialStep || 0),
                highestStep: Number(options.initialStep || 0),
                bannerFailed: false,
                fieldErrors: { title: '', description: '', dates: '' },
                values: {
                    title: options.title || '',
                    description: options.description || '',
                    format: options.format || 'in_person',
                    bannerUrl: options.bannerUrl || '',
                    categoryId: '',
                    startsAt: '',
                    endsAt: '',
                    location: '',
                    city: '',
                },

                init() {
                    this.restoreAiDraft();
                },

                form() {
                    return document.getElementById('event-form');
                },

                formVal(name) {
                    var form = this.form();
                    return form && form.elements[name] ? (form.elements[name].value || '') : '';
                },

                selectLabel(name) {
                    if (name === 'category_id') {
                        var select = this.form()?.elements?.category_id;
                        if (select && this.values.categoryId) {
                            var opt = Array.from(select.options).find((o) => String(o.value) === String(this.values.categoryId));
                            return opt ? opt.text : '—';
                        }
                        return '—';
                    }
                    var form = this.form();
                    var select = form && form.elements[name] ? form.elements[name] : null;
                    return select && select.selectedIndex >= 0 ? (select.options[select.selectedIndex].text || '—') : '—';
                },

                formatDate(value) {
                    if (!value) return 'Date not set';
                    var date = new Date(value);
                    if (Number.isNaN(date.getTime())) return 'Date not set';
                    return date.toLocaleString('en', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
                },

                locationSummary() {
                    var parts = [this.values.location, this.values.city].filter(Boolean);
                    return parts.length ? parts.join(', ') : 'Location not set';
                },

                goToStep(index) {
                    if (index <= this.highestStep) this.step = index;
                },

                previousStep() {
                    this.step = Math.max(0, this.step - 1);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                nextStep() {
                    if (this.step === 0 && !this.validateBasics()) return;
                    if (this.step === 1 && !this.validateDetails()) return;
                    this.step = Math.min(2, this.step + 1);
                    this.highestStep = Math.max(this.highestStep, this.step);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                validateBasics() {
                    this.fieldErrors.title = this.values.title.trim() ? '' : 'Add an event title to continue.';
                    this.fieldErrors.description = this.values.description.trim().length >= 20 ? '' : 'Add at least 20 characters describing the event.';
                    if (!this.fieldErrors.title && !this.fieldErrors.description) return true;
                    this.$nextTick(() => document.getElementById(this.fieldErrors.title ? 'event-title' : 'event-description')?.focus());
                    return false;
                },

                validateDetails() {
                    var form = this.form();
                    var requiredFields = ['category_id', 'format', 'location', 'city', 'starts_at', 'ends_at'];
                    var missingField = requiredFields.find((name) => !String(form.elements[name]?.value || '').trim());
                    if (missingField) {
                        form.elements[missingField]?.focus();
                        return false;
                    }

                    var startsAt = new Date(form.elements.starts_at.value);
                    var endsAt = new Date(form.elements.ends_at.value);
                    if (startsAt <= new Date()) {
                        this.fieldErrors.dates = 'The event start time must be in the future.';
                        form.elements.starts_at.focus();
                        return false;
                    }
                    if (endsAt <= startsAt) {
                        this.fieldErrors.dates = 'The end time must be after the start time.';
                        form.elements.ends_at.focus();
                        return false;
                    }
                    this.fieldErrors.dates = '';
                    return true;
                },

                restoreAiDraft() {
                    try {
                        var raw = window.sessionStorage ? sessionStorage.getItem('aix_copilot_session_v1') : null;
                        if (!raw) return;
                        var session = JSON.parse(raw);
                        if (!session || !session.applied || !session.draft || !session.draft.suggestions) return;
                        var suggestions = session.draft.suggestions;
                        if (suggestions.title && !this.values.title) this.values.title = suggestions.title;
                        if (suggestions.description && !this.values.description) this.values.description = suggestions.description;
                        if (suggestions.category && suggestions.category.id) {
                            this.$nextTick(() => {
                                var select = this.form()?.elements.category_id;
                                if (select && Array.from(select.options).some((option) => String(option.value) === String(suggestions.category.id))) {
                                    select.value = String(suggestions.category.id);
                                    select.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            });
                        }
                        sessionStorage.removeItem('aix_copilot_session_v1');
                    } catch (error) {
                        // Ignore unavailable or malformed session storage and keep the form usable.
                    }
                },
            };
        }
    </script>
</x-app-layout>
