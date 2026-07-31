<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use RuntimeException;

class SubmitEventAction
{
    /**
     * Submit an event for admin review (Draft -> UnderReview).
     */
    public function __invoke(Event $event): Event
    {
        if (! $event->status->isDraft()) {
            throw new RuntimeException('Only draft events can be submitted for review.');
        }

        $event->forceFill(['status' => EventStatus::UnderReview])->save();

        return $event;
    }
}
