<?php

namespace App\Actions\Events;

use App\Models\Event;

class RestoreEventAction
{
    /**
     * Restore a soft-deleted event, keeping its existing status.
     */
    public function __invoke(Event $event): Event
    {
        $event->restore();

        return $event;
    }
}
