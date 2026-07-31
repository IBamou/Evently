<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any events.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the event.
     */
    public function view(User $user, Event $event): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create events.
     */
    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can submit the event for review.
     */
    public function submit(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id;
    }

    /**
     * Determine whether the user can publish the event.
     */
    public function publish(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reject the event.
     */
    public function reject(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can cancel the event.
     */
    public function cancel(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete (soft-delete) the event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the event.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }
}
