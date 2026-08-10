<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Str;

class CreateEventAction
{
    /**
     * Create a new event in draft status for the given organizer.
     */
    public function __invoke(User $organizer, array $data): Event
    {
        $data['slug'] = $this->uniqueSlug($data['title']);

        $event = $organizer->events()->create($data);
        $event->forceFill(['status' => EventStatus::Draft])->save();

        return $event;
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 2;

        while (Event::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
