<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use RuntimeException;

class RejectEventAction
{
    /**
     * Reject an event under review (UnderReview -> Draft).
     */
    public function __invoke(Event $event): Event
    {
        if (! $event->status->isUnderReview()) {
            throw new RuntimeException('Only events under review can be rejected.');
        }

        $event->forceFill(['status' => EventStatus::Draft])->save();

        return $event;
    }
}
