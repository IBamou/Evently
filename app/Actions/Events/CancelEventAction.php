<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use RuntimeException;

class CancelEventAction
{
    /**
     * Cancel a published event (Published -> Cancelled). Terminal — no re-publish.
     */
    public function __invoke(Event $event): Event
    {
        if (! $event->status->isPublished()) {
            throw new RuntimeException('Only published events can be cancelled.');
        }

        $event->forceFill(['status' => EventStatus::Cancelled])->save();

        return $event;
    }
}
