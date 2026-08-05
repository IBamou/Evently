<?php

namespace App\Traits;

use App\Models\AiGeneration;
use App\Services\Ai\AiGenerationRecorder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasAiGenerationUsage
{
    /**
     * @return HasMany<AiGeneration, $this>
     */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    /**
     * Atomically check and reserve an AI generation slot for the user.
     *
     * @return true|string True when a slot was reserved, error code string when rate-limited.
     */
    public function canRunAiGeneration(): true|string
    {
        /** @var AiGenerationRecorder $recorder */
        $recorder = app(AiGenerationRecorder::class);

        return $recorder->reserveGenerationSlot($this->id) ?? true;
    }

    public function aiUsageToday(): int
    {
        /** @var AiGenerationRecorder $recorder */
        $recorder = app(AiGenerationRecorder::class);

        return $recorder->getDailyCount($this->id);
    }

    public function aiUsageThisMinute(): int
    {
        /** @var AiGenerationRecorder $recorder */
        $recorder = app(AiGenerationRecorder::class);

        return $recorder->getMinuteCount($this->id);
    }
}
