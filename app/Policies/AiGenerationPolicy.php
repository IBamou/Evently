<?php

namespace App\Policies;

use App\Models\AiGeneration;
use App\Models\User;

class AiGenerationPolicy
{
    /**
     * Determine whether the user can view the generation status.
     */
    public function view(User $user, AiGeneration $generation): bool
    {
        return $generation->user_id === $user->id;
    }

    /**
     * Determine whether the user can record feedback on the generation.
     */
    public function feedback(User $user, AiGeneration $generation): bool
    {
        return $generation->user_id === $user->id;
    }
}
