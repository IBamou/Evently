<?php

namespace App\Actions\Events;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class UpdateEventAction
{
    /**
     * Update an existing event with validated data.
     */
    public function __invoke(Event $event, array $data): Event
    {
        if ($event->starts_at instanceof Carbon && $event->starts_at->isPast()) {
            throw new RuntimeException('Cannot update an event that has already started.');
        }

        if ($event->status->isUnderReview()) {
            throw new RuntimeException('Cannot edit an event that is under review.');
        }

        // Prevent ownership/status changes
        unset($data['organizer_id'], $data['status'], $data['deleted_at']);

        // Merge existing dates when absent from request
        if (empty($data['starts_at'])) {
            $data['starts_at'] = $event->starts_at;
        } else {
            $data['starts_at'] = $data['starts_at'] instanceof Carbon
                ? $data['starts_at']
                : Carbon::parse($data['starts_at']);
        }

        if (empty($data['ends_at'])) {
            $data['ends_at'] = $event->ends_at;
        } else {
            $data['ends_at'] = $data['ends_at'] instanceof Carbon
                ? $data['ends_at']
                : Carbon::parse($data['ends_at']);
        }

        /** @var Carbon $endsAt */
        $endsAt = $data['ends_at'];
        /** @var Carbon $startsAt */
        $startsAt = $data['starts_at'];

        // Prevent moving the start time into the past.
        if ($startsAt->isPast()) {
            throw new RuntimeException('Cannot move the event start time into the past.');
        }

        // Verify ends_at strictly after starts_at
        if ($endsAt->lte($startsAt)) {
            throw new RuntimeException('End time must be after start time.');
        }

        // Regenerate slug if title changed
        if (isset($data['title']) && $data['title'] !== $event->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $event->id);
        }

        $event->update($data);

        return $event;
    }

    private function uniqueSlug(string $title, int $exceptId): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 2;

        while (Event::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $exceptId)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
