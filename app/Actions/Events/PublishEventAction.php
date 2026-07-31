<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Carbon\Carbon;
use RuntimeException;

class PublishEventAction
{
    /**
     * Publish an event (UnderReview -> Published).
     *
     * Validates completeness: description >= 20 chars, title/location/city exist,
     * ends_at strictly after starts_at, event not started yet.
     */
    public function __invoke(Event $event): Event
    {
        if (! $event->status->isUnderReview()) {
            throw new RuntimeException('Only events under review can be published.');
        }

        if (! $event->starts_at instanceof Carbon) {
            throw new RuntimeException('Event must have a start date.');
        }

        if ($event->starts_at->isPast()) {
            throw new RuntimeException('Cannot publish an event that has already started.');
        }

        // Verify completeness
        if (! $event->title || ! $event->location || ! $event->city) {
            throw new RuntimeException('Event is missing required fields (title, location, or city).');
        }

        if (mb_strlen($event->description) < 20) {
            throw new RuntimeException('Event description must be at least 20 characters long.');
        }

        if (! $event->ends_at instanceof Carbon) {
            throw new RuntimeException('Event must have an end date.');
        }

        if ($event->ends_at->lte($event->starts_at)) {
            throw new RuntimeException('Event end time must be after start time.');
        }

        $event->forceFill(['status' => EventStatus::Published])->save();

        return $event;
    }
}
